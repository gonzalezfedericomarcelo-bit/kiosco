<?php
// acciones/buscar_cliente_ajax.php - CORREGIDO (Incluye saldo_favor)
require_once '../includes/db.php';

// Encabezado para que el navegador sepa que es JSON y no guarde caché vieja
header('Content-Type: application/json');

$term = $_GET['term'] ?? '';

if (strlen($term) > 0) {
    // AGREGADO: c.saldo_favor en la lista de campos
    $sql = "SELECT 
                c.id, 
                c.nombre, 
                c.dni, 
                c.whatsapp, 
                c.saldo_actual,
                c.saldo_favor, 
                (SELECT COUNT(*) FROM ventas v WHERE v.id_cliente = c.id) as cantidad_compras
            FROM clientes c 
            WHERE c.nombre LIKE :t OR c.dni LIKE :t 
            LIMIT 10";
            
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':t' => "%$term%"]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($res);
} else {
    echo json_encode([]);
}
?>