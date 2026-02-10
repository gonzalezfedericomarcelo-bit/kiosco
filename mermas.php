<?php
// mermas.php - VERSIÓN FINAL (MENU FIXED + DISEÑO PREMIUM)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

// 2. SEGURIDAD
$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
if (!in_array('gestionar_mermas', $permisos) && $rol > 2) { header("Location: dashboard.php"); exit; }

// 3. PROCESAR BAJA (LÓGICA INTACTA)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_prod = $_POST['id_producto'];
    $cant = $_POST['cantidad'];
    $motivo = $_POST['motivo'];
    $nota = $_POST['nota_adicional'] ?? '';
    $motivo_full = $motivo . ($nota ? " ($nota)" : "");

    try {
        $conexion->beginTransaction();
        $stmt = $conexion->prepare("INSERT INTO mermas (id_producto, cantidad, motivo, fecha, id_usuario) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$id_prod, $cant, $motivo_full, $_SESSION['usuario_id']]);
        
        $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$cant, $id_prod]);
        $conexion->commit();
        header("Location: mermas.php?msg=ok"); exit;
    } catch (Exception $e) { 
        $conexion->rollBack(); 
        die("Error: " . $e->getMessage()); 
    }
}

// 4. DATOS
$productos = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_OBJ);
$mermas = $conexion->query("SELECT m.*, p.descripcion, u.usuario FROM mermas m JOIN productos p ON m.id_producto = p.id JOIN usuarios u ON m.id_usuario = u.id ORDER BY m.fecha DESC LIMIT 15")->fetchAll(PDO::FETCH_OBJ);

// KPI: Mermas de HOY
$hoy = date('Y-m-d');
$sqlMermasHoy = "SELECT COUNT(*) as cant, COALESCE(SUM(m.cantidad * p.precio_costo), 0) as costo_total 
                 FROM mermas m JOIN productos p ON m.id_producto = p.id 
                 WHERE DATE(m.fecha) = '$hoy'";
$kpiHoy = $conexion->query($sqlMermasHoy)->fetch(PDO::FETCH_ASSOC);

// KPI: Mermas del MES
$mes = date('Y-m');
$mermasMes = $conexion->query("SELECT COUNT(*) FROM mermas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes'")->fetchColumn() ?: 0;

$perdida_hoy = $kpiHoy['costo_total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Mermas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* DISEÑO AZUL UNIFICADO */
        .header-blue {
            background-color: #102A57;
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
            position: relative;
            overflow: visible;
            z-index: 0; /* Clave para que el menú flote por encima */
        }
        .bg-icon-large {
            position: absolute; top: 50%; right: 20px;
            transform: translateY(-50%) rotate(-10deg);
            font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
        }
        
        .stat-card {
            border: none; border-radius: 15px; padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
            background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }

        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">
    
    <?php include 'includes/layout_header.php'; ?>

    <div class="header-blue">
        <i class="bi bi-trash3 bg-icon-large"></i>
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-white">Control de Mermas</h2>
                    <p class="opacity-75 mb-0 text-white">Gestión de roturas, vencimientos y bajas de stock.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Bajas de Hoy</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $kpiHoy['cant']; ?> <small class="fs-6 text-muted">items</small></h2>
                        </div>
                        <div class="icon-box bg-primary-soft"><i class="bi bi-exclamation-triangle"></i></div>
                    </div>
                </div>
                
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Pérdida (Costo)</h6>
                            <h2 class="mb-0 fw-bold text-danger">$<?php echo number_format($perdida_hoy, 0, ',', '.'); ?></h2>
                        </div>
                        <div class="icon-box bg-danger-soft"><i class="bi bi-currency-dollar"></i></div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted small fw-bold mb-1 text-uppercase">Total Mes</h6>
                            <h2 class="mb-0 fw-bold text-warning"><?php echo $mermasMes; ?> <small class="fs-6 text-muted">items</small></h2>
                        </div>
                        <div class="icon-box bg-warning-soft"><i class="bi bi-calendar-check"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0 text-primary">
                        <i class="bi bi-box-arrow-down me-2"></i> Registrar Baja
                    </div>
                    <div class="card-body bg-light rounded-bottom">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Producto</label>
                                <select name="id_producto" id="selectProducto" class="form-select" required>
                                    <option></option>
                                    <?php foreach($productos as $p): ?>
                                        <option value="<?php echo $p->id; ?>"><?php echo htmlspecialchars($p->descripcion); ?> (Stock: <?php echo $p->stock_actual; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Cantidad</label>
                                    <input type="number" step="0.01" name="cantidad" class="form-control fw-bold" required placeholder="0.00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Motivo</label>
                                    <select name="motivo" class="form-select">
                                        <option value="Vencido">📅 Vencido</option>
                                        <option value="Roto">🔨 Roto / Dañado</option>
                                        <option value="Robo">🦹 Robo / Falta</option>
                                        <option value="Consumo">☕ Consumo Interno</option>
                                        <option value="Otros">📦 Otros</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Nota Adicional</label>
                                <textarea name="nota_adicional" class="form-control" rows="2" placeholder="Ej: Se rompió al descargar..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> CONFIRMAR BAJA
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">
                        <i class="bi bi-clock-history me-2 text-primary"></i> Historial Reciente
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Producto</th>
                                    <th>Motivo</th>
                                    <th class="text-end pe-3">Cant.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($mermas) > 0): ?>
                                    <?php foreach($mermas as $m): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?php echo date('d/m H:i', strtotime($m->fecha)); ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($m->descripcion); ?></div>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i> <?php echo $m->usuario; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-normal"><?php echo $m->motivo; ?></span>
                                        </td>
                                        <td class="text-end fw-bold text-danger pe-3">-<?php echo floatval($m->cantidad); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay registros de mermas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#selectProducto').select2({ 
                theme: 'bootstrap-5', 
                placeholder: "Buscar producto...",
                allowClear: true
            });

            // SweetAlert de éxito
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('msg') === 'ok') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Baja registrada', showConfirmButton: false, timer: 3000 });
            }
        });
    </script>

    <?php include 'includes/layout_footer.php'; ?>
</body>
</html>