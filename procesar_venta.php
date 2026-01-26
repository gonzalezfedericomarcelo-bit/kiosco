<?php
// acciones/procesar_venta.php - VERSIÓN FINAL CON FIADO INTEGRADO
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) { echo json_encode(['status'=>'error', 'msg'=>'Sesión expirada']); exit; }

$items = $_POST['items'] ?? [];
$metodo = $_POST['metodo'] ?? 'Efectivo';
$total = $_POST['total'] ?? 0;
$id_cliente = $_POST['id_cliente'] ?? 1; // Recibimos el cliente real
$user_id = $_SESSION['usuario_id'];

if(empty($items)) { echo json_encode(['status'=>'error', 'msg'=>'Carrito vacío']); exit; }

try {
    $conexion->beginTransaction();

    // 1. Guardar Venta
    $stmt = $conexion->prepare("INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado) VALUES (1, ?, ?, ?, ?, NOW(), 'completada')");
    $stmt->execute([$user_id, $id_cliente, $total, $metodo]);
    $venta_id = $conexion->lastInsertId();

    // 2. Guardar Detalles y Descontar Stock
    foreach($items as $item) {
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)");
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDetalle->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

        $stmtStock = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        $stmtStock->execute([$item['cantidad'], $item['id']]);
    }

    // 3. SI ES FIADO (CTA CORRIENTE) -> REGISTRAR DEUDA
    if ($metodo === 'CtaCorriente' && $id_cliente > 1) {
        // A. Insertar en tabla de movimientos
        $stmtCC = $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, concepto, fecha) VALUES (?, ?, ?, 'debe', ?, 'Compra Fiado', NOW())");
        $stmtCC->execute([$id_cliente, $venta_id, $user_id, $total]);

        // B. Actualizar saldo en la tabla clientes
        $stmtUpd = $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?");
        $stmtUpd->execute([$total, $id_cliente]);
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>