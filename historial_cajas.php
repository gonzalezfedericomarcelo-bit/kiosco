<?php
// historial_cajas.php - DISEÑO AZUL Y ENLACES CORREGIDOS
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id']) || (isset($_SESSION['rol']) && $_SESSION['rol'] > 2)) {
    header("Location: dashboard.php"); exit;
}

// OBTENER HISTORIAL (Últimos 50 cierres + la abierta actual)
$sql = "SELECT c.*, u.usuario, u.nombre_completo 
        FROM cajas_sesion c 
        JOIN usuarios u ON c.id_usuario = u.id 
        ORDER BY c.id DESC LIMIT 50";
$cajas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/layout_header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary"><i class="bi bi-clock-history"></i> Historial de Cajas</h2>
            <p class="text-muted mb-0">Auditoría de aperturas, cierres y diferencias.</p>
        </div>
        <a href="cierre_caja.php" class="btn btn-primary rounded-pill fw-bold shadow-sm">
            <i class="bi bi-calculator me-2"></i> Ir a Caja Actual
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#ID</th>
                            <th>Responsable</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th>Total Ventas</th>
                            <th>Estado / Diferencia</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($cajas)): ?>
                            <tr><td colspan="7" class="text-center p-5 text-muted">No hay registros de cajas aún.</td></tr>
                        <?php else: ?>
                            <?php foreach($cajas as $c): 
                                $dif = $c['diferencia'];
                                $apertura = date('d/m H:i', strtotime($c['fecha_apertura']));
                                $cierre = $c['fecha_cierre'] ? date('d/m H:i', strtotime($c['fecha_cierre'])) : '-';
                                
                                // Estilos según estado
                                if($c['estado'] == 'abierta') {
                                    $badge = '<span class="badge bg-primary text-uppercase">Abierta</span>';
                                    $txtDif = '<span class="text-muted small">En curso...</span>';
                                    $borde = 'border-start border-primary border-4';
                                } else {
                                    if($dif < 0) { 
                                        $badge = '<span class="badge bg-danger">Faltante</span>';
                                        $txtDif = '<span class="text-danger fw-bold">$ '.number_format($dif, 2).'</span>';
                                        $borde = 'border-start border-danger border-4';
                                    } elseif($dif > 0) { 
                                        $badge = '<span class="badge bg-warning text-dark">Sobrante</span>';
                                        $txtDif = '<span class="text-success fw-bold">+$ '.number_format($dif, 2).'</span>';
                                        $borde = 'border-start border-warning border-4';
                                    } else { 
                                        $badge = '<span class="badge bg-success">Perfecta</span>';
                                        $txtDif = '<span class="text-success fw-bold"><i class="bi bi-check-all"></i> OK</span>';
                                        $borde = 'border-start border-success border-4';
                                    }
                                }
                            ?>
                            <tr class="<?php echo $borde; ?>">
                                <td class="fw-bold ps-4 text-muted">#<?php echo $c['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo ucfirst($c['usuario']); ?></div>
                                </td>
                                <td><?php echo $apertura; ?></td>
                                <td><?php echo $cierre; ?></td>
                                <td class="fw-bold">$ <?php echo number_format($c['total_ventas'], 2); ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <div><?php echo $badge; ?></div>
                                        <?php echo $txtDif; ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($c['estado'] == 'cerrada'): ?>
                                        <a href="ver_detalle_caja.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                            <i class="bi bi-eye-fill me-1"></i> Ver Detalle
                                        </a>
                                    <?php else: ?>
                                        <a href="cierre_caja.php?id_sesion=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="bi bi-box-arrow-in-right"></i> Ir a Cerrar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>