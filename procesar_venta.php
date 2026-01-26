<?php
// acciones/procesar_venta.php - VERSIÓN FINAL: COMBOS DINÁMICOS + FIADO + DESCUENTOS
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) { echo json_encode(['status'=>'error', 'msg'=>'Sesión expirada']); exit; }

$items = $_POST['items'] ?? [];
$metodo = $_POST['metodo'] ?? 'Efectivo';
$total = $_POST['total'] ?? 0;
$id_cliente = $_POST['id_cliente'] ?? 1;
$user_id = $_SESSION['usuario_id'];

// Datos opcionales de descuento
$cupon_codigo = $_POST['cupon_codigo'] ?? null;
$desc_cupon_monto = $_POST['desc_cupon_monto'] ?? 0;
$desc_manual_monto = $_POST['desc_manual_monto'] ?? 0;

if(empty($items)) { echo json_encode(['status'=>'error', 'msg'=>'Carrito vacío']); exit; }

try {
    $conexion->beginTransaction();

    // 1. INSERTAR VENTA (MODO RESILIENTE A ESTRUCTURA BD)
    try {
        // Intenta guardar con columnas de descuento
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado, 
                descuento_monto_cupon, descuento_manual, codigo_cupon) 
                VALUES (1, ?, ?, ?, ?, NOW(), 'completada', ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo, $desc_cupon_monto, $desc_manual_monto, $cupon_codigo]);
        
    } catch (PDOException $e) {
        // Si falla (ej. no corriste el SQL de descuentos), guarda modo compatible
        $sql = "INSERT INTO ventas (id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, fecha, estado) 
                VALUES (1, ?, ?, ?, ?, NOW(), 'completada')";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$user_id, $id_cliente, $total, $metodo]);
    }
    
    $venta_id = $conexion->lastInsertId();

    // 2. GUARDAR DETALLES Y DESCONTAR STOCK (LÓGICA DE COMBOS APLICADA)
    foreach($items as $item) {
        // A. Guardar detalle de venta (siempre se guarda el ítem vendido, sea combo o no)
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)");
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDetalle->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

        // B. LÓGICA DE STOCK INTELIGENTE
        // Primero consultamos qué tipo de producto es
        $stmtTipo = $conexion->prepare("SELECT tipo FROM productos WHERE id = ?");
        $stmtTipo->execute([$item['id']]);
        $productoInfo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
        
        if ($productoInfo && $productoInfo['tipo'] === 'combo') {
            // ES UN COMBO: Buscamos sus hijos y descontamos a ellos
            $stmtHijos = $conexion->prepare("SELECT id_producto_hijo, cantidad FROM productos_combo WHERE id_combo = ?");
            $stmtHijos->execute([$item['id']]);
            $hijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($hijos as $hijo) {
                // Cantidad a descontar = (Cantidad que lleva el combo * Cantidad de combos vendidos)
                $descuentoReal = $hijo['cantidad'] * $item['cantidad'];
                
                $stmtStockHijo = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
                $stmtStockHijo->execute([$descuentoReal, $hijo['id_producto_hijo']]);
            }
            // NOTA: No descontamos stock del ID del combo padre, ya que es virtual/dinámico.

        } else {
            // ES UNITARIO / PESABLE / OTRO: Descuento directo
            $stmtStock = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
            $stmtStock->execute([$item['cantidad'], $item['id']]);
        }
    }

    // 3. SI ES FIADO -> REGISTRAR DEUDA
    if ($metodo === 'CtaCorriente' && $id_cliente > 1) {
        $stmtCC = $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, id_usuario, tipo, monto, concepto, fecha) VALUES (?, ?, ?, 'debe', ?, 'Compra Fiado', NOW())");
        $stmtCC->execute([$id_cliente, $venta_id, $user_id, $total]);

        $stmtUpd = $conexion->prepare("UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?");
        $stmtUpd->execute([$total, $id_cliente]);
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'id_venta' => $venta_id]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['status' => 'error', 'msg' => 'Error Crítico: ' . $e->getMessage()]);
}
?>