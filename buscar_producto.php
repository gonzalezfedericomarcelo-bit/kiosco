<?php
// acciones/buscar_producto.php - VERSIÓN PREDICTIVA TOTAL
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';

// Si no hay término, no devolvemos nada
if(strlen($term) < 1) {
    echo json_encode(['status' => 'error']);
    exit;
}

// BÚSQUEDA HÍBRIDA:
// 1. Buscamos coincidencias parciales en NOMBRE o CÓDIGO (LIKE)
// 2. Limitamos a 10 para no saturar la lista
$sql = "SELECT id, descripcion, precio_venta, stock_actual, codigo_barras 
        FROM productos 
        WHERE (descripcion LIKE :term OR codigo_barras LIKE :term) 
        AND activo = 1 
        ORDER BY descripcion ASC 
        LIMIT 10";

$stmt = $conexion->prepare($sql);
$stmt->execute([':term' => "%$term%"]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($productos) {
    // Si encontramos algo, devolvemos la lista completa
    echo json_encode(['status' => 'success', 'data' => $productos]);
} else {
    echo json_encode(['status' => 'error']);
}
?>