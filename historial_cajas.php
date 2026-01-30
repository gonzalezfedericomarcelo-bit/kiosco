<?php
// historial_cajas.php - VISOR GERENCIAL DE CAJAS
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

// OBTENER HISTORIAL (Últimos 50 cierres)
$sql = "SELECT c.*, u.usuario, u.nombre_completo 
        FROM cajas_sesion c 
        JOIN usuarios u ON c.id_usuario = u.id 
        ORDER BY c.id DESC LIMIT 50";
$cajas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoría de Cajas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* ESTILOS DE ALERTA VISUAL */
        .fila-perfecta { border-left: 5px solid #198754; }
        .fila-faltante { border-left: 5px solid #dc3545; background-color: #fff5f5; }
        .fila-sobrante { border-left: 5px solid #ffc107; background-color: #fffcf2; }
        .fila-abierta { border-left: 5px solid #0d6efd; background-color: #f8f9fa; }
        
        .badge-estado { min-width: 100px; }
        .monto-rojo { color: #dc3545; font-weight: bold; }
        .monto-verde { color: #198754; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary"><i class="bi bi-shield-lock-fill"></i> Auditoría de Cajas</h3>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Empleado</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Total Ventas</th>
                                <th>Resultado (Dif)</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-3">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($cajas)): ?>
                                <tr><td colspan="8" class="text-center p-5 text-muted">No hay registros de cajas cerradas aún.</td></tr>
                            <?php else: ?>
                                <?php foreach($cajas as $c): 
                                    // Lógica de colores y estados
                                    $claseFila = 'fila-perfecta';
                                    $dif = $c['diferencia'];
                                    
                                    if($c['estado'] == 'abierta') {
                                        $claseFila = 'fila-abierta';
                                        $txtDif = '--';
                                        $colorDif = '';
                                    } else {
                                        if($dif < 0) { $claseFila = 'fila-faltante'; $colorDif = 'text-danger fw-bold'; $txtDif = '$ ' . number_format($dif, 2); }
                                        elseif($dif > 0) { $claseFila = 'fila-sobrante'; $colorDif = 'text-success fw-bold'; $txtDif = '+ $ ' . number_format($dif, 2); }
                                        else { $claseFila = 'fila-perfecta'; $colorDif = 'text-muted'; $txtDif = 'OK ($0)'; }
                                    }
                                    
                                    $apertura = date('d/m H:i', strtotime($c['fecha_apertura']));
                                    $cierre = $c['fecha_cierre'] ? date('d/m H:i', strtotime($c['fecha_cierre'])) : '<span class="badge bg-primary">EN CURSO</span>';
                                ?>
                                <tr class="<?php echo $claseFila; ?>">
                                    <td class="fw-bold ps-3">#<?php echo $c['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo $c['usuario']; ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo substr($c['nombre_completo'], 0, 15); ?></small>
                                    </td>
                                    <td><?php echo $apertura; ?></td>
                                    <td><?php echo $cierre; ?></td>
                                    <td class="fw-bold text-primary">$ <?php echo number_format($c['total_ventas'], 2); ?></td>
                                    <td class="<?php echo $colorDif; ?> fs-5"><?php echo $txtDif; ?></td>
                                    <td class="text-center">
                                        <?php if($c['estado']=='abierta'): ?>
                                            <span class="badge bg-primary badge-estado">ABIERTA</span>
                                        <?php elseif($dif == 0): ?>
                                            <span class="badge bg-success badge-estado"><i class="bi bi-check-lg"></i> PERFECTA</span>
                                        <?php elseif($dif < 0): ?>
                                            <span class="badge bg-danger badge-estado">⚠️ FALTANTE</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark badge-estado">⚠️ SOBRANTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if($c['estado'] == 'cerrada'): ?>
                                            <button class="btn btn-sm btn-outline-dark shadow-sm" onclick="verDetalle(<?php echo $c['id']; ?>)" title="Ver desglose completo">
                                                <i class="bi bi-search"></i> Ver
                                            </button>
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

    <div class="modal fade" id="modalDetalleCaja" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-receipt"></i> Detalle de Cierre de Caja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenido-detalle">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Cargando auditoría...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para cargar el detalle vía AJAX
        function verDetalle(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleCaja'));
            document.getElementById('contenido-detalle').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Analizando datos...</p></div>';
            modal.show();

            // Llamada AJAX al archivo que creamos
            $.get('acciones/ver_detalle_caja.php', { id: id }, function(data) {
                document.getElementById('contenido-detalle').innerHTML = data;
            }).fail(function() {
                document.getElementById('contenido-detalle').innerHTML = '<div class="alert alert-danger">Error al cargar los datos.</div>';
            });
        }
    </script>
</body>
</html>