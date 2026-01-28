<?php
// acciones/procesar_venta.php - FIX: DUPLICADOS ELIMINADOS
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
// Recibimos el saldo usado
$saldo_favor_usado = $_POST['saldo_favor_usado'] ?? 0;


if(empty($items)) { echo json_encode(['status'=>'error', 'msg'=>'Carrito vacío']); exit; }

try {
    $conexion->beginTransaction();

    // 1. OBTENER CONFIGURACIÓN
    $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    $ratio_puntos = $conf['dinero_por_punto'] ?? 100; 
    if($ratio_puntos <= 0) $ratio_puntos = 100; 

    $fecha_actual = date('Y-m-d H:i:s');

    // 2. INSERTAR VENTA
    try {
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado, descuento_monto_cupon, descuento_manual, codigo_cupon) VALUES (1, ?, ?, ?, ?, ?, 'completada', ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo, $fecha_actual, $desc_cupon_monto, $desc_manual_monto, $cupon_codigo]);
    } catch (PDOException $e) {
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado) VALUES (1, ?, ?, ?, ?, ?, 'completada')";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo, $fecha_actual]);
    }
    
    $venta_id = $conexion->lastInsertId();

    // 3. DETALLES Y STOCK
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

    // 4. FIADO, PUNTOS Y SALDO A FAVOR
    if ($id_cliente > 1) { 
        // Fiado
        if ($metodo === 'CtaCorriente') {
            $monto_a_deber = $total - $saldo_favor_usado;
            
            // Registramos deuda solo si queda algo por pagar
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
    }
    
    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>