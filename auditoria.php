<?php
// auditoria.php - VISOR DE SEGURIDAD (BOTÓN ROJO DEL DASHBOARD)
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) y Dueño (2) pueden entrar. Empleados NO.
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php");
    exit;
}

// Traemos los últimos 50 movimientos
$sql = "SELECT a.*, u.usuario as responsable 
        FROM auditoria a 
        LEFT JOIN usuarios u ON a.id_usuario = u.id 
        ORDER BY a.fecha DESC LIMIT 50";
$stmt = $conexion->query($sql);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - Seguridad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-arrow-left-circle"></i> Volver al Panel</a>
            <span class="text-white">Auditoría de Seguridad</span>
        </div>
    </nav>

    <div class="container">
        <div class="card shadow border-0">
            <div class="card-header bg-white p-3">
                <h5 class="mb-0 text-danger fw-bold"><i class="bi bi-shield-lock"></i> Registro de Actividades</h5>
                <small class="text-muted">Aquí ves quién modificó stock o datos sensibles.</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap text-secondary">
                                    <?php echo date("d/m/Y H:i", strtotime($log->fecha)); ?>
                                </td>
                                <td class="fw-bold text-primary">
                                    <?php echo htmlspecialchars($log->responsable); ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $log->accion; ?></span>
                                </td>
                                <td class="small">
                                    <?php echo htmlspecialchars($log->detalles); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($logs) == 0): ?>
                                <tr><td colspan="4" class="text-center p-4 text-muted">No hay movimientos sospechosos aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>