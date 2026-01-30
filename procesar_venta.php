<?php
// acciones/procesar_venta.php - CON VALIDACIÓN DE STOCK Y AUDITORÍA
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) { echo json_encode(['status'=>'error', 'msg'=>'Sesión expirada']); exit; }

$items = $_POST['items'] ?? [];
$metodo = $_POST['metodo'] ?? 'Efectivo';
$total = $_POST['total'] ?? 0;
$id_cliente = $_POST['id_cliente'] ?? 1;
$user_id = $_SESSION['usuario_id'];
$cupon_codigo = $_POST['cupon_codigo'] ?? null;
$desc_cupon_monto = $_POST['desc_cupon_monto'] ?? 0;
$desc_manual_monto = $_POST['desc_manual_monto'] ?? 0;
$saldo_favor_usado = $_POST['saldo_favor_usado'] ?? 0;
$pago_deuda = $_POST['pago_deuda'] ?? 0;
$pagos_mixtos = $_POST['pagos_mixtos'] ?? null;

if(empty($items)) { echo json_encode(['status'=>'error', 'msg'=>'Carrito vacío']); exit; }

try {
    $conexion->beginTransaction();

    // 1. OBTENER ID CAJA ACTUAL
    $stmtCaja = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
    $stmtCaja->execute([$user_id]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    
    if(!$caja) { 
        throw new Exception("No hay caja abierta. Por favor abrí caja primero."); 
    }
    $id_caja_sesion = $caja['id'];

    // ---------------------------------------------------------
    // 2. VALIDACIÓN DE STOCK (NUEVO BLINDAJE)
    // ---------------------------------------------------------
    foreach($items as $item) {
        // Consultamos stock actual y si es combo
        $stmtProd = $conexion->prepare("SELECT descripcion, stock_actual, tipo FROM productos WHERE id = ?");
        $stmtProd->execute([$item['id']]);
        $prodDB = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$prodDB) throw new Exception("El producto ID {$item['id']} no existe.");

        $cantidad_venta = $item['cantidad'];

        if ($prodDB['tipo'] === 'combo') {
            // Si es combo, verificamos el stock de CADA hijo
            $stmtHijos = $conexion->prepare("
                SELECT p.descripcion, p.stock_actual, pc.cantidad as cant_hijo 
                FROM productos_combo pc 
                JOIN productos p ON pc.id_producto_hijo = p.id 
                WHERE pc.id_combo = ?
            ");
            $stmtHijos->execute([$item['id']]);
            $hijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($hijos as $hijo) {
                $stock_necesario = $hijo['cant_hijo'] * $cantidad_venta;
                if ($hijo['stock_actual'] < $stock_necesario) {
                    throw new Exception("Stock insuficiente para el componente '{$hijo['descripcion']}' del combo '{$prodDB['descripcion']}'. (Tienes {$hijo['stock_actual']}, necesitas $stock_necesario)");
                }
            }
        } elseif ($prodDB['tipo'] !== 'pesable') { 
            // Si es unitario (no pesable), validamos directo
            // (Los pesables a veces permiten decimales raros, pero idealmente también se validan)
            if ($prodDB['stock_actual'] < $cantidad_venta) {
                throw new Exception("Stock insuficiente para '{$prodDB['descripcion']}'. (Tienes ".floatval($prodDB['stock_actual']).", intentas vender $cantidad_venta)");
            }
        }
    }
    // ---------------------------------------------------------

    // 3. CONFIGURACIÓN Y FECHA
    $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    $ratio_puntos = $conf['dinero_por_punto'] ?? 100; 
    if($ratio_puntos <= 0) $ratio_puntos = 100; 
    $fecha_actual = date('Y-m-d H:i:s');

    // 4. INSERTAR VENTA
    $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado, descuento_monto_cupon, descuento_manual, codigo_cupon) VALUES (?, ?, ?, ?, ?, ?, 'completada', ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id_caja_sesion, $user_id, $id_cliente, $total, $metodo, $fecha_actual, $desc_cupon_monto, $desc_manual_monto, $cupon_codigo]);
    
    $venta_id = $conexion->lastInsertId();

    // 5. DETALLES Y DESCUENTO DE STOCK REAL
    foreach($items as $item) {
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)");
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDetalle->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

        $stmtTipo = $conexion->prepare("SELECT tipo FROM productos WHERE id = ?");
        $stmtTipo->execute([$item['id']]);
        $prod = $stmtTipo->fetch(PDO::FETCH_ASSOC);
        
        if ($prod && $prod['tipo'] === 'combo') {
            $stmtHijos = $conexion->prepare("SELECT id_producto_hijo, cantidad FROM productos_combo WHERE id_combo = ?");
            $stmtHijos->execute([$item['id']]);
            $hijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);
            foreach ($hijos as $hijo) {
                $desc = $hijo['cantidad'] * $item['cantidad'];
                $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$desc, $hijo['id_producto_hijo']]);
            }
        } else {
            $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$item['cantidad'], $item['id']]);
        }
    }

    // 6. SI ES PAGO MIXTO, GUARDAR EL DESGLOSE
    if($metodo === 'Mixto' && is_array($pagos_mixtos)) {
        $stmtMix = $conexion->prepare("INSERT INTO pagos_ventas (id_venta, metodo_pago, monto) VALUES (?, ?, ?)");
        foreach($pagos_mixtos as $metodo_nombre => $monto) {
            if($monto > 0) {
                $stmtMix->execute([$venta_id, $metodo_nombre, $monto]);
            }
        }
    }

    // 7. FIADO, PUNTOS Y SALDO A FAVOR
    if ($id_cliente > 1) { 
        // Fiado
        if ($metodo === 'CtaCorriente') {
            $monto_a_deber = $total - $saldo_favor_usado;
            if($monto_a_deber > 0) {
                 $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, concepto, fecha) VALUES (?, ?, ?, 'debe', ?, 'Compra Fiado', ?)")->execute([$id_cliente, $venta_id, $user_id, $monto_a_deber, $fecha_actual]);
                 $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?")->execute([$monto_a_deber, $id_cliente]);
            }
        }

        // Puntos
        $puntos = floor($total / $ratio_puntos);
        if ($puntos > 0) {
            $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos, $id_cliente]);
        }
        
        // Descontar Saldo a Favor
        if ($saldo_favor_usado > 0) {
            $conexion->prepare("UPDATE clientes SET saldo_favor = saldo_favor - ? WHERE id = ?")->execute([$saldo_favor_usado, $id_cliente]);
        }

        // 8. REGISTRAR PAGO DE DEUDA
        if ($pago_deuda > 0) {
            $concepto = "Cobro Deuda en Ticket #" . $venta_id;
            $stmtDeuda = $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, concepto, fecha) VALUES (?, ?, ?, 'haber', ?, ?, ?)");
            $stmtDeuda->execute([$id_cliente, $venta_id, $user_id, $pago_deuda, $concepto, $fecha_actual]);

            $stmtUpdCli = $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual - ? WHERE id = ?");
            $stmtUpdCli->execute([$pago_deuda, $id_cliente]);
        }
    }
    
    // ---------------------------------------------------------
    // 9. AUDITORÍA FORENSE (REGISTRO DE LA VENTA)
    // ---------------------------------------------------------
    $detalles_audit = "Venta #$venta_id | Total: $$total | Metodo: $metodo";
    if($desc_manual_monto > 0) $detalles_audit .= " | Desc.Manual: $$desc_manual_monto";
    
    $stmtAudit = $conexion->prepare("INSERT INTO auditoria (fecha, id_usuario, accion, detalles) VALUES (?, ?, 'Nueva Venta', ?)");
    $stmtAudit->execute([$fecha_actual, $user_id, $detalles_audit]);
    // ---------------------------------------------------------

    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>