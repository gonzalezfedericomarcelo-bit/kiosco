<?php
// acciones/buscar_producto.php
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';

if(strlen($term) > 0) {
    // Busca por nombre o codigo de barras
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE (descripcion LIKE ? OR codigo_barras LIKE ?) AND activo = 1 LIMIT 10");
    $stmt->execute(["%$term%", "%$term%"]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($productos) {
        echo json_encode(['status' => 'success', 'data' => $productos]);
    } else {
        echo json_encode(['status' => 'error']);
    }
} else {
    echo json_encode(['status' => 'error']);
}
?>