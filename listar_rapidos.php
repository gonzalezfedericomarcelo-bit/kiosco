<?php
// acciones/listar_rapidos.php
require_once '../includes/db.php';

$cat = $_GET['cat'] ?? '';
$filtro = "";

if($cat != '') {
    // Si hay categoría, filtramos por ella
    $filtro = "AND id_categoria = $cat";
} 

// Traemos los productos activos.
// TRUCO: Si quieres "Más Vendidos", haríamos un JOIN con ventas, pero por ahora mostramos todos ordenados por nombre para no complicar.
$sql = "SELECT id, descripcion, precio_venta, imagen_url FROM productos WHERE activo = 1 $filtro ORDER BY descripcion ASC LIMIT 20";
$stmt = $conexion->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($productos);
?>