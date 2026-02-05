<?php
session_start();
require_once '../includes/db.php'; // Ajusta la ruta si es necesario

if (!isset($_SESSION['usuario_id'])) { die(json_encode(['status'=>'error'])); }

$data = json_decode(file_get_contents('php://input'), true);
$nombre_ref = $data['referencia'];
$carrito = $data['carrito'];
$total = $data['total'];
$usuario = $_SESSION['usuario_id'];

try {
    $conexion->beginTransaction();
    
    // 1. Guardar Cabecera
    $stmt = $conexion->prepare("INSERT INTO ventas_suspendidas (fecha, nombre_cliente_temporal, total, id_usuario) VALUES (NOW(), ?, ?, ?)");
    $stmt->execute([$nombre_ref, $total, $usuario]);
    $id_sus = $conexion->lastInsertId();
    
    // 2. Guardar Items
    $stmtItem = $conexion->prepare("INSERT INTO ventas_suspendidas_items (id_suspendida, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    foreach($carrito as $prod) {
        $subtotal = $prod['precio'] * $prod['cantidad'];
        $stmtItem->execute([$id_sus, $prod['id'], $prod['cantidad'], $prod['precio'], $subtotal]);
    }
    
    $conexion->commit();
    echo json_encode(['status'=>'success']);
} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
}
?>
