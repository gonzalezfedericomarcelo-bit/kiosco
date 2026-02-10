<?php
// gestionar_cupones.php - DISEÑO PREMIUM AZUL + MENÚ FIXED
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

// 3. LÓGICA DE BORRADO
if (isset($_GET['borrar'])) {
    $conexion->prepare("DELETE FROM cupones WHERE id = ?")->execute([$_GET['borrar']]);
    header("Location: gestionar_cupones.php?msg=del"); exit;
}

$mensaje = '';

// 4. LÓGICA DE CREACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = strtoupper(trim($_POST['codigo']));
    $porcentaje = (int)$_POST['porcentaje'];
    $vencimiento = $_POST['vencimiento'];
    $limite = (int)$_POST['limite'];

    $stmt = $conexion->prepare("SELECT COUNT(*) FROM cupones WHERE codigo = ?");
    $stmt->execute([$codigo]);
    
    if ($stmt->fetchColumn() > 0) {
        $mensaje = '<div class="alert alert-danger shadow-sm border-0"><i class="bi bi-x-circle-fill me-2"></i> Código duplicado.</div>';
    } else {
        $sql = "INSERT INTO cupones (codigo, descuento_porcentaje, fecha_limite, cantidad_limite, usos_actuales, activo) VALUES (?, ?, ?, ?, 0, 1)";
        $conexion->prepare($sql)->execute([$codigo, $porcentaje, $vencimiento, $limite]);
        header("Location: gestionar_cupones.php?msg=ok"); exit;
    }
}

// 5. CONSULTAS PARA LISTADO Y WIDGETS
$cupones = $conexion->query("SELECT * FROM cupones ORDER BY fecha_limite DESC")->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$total_usos = $conexion->query("SELECT SUM(usos_actuales) FROM cupones")->fetchColumn() ?: 0;
$activos = 0;
$por_vencer = 0;
$hoy = date('Y-m-d');
$proxima_semana = date('Y-m-d', strtotime('+7 days'));

foreach($cupones as $c) { 
    if($c['fecha_limite'] >= $hoy) {
        $activos++;
        if($c['fecha_limite'] <= $proxima_semana) $por_vencer++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketing & Cupones</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        /* BANNER AZUL INSTITUCIONAL */
        .header-blue {
            background-color: #102A57;
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
            position: relative;
            overflow: visible;
            z-index: 1;
        }
        .bg-icon-large {
            position: absolute; top: 50%; right: 20px;
            transform: translateY(-50%) rotate(-10deg);
            font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
            z-index: 0;
        }
        
        /* WIDGETS */
        .stat-card {
            border: none; border-radius: 15px; padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
            background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }

        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control-lg { font-size: 1rem; padding: 0.75rem 1rem; }
    </style>
</head>
<body class="bg-light">
    
    <?php include 'includes/layout_header.php'; ?>

    <div class="header-blue">
        <i class="bi bi-ticket-perforated bg-icon-large"></i>
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-white">Marketing & Cupones</h2>
                    <p class="opacity-75 mb-0 text-white">Gestioná descuentos y promociones para fidelizar clientes.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Cupones Activos</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $activos; ?></h2>
                        </div>
                        <div class="icon-box bg-success-soft"><i class="bi bi-check-circle"></i></div>
                    </div>
                </div>
                
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Usos Totales</h6>
                            <h2 class="mb-0 fw-bold text-primary"><?php echo $total_usos; ?></h2>
                        </div>
                        <div class="icon-box bg-primary-soft"><i class="bi bi-people"></i></div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Vencen pronto</h6>
                            <h2 class="mb-0 fw-bold text-warning"><?php echo $por_vencer; ?></h2>
                        </div>
                        <div class="icon-box bg-warning-soft"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0 text-primary">
                        <i class="bi bi-plus-circle-fill me-2"></i> Crear Nuevo Cupón
                    </div>
                    <div class="card-body bg-light rounded-bottom">
                        <?php echo $mensaje; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Código del Cupón</label>
                                <input type="text" name="codigo" class="form-control form-control-lg text-uppercase fw-bold shadow-sm" placeholder="EJ: PROMO20" required>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted text-uppercase">% Descuento</label>
                                    <div class="input-group">
                                        <input type="number" name="porcentaje" class="form-control fw-bold" min="1" max="100" required>
                                        <span class="input-group-text bg-white border-start-0 text-muted">%</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted text-uppercase">Límite Usos</label>
                                    <input type="number" name="limite" class="form-control" value="0" placeholder="0 = ∞">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="small fw-bold text-muted text-uppercase">Fecha de Vencimiento</label>
                                <input type="date" name="vencimiento" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="bi bi-save me-2"></i> GUARDAR CUPÓN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">
                        <i class="bi bi-list-task me-2 text-primary"></i> Cupones Generados
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4">Código / Descuento</th>
                                    <th>Estado</th>
                                    <th>Uso / Límite</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($cupones) > 0): ?>
                                    <?php foreach($cupones as $c): 
                                        $venc = $c['fecha_limite'];
                                        $vencido = ($venc < date('Y-m-d'));
                                        $agotado = ($c['cantidad_limite'] > 0 && $c['usos_actuales'] >= $c['cantidad_limite']);
                                        
                                        if($vencido) {
                                            $badge_estado = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Vencido</span>';
                                        } elseif($agotado) {
                                            $badge_estado = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Agotado</span>';
                                        } else {
                                            $badge_estado = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activo</span>';
                                        }
                                    ?>
                                    <tr class="<?php echo ($vencido || $agotado) ? 'opacity-50' : ''; ?>">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark fs-5"><?php echo $c['codigo']; ?></div>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                <?php echo $c['descuento_porcentaje']; ?>% OFF
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $badge_estado; ?>
                                            <div class="small text-muted mt-1">Vence: <?php echo date('d/m/y', strtotime($venc)); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo $c['usos_actuales']; ?> <small class="text-muted fw-normal">usos</small></div>
                                            <small class="text-muted">Límite: <?php echo $c['cantidad_limite'] > 0 ? $c['cantidad_limite'] : '∞'; ?></small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button onclick="confirmarBorrado(<?php echo $c['id']; ?>)" class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-sm">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay cupones creados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script>
        // CONFIRMACIÓN DE BORRADO
        function confirmarBorrado(id) {
            Swal.fire({
                title: '¿Eliminar cupón?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "gestionar_cupones.php?borrar=" + id;
                }
            })
        }

        // Alertas Toast de éxito
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'ok') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Cupón creado correctamente', showConfirmButton: false, timer: 3000 });
        } else if(urlParams.get('msg') === 'del') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Cupón eliminado', showConfirmButton: false, timer: 3000 });
        }
    </script>

    <?php include 'includes/layout_footer.php'; ?>
</body>
</html>