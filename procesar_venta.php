<?php
// acciones/procesar_venta.php - FIX: FECHA PHP, PUNTOS DINÁMICOS Y STOCK
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

if(empty($items)) { echo json_encode(['status'=>'error', 'msg'=>'Carrito vacío']); exit; }

try {
    $conexion->beginTransaction();

    // 1. OBTENER CONFIGURACIÓN (Para puntos)
    $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    $ratio_puntos = $conf['dinero_por_punto'] ?? 100; // Si falla, usa 100 por defecto
    if($ratio_puntos <= 0) $ratio_puntos = 100; // Evitar división por cero

    // 2. FECHA ACTUAL (Usamos PHP para asegurar zona horaria Argentina definida en db.php)
    $fecha_actual = date('Y-m-d H:i:s');

    // 3. INSERTAR VENTA
    // Usamos TRY/CATCH interno por si la tabla no tiene las columnas de descuento aún
    try {
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado, descuento_monto_cupon, descuento_manual, codigo_cupon) VALUES (1, ?, ?, ?, ?, ?, 'completada', ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo, $fecha_actual, $desc_cupon_monto, $desc_manual_monto, $cupon_codigo]);
    } catch (PDOException $e) {
        // Fallback porsiaca
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado) VALUES (1, ?, ?, ?, ?, ?, 'completada')";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo, $fecha_actual]);
    }
    
    $venta_id = $conexion->lastInsertId();

    // 4. DETALLES Y STOCK
    foreach($items as $item) {
        // Guardar detalle
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)");
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDetalle->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

        // Stock (Combo vs Unitario)
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

    // 5. FIADO Y PUNTOS
    if ($id_cliente > 1) { // Solo si no es Consumidor Final
        // Fiado
        if ($metodo === 'CtaCorriente') {
            $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, concepto, fecha) VALUES (?, ?, ?, 'debe', ?, 'Compra Fiado', ?)")->execute([$id_cliente, $venta_id, $user_id, $total, $fecha_actual]);
            $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?")->execute([$total, $id_cliente]);
        }

        // Puntos (Lógica dinámica solicitada)
        $puntos = floor($total / $ratio_puntos);
        if ($puntos > 0) {
            $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos, $id_cliente]);
        }
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
?>