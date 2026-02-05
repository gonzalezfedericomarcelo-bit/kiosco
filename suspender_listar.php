<?php
require_once '../includes/db.php';

$sql = "SELECT id, fecha, nombre_cliente_temporal, total FROM ventas_suspendidas ORDER BY id DESC";
$ventas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if(empty($ventas)) {
    echo '<div class="text-center p-4 text-muted">No hay ventas en espera.</div>';
} else {
    echo '<div class="list-group">';
    foreach($ventas as $v) {
        $hora = date('H:i', strtotime($v['fecha']));
        echo '
        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="recuperarVentaId('.$v['id'].')">
            <div>
                <span class="badge bg-warning text-dark me-2">'.$hora.'hs</span>
                <strong>'.htmlspecialchars($v['nombre_cliente_temporal']).'</strong>
            </div>
            <span class="fw-bold text-success">$'.number_format($v['total'],0).'</span>
        </button>';
    }
    echo '</div>';
}
?>
