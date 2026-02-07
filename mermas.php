<?php
// mermas.php - VERSIÓN CORREGIDA (Sin error number_format)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error Crítico: No se encuentra db.php");

// SEGURIDAD
$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
if (!in_array('gestionar_mermas', $permisos) && $rol > 2) { header("Location: dashboard.php"); exit; }

// PROCESAR BAJA
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
    } catch (Exception $e) { $conexion->rollBack(); die("Error: " . $e->getMessage()); }
}

// DATOS
$productos = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 ORDER BY descripcion ASC")->fetchAll();
$mermas = $conexion->query("SELECT m.*, p.descripcion, u.usuario FROM mermas m JOIN productos p ON m.id_producto = p.id JOIN usuarios u ON m.id_usuario = u.id ORDER BY m.fecha DESC LIMIT 15")->fetchAll();

// KPI: Mermas de HOY (Corrección del error NULL)
$hoy = date('Y-m-d');
$sqlMermasHoy = "SELECT COUNT(*) as cant, COALESCE(SUM(m.cantidad * p.precio_costo), 0) as costo_total 
                 FROM mermas m JOIN productos p ON m.id_producto = p.id 
                 WHERE DATE(m.fecha) = '$hoy'";
$kpiHoy = $conexion->query($sqlMermasHoy)->fetch(PDO::FETCH_ASSOC);

// Aseguramos que no sea null
$perdida_hoy = $kpiHoy['costo_total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Mermas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        .header-gradient {
            background: linear-gradient(135deg, #870000 0%, #190a05 100%);
            color: white; padding: 30px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .stat-card {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px;
        }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    
    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="header-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="fw-bold mb-0"><i class="bi bi-trash3"></i> Control de Mermas</h2>
                    <p class="opacity-75 mb-0">Gestión de roturas, vencimientos y bajas.</p>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="fs-1 opacity-50"><i class="bi bi-exclamation-triangle"></i></div>
                                <div>
                                    <div class="small text-uppercase opacity-75 fw-bold">Items Hoy</div>
                                    <div class="h4 fw-bold mb-0"><?php echo $kpiHoy['cant']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card bg-danger bg-opacity-25 border-0">
                                <div class="fs-1 text-danger"><i class="bi bi-currency-dollar"></i></div>
                                <div>
                                    <div class="small text-uppercase text-danger fw-bold">Pérdida Est.</div>
                                    <div class="h4 fw-bold mb-0 text-white">$<?php echo number_format($perdida_hoy, 0); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3"><i class="bi bi-box-arrow-down"></i> Registrar Baja</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Producto</label>
                                <select name="id_producto" id="selectProducto" class="form-select" required>
                                    <option></option>
                                    <?php foreach($productos as $p): ?>
                                        <option value="<?php echo $p->id; ?>"><?php echo htmlspecialchars($p->descripcion); ?> (Stock: <?php echo $p->stock_actual; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Cantidad</label>
                                    <input type="number" step="0.01" name="cantidad" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Motivo</label>
                                    <select name="motivo" class="form-select">
                                        <option value="Vencido">📅 Vencido</option>
                                        <option value="Roto">🔨 Roto</option>
                                        <option value="Robo">🦹 Robo</option>
                                        <option value="Consumo">☕ Consumo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nota (Opcional)</label>
                                <textarea name="nota_adicional" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">CONFIRMAR BAJA</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Historial Reciente</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr><th>Fecha</th><th>Producto</th><th>Motivo</th><th class="text-end">Cant.</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($mermas as $m): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?php echo date('d/m H:i', strtotime($m->fecha)); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($m->descripcion); ?></div>
                                        <small class="text-muted"><i class="bi bi-person"></i> <?php echo $m->usuario; ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo $m->motivo; ?></span></td>
                                    <td class="text-end fw-bold text-danger pe-3">-<?php echo floatval($m->cantidad); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#selectProducto').select2({ theme: 'bootstrap-5', placeholder: "Buscar producto..." });
            if(new URLSearchParams(window.location.search).get('msg') === 'ok') {
                Swal.fire({ icon: 'success', title: 'Baja Exitosa', timer: 1500, showConfirmButton: false });
            }
        });
    </script>
</body>
</html>