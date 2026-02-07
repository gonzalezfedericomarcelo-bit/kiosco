<?php
// gastos.php - DISEÑO PREMIUM
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error db.php");

$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
if (!in_array('gestionar_gastos', $permisos) && $rol > 2) { header("Location: dashboard.php"); exit; }

// VERIFICAR CAJA
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$usuario_id]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$caja) { header("Location: apertura_caja.php"); exit; }
$id_caja_sesion = $caja['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $desc = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $cat = $_POST['categoria'];
    
    $stmt = $conexion->prepare("INSERT INTO gastos (descripcion, monto, categoria, fecha, id_usuario, id_caja_sesion) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([$desc, $monto, $cat, $_SESSION['usuario_id'], $id_caja_sesion]);
    header("Location: gastos.php?msg=ok"); exit;
}

$gastos = $conexion->query("SELECT g.*, u.usuario FROM gastos g JOIN usuarios u ON g.id_usuario = u.id ORDER BY g.fecha DESC LIMIT 15")->fetchAll();
// KPI HOY
$hoy = date('Y-m-d');
$totalHoy = $conexion->query("SELECT SUM(monto) FROM gastos WHERE DATE(fecha) = '$hoy'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .header-gradient {
            background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
            color: white; padding: 30px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(239, 71, 58, 0.2);
        }
        .stat-card { background: rgba(255,255,255,0.2); border-radius: 15px; padding: 15px; display: inline-flex; align-items: center; gap: 10px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    
    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="header-gradient">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Gastos de Caja</h2>
                <p class="opacity-75 mb-0">Registro de salidas y retiros.</p>
            </div>
            <div class="stat-card">
                <i class="bi bi-wallet2 fs-2"></i>
                <div>
                    <div class="small text-uppercase fw-bold opacity-75">Total Hoy</div>
                    <div class="h4 mb-0 fw-bold">$<?php echo number_format($totalHoy, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3"><i class="bi bi-dash-circle text-danger"></i> Nuevo Retiro</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Monto ($)</label>
                                <input type="number" step="0.01" name="monto" class="form-control form-control-lg fw-bold text-danger" required placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" required placeholder="Ej: Pago Proveedor">
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Proveedores">🚚 Proveedores</option>
                                    <option value="Servicios">💡 Servicios</option>
                                    <option value="Alquiler">🏠 Alquiler</option>
                                    <option value="Sueldos">👥 Sueldos</option>
                                    <option value="Retiro">💸 Retiro Ganancias</option>
                                    <option value="Otros">📦 Otros</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">REGISTRAR SALIDA</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Últimos Movimientos</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr><th>Fecha</th><th>Detalle</th><th>Cat.</th><th class="text-end pe-4">Monto</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($gastos as $g): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?php echo date('d/m H:i', strtotime($g->fecha)); ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($g->descripcion); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($g->usuario); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $g->categoria; ?></span></td>
                                    <td class="text-end text-danger fw-bold pe-4">-$<?php echo number_format($g->monto, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if(new URLSearchParams(window.location.search).get('msg') === 'ok') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Gasto registrado', showConfirmButton: false, timer: 3000 });
        }
    </script>
</body>
</html>