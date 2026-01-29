<?php
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';

if (strlen($term) > 0) {
    // Buscamos clientes
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE nombre LIKE ? OR dni LIKE ? LIMIT 10");
    $stmt->execute(["%$term%", "%$term%"]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculamos saldos (si existe la tabla)
    $resultados = [];
    foreach($clientes as $c) {
        $saldo_deuda = 0;
        $saldo_favor = 0;
        
        // Intentar calcular saldo real
        try {
            // Deuda
            $stmtDeuda = $conexion->prepare("SELECT SUM(monto) FROM movimientos_cc WHERE id_cliente = ? AND tipo = 'debe'");
            $stmtDeuda->execute([$c['id']]);
            $deuda = $stmtDeuda->fetchColumn() ?: 0;

            // Pagos/Favor
            $stmtHaber = $conexion->prepare("SELECT SUM(monto) FROM movimientos_cc WHERE id_cliente = ? AND tipo = 'haber'");
            $stmtHaber->execute([$c['id']]);
            $haber = $stmtHaber->fetchColumn() ?: 0;

            $balance = $deuda - $haber;
            
            if($balance > 0) $saldo_deuda = $balance;
            if($balance < 0) $saldo_favor = abs($balance);

        } catch (Exception $e) { }

        $c['saldo_actual'] = $saldo_deuda;
        $c['saldo_favor'] = $saldo_favor;
        $resultados[] = $c;
    }
    
    echo json_encode($resultados);
}
?>