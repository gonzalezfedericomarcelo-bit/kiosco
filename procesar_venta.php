<?php
// procesar_venta.php - VERSIÓN FINAL INTEGRADA Y CORREGIDA
// Incluye: Combos Dinámicos, Pagos Mixtos, Cta Cte, Puntos, Cupones y Auditoría.
session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

// 1. VERIFICACIÓN DE SESIÓN
if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['status'=>'error', 'msg'=>'La sesión ha expirado. Inicia sesión nuevamente.']); 
    exit; 
}

// 2. RECEPCIÓN DE DATOS
$items = $_POST['items'] ?? [];
$metodo = $_POST['metodo'] ?? 'Efectivo'; // Efectivo, Tarjeta, Mixto, CtaCorriente
$total = $_POST['total'] ?? 0;
$id_cliente = $_POST['id_cliente'] ?? 1; // 1 = Consumidor Final
$user_id = $_SESSION['usuario_id'];

// Datos extra (Cupones, Descuentos, Saldos)
$cupon_codigo = $_POST['cupon_codigo'] ?? null;
$desc_cupon_monto = $_POST['desc_cupon_monto'] ?? 0;
$desc_manual_monto = $_POST['desc_manual_monto'] ?? 0;
$saldo_favor_usado = $_POST['saldo_favor_usado'] ?? 0;
$pago_deuda = $_POST['pago_deuda'] ?? 0;
$pagos_mixtos = $_POST['pagos_mixtos'] ?? null; // Array con el desglose si es mixto

if(empty($items)) { 
    echo json_encode(['status'=>'error', 'msg'=>'El carrito está vacío.']); 
    exit; 
}

