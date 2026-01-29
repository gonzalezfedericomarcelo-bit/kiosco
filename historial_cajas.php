<?php
// historial_cajas.php - VER LOS CIERRES DE CAJA Y DIFERENCIAS
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) o Dueño (2) pueden ver el historial completo
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

// OBTENER HISTORIAL
$sql = "SELECT c.*, u.usuario 
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
    <title>Historial de Cajas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .badge-sobrante { background-color: #198754; color: white; }
        .badge-faltante { background-color: #dc3545; color: white; }
        .badge-perfecto { background-color: #0d6efd; color: white; }
        .badge-abierta { background-color: #ffc107; color: black; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <h3 class="mb-4 fw-bold text-secondary"><i class="bi bi-clock-history"></i> Historial de Cajas y Turnos</h3>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cajero/Usuario</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Monto Inicial</th>
                                <th>Total Ventas</th>
                                <th>Monto Final (Real)</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($cajas)): ?>
                                <tr><td colspan="9" class="text-center p-4 text-muted">No hay cajas registradas aún.</td></tr>
                            <?php else: ?>
                                <?php foreach($cajas as $c): 
                                    // Determinar badge de diferencia
                                    $dif = $c['diferencia'];
                                    $badgeClase = 'badge-perfecto';
                                    $icon = 'bi-check-circle';
                                    
                                    if($dif > 0) { $badgeClase = 'badge-sobrante'; $icon = 'bi-arrow-up-circle'; }
                                    if($dif < 0) { $badgeClase = 'badge-faltante'; $icon = 'bi-arrow-down-circle'; }
                                    
                                    // Formateo de fechas
                                    $apertura = date('d/m H:i', strtotime($c['fecha_apertura']));
                                    $cierre = $c['fecha_cierre'] ? date('d/m H:i', strtotime($c['fecha_cierre'])) : '--';
                                ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $c['id']; ?></td>
                                    <td class="text-primary fw-bold text-uppercase"><?php echo $c['usuario']; ?></td>
                                    <td><?php echo $apertura; ?></td>
                                    <td><?php echo $cierre; ?></td>
                                    
                                    <td>$<?php echo number_format($c['monto_inicial'], 2); ?></td>
                                    <td class="fw-bold">$<?php echo number_format($c['total_ventas'], 2); ?></td>
                                    
                                    <td>
                                        <?php if($c['monto_final'] !== null): ?>
                                            $<?php echo number_format($c['monto_final'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if($c['estado'] == 'cerrada'): ?>
                                            <span class="badge <?php echo $badgeClase; ?> p-2">
                                                <i class="bi <?php echo $icon; ?>"></i> $<?php echo number_format($dif, 2); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if($c['estado'] == 'abierta'): ?>
                                            <span class="badge badge-abierta">EN CURSO</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">CERRADA</span>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>