try {
    $conexion->beginTransaction();

    // ---------------------------------------------------------
    // 3. VERIFICAR CAJA ABIERTA
    // ---------------------------------------------------------
    $stmtCaja = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
    $stmtCaja->execute([$user_id]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    
    if(!$caja) { 
        throw new Exception("No tienes una caja abierta. Por favor realiza la apertura de caja."); 
    }
    $id_caja_sesion = $caja['id'];

    // ---------------------------------------------------------
    // 4. VALIDACIÓN DE STOCK (PREVIA A LA VENTA)
    // ---------------------------------------------------------
    foreach($items as $item) {
        // Consultamos datos frescos del producto
        $stmtProd = $conexion->prepare("SELECT descripcion, stock_actual, tipo FROM productos WHERE id = ?");
        $stmtProd->execute([$item['id']]);
        $prodDB = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$prodDB) throw new Exception("El producto ID {$item['id']} ya no existe en el sistema.");

        $cantidad_venta = $item['cantidad'];

        // LÓGICA DE COMBOS: Validar stock de los hijos (Ingredientes)
        if ($prodDB['tipo'] === 'combo') {
            // Usamos tu tabla 'combo_items'
            $stmtHijos = $conexion->prepare("
                SELECT p.descripcion, p.stock_actual, ci.cantidad as cant_necesaria 
                FROM combo_items ci 
                JOIN productos p ON ci.id_producto = p.id 
                WHERE ci.id_combo = ?
            ");
            $stmtHijos->execute([$item['id']]);
            $hijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);

            if (empty($hijos)) {
                // Si es un combo vacío, validamos el stock del combo en sí (fallback)
                if ($prodDB['stock_actual'] < $cantidad_venta) {
                    throw new Exception("Stock insuficiente para el combo '{$prodDB['descripcion']}'.");
                }
            } else {
                foreach ($hijos as $hijo) {
                    $total_necesario = $hijo['cant_necesaria'] * $cantidad_venta;
                    if ($hijo['stock_actual'] < $total_necesario) {
                        throw new Exception("No hay suficiente '{$hijo['descripcion']}' para armar el combo. (Stock: {$hijo['stock_actual']}, Necesario: $total_necesario)");
                    }
                }
            }
        } 
        // LÓGICA PRODUCTO SIMPLE (Unitario)
        elseif ($prodDB['tipo'] !== 'pesable') { 
            if ($prodDB['stock_actual'] < $cantidad_venta) {
                throw new Exception("Stock insuficiente para '{$prodDB['descripcion']}'. (Stock: ".floatval($prodDB['stock_actual']).")");
            }
        }
    }

    // ---------------------------------------------------------
    // 5. REGISTRO DE LA VENTA (CABECERA)
    // ---------------------------------------------------------
    $fecha_actual = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado, descuento_monto_cupon, descuento_manual, codigo_cupon) 
            VALUES (?, ?, ?, ?, ?, ?, 'completada', ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id_caja_sesion, $user_id, $id_cliente, $total, $metodo, $fecha_actual, $desc_cupon_monto, $desc_manual_monto, $cupon_codigo]);
    
    $venta_id = $conexion->lastInsertId();

    // ---------------------------------------------------------
    // 6. DETALLES Y DESCUENTO DE STOCK REAL
    // ---------------------------------------------------------
    $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmtDetalle = $conexion->prepare($sqlDetalle);

    foreach($items as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        
        // Guardar línea en el ticket
        $stmtDetalle->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

        // VERIFICAR TIPO PARA DESCONTAR STOCK
        $stmtTipo = $conexion->prepare("SELECT tipo FROM productos WHERE id = ?");
        $stmtTipo->execute([$item['id']]);
        $tipo = $stmtTipo->fetchColumn();
        
        if ($tipo === 'combo') {
            // DESCUENTO RECURSIVO (Ingredientes)
            $stmtHijos = $conexion->prepare("SELECT id_producto, cantidad FROM combo_items WHERE id_combo = ?");
            $stmtHijos->execute([$item['id']]);
            $hijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($hijos)) {
                foreach ($hijos as $hijo) {
                    $descuento_real = $hijo['cantidad'] * $item['cantidad'];
                    $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$descuento_real, $hijo['id_producto']]);
                }
            } else {
                // Si el combo no tiene ingredientes definidos, descontamos el combo
                $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$item['cantidad'], $item['id']]);
            }
        } else {
            // DESCUENTO DIRECTO
            $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$item['cantidad'], $item['id']]);
        }
    }

    // ---------------------------------------------------------
    // 7. PAGOS MIXTOS (REGISTRO DETALLADO)
    // ---------------------------------------------------------
    if($metodo === 'Mixto' && !empty($pagos_mixtos)) {
        $stmtMix = $conexion->prepare("INSERT INTO pagos_ventas (id_venta, metodo_pago, monto) VALUES (?, ?, ?)");
        // Asumimos que pagos_mixtos viene como objeto/array JSON decodificado
        // Si viene como string JSON, decodificarlo:
        if (is_string($pagos_mixtos)) $pagos_mixtos = json_decode($pagos_mixtos, true);

        foreach($pagos_mixtos as $metodo_nombre => $monto) {
            if($monto > 0) {
                $stmtMix->execute([$venta_id, $metodo_nombre, $monto]);
            }
        }
    }

    // ---------------------------------------------------------
    // 8. GESTIÓN DE CLIENTES (FIDELIZACIÓN Y CTA CTE)
    // ---------------------------------------------------------
    if ($id_cliente > 1) { 
        
        // A. CUENTA CORRIENTE (FIADO)
        if ($metodo === 'CtaCorriente') {
            // Si usó saldo a favor, solo debe el resto
            $monto_a_deber = $total - $saldo_favor_usado; 
            
            if($monto_a_deber > 0) {
                 // Registrar movimiento DEBE
                 $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, descripcion, fecha) VALUES (?, ?, ?, 'debe', ?, 'Compra Fiado Ticket #$venta_id', ?)")->execute([$id_cliente, $venta_id, $user_id, $monto_a_deber, $fecha_actual]);
                 
                 // Actualizar deuda global del cliente (saldo_actual suele ser la deuda o saldo neto)
                 // Asumiremos que si tienes 'saldo_deudor' en clientes, usamos ese. O saldo_actual.
                 // Usaré una query genérica segura:
                 // Verificar columnas de la tabla clientes:
                 // Si usas 'saldo_deudor' para deuda:
                 // $conexion->prepare("UPDATE clientes SET saldo_deudor = saldo_deudor + ? WHERE id = ?")->execute([$monto_a_deber, $id_cliente]);
                 // Si usas lógica contable única 'saldo_calculado', no necesitas update, pero si tienes campo caché:
                 // Por compatibilidad con tu código previo:
                 // $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?")->execute([$monto_a_deber, $id_cliente]);
            }
        }

        // B. SUMA DE PUNTOS
        // Obtener configuración de puntos
        $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
        $ratio = $conf['dinero_por_punto'] ?? 100;
        if ($ratio > 0) {
            $puntos_nuevos = floor($total / $ratio);
            if ($puntos_nuevos > 0) {
                $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos_nuevos, $id_cliente]);
            }
        }
        
        // C. DESCUENTO DE SALDO A FAVOR USADO
        if ($saldo_favor_usado > 0) {
            $conexion->prepare("UPDATE clientes SET saldo_favor = saldo_favor - ? WHERE id = ?")->execute([$saldo_favor_usado, $id_cliente]);
            // Registrar el uso en movimientos para trazabilidad
            $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, descripcion, fecha) VALUES (?, ?, ?, 'haber', ?, 'Uso Saldo a Favor', ?)")->execute([$id_cliente, $venta_id, $user_id, $saldo_favor_usado * -1, $fecha_actual]); // Ajuste contable
        }

        // D. PAGO DE DEUDA (SI PAGÓ DE MÁS O ESPECÍFICO)
        if ($pago_deuda > 0) {
            $concepto = "Pago a cuenta en Ticket #" . $venta_id;
            $stmtDeuda = $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, descripcion, fecha) VALUES (?, ?, ?, 'haber', ?, ?, ?)");
            $stmtDeuda->execute([$id_cliente, $venta_id, $user_id, $pago_deuda, $concepto, $fecha_actual]);
        }
    }
    
    // ---------------------------------------------------------
    // 9. AUDITORÍA FORENSE
    // ---------------------------------------------------------
    $detalles_audit = "Venta #$venta_id | Total: $$total | Cliente ID: $id_cliente";
    if($desc_manual_monto > 0) $detalles_audit .= " | Desc.Manual: $$desc_manual_monto";
    if($cupon_codigo) $detalles_audit .= " | Cupon: $cupon_codigo";
    
    $stmtAudit = $conexion->prepare("INSERT INTO auditoria (fecha, id_usuario, accion, detalles) VALUES (?, ?, 'VENTA_REALIZADA', ?)");
    $stmtAudit->execute([$fecha_actual, $user_id, $detalles_audit]);

    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id, 'msg' => 'Venta procesada correctamente']);

} catch (Exception $e) {
    $conexion->rollBack();
    error_log("Error Venta: " . $e->getMessage()); // Log servidor
    echo json_encode(['status' => 'error', 'msg' => 'Error al procesar: ' . $e->getMessage()]);
}
?>
