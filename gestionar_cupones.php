<?php
// gestionar_cupones.php - DISEÑO PREMIUM
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error db.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

if (isset($_GET['borrar'])) {
    $conexion->prepare("DELETE FROM cupones WHERE id = ?")->execute([$_GET['borrar']]);
    header("Location: gestionar_cupones.php?msg=del"); exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = strtoupper(trim($_POST['codigo']));
    $porcentaje = (int)$_POST['porcentaje'];
    $vencimiento = $_POST['vencimiento'];
    $limite = (int)$_POST['limite'];

    $stmt = $conexion->prepare("SELECT COUNT(*) FROM cupones WHERE codigo = ?");
    $stmt->execute([$codigo]);
    
    if ($stmt->fetchColumn() > 0) {
        $mensaje = '<div class="alert alert-danger rounded-pill text-center">❌ Código duplicado.</div>';
    } else {
        $sql = "INSERT INTO cupones (codigo, descuento_porcentaje, fecha_limite, cantidad_limite, usos_actuales, activo) VALUES (?, ?, ?, ?, 0, 1)";
        $conexion->prepare($sql)->execute([$codigo, $porcentaje, $vencimiento, $limite]);
        $mensaje = '<div class="alert alert-success rounded-pill text-center">✅ Cupón creado.</div>';
    }
}

$cupones = $conexion->query("SELECT * FROM cupones ORDER BY fecha_limite DESC")->fetchAll(PDO::FETCH_ASSOC);
$activos = 0; foreach($cupones as $c) { if($c['fecha_limite'] >= date('Y-m-d')) $activos++; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cupones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .header-gradient {
            background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
            color: white; padding: 30px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(74, 0, 224, 0.2);
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
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Marketing & Cupones</h2>
                <p class="opacity-75 mb-0">Gestioná descuentos para tus clientes.</p>
            </div>
            <div class="bg-white bg-opacity-25 rounded-pill px-4 py-2">
                <span class="fw-bold fs-5"><?php echo $activos; ?></span> <small>Activos</small>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3"><i class="bi bi-plus-circle"></i> Crear Nuevo</div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Código (Ej: SUMMER25)</label>
                                <input type="text" name="codigo" class="form-control form-control-lg text-uppercase fw-bold" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">% Descuento</label>
                                    <input type="number" name="porcentaje" class="form-control" min="1" max="100" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Límite Usos</label>
                                    <input type="number" name="limite" class="form-control" value="0" placeholder="0 = Infinito">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted">Vencimiento</label>
                                <input type="date" name="vencimiento" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">GUARDAR CUPÓN</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Listado de Cupones</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Código</th>
                                    <th>Descuento</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cupones as $c): 
                                    $venc = $c['fecha_limite'];
                                    $vencido = ($venc < date('Y-m-d'));
                                    $agotado = ($c['cantidad_limite'] > 0 && $c['usos_actuales'] >= $c['cantidad_limite']);
                                    $estado = $vencido ? '<span class="badge bg-secondary">Vencido</span>' : ($agotado ? '<span class="badge bg-warning text-dark">Agotado</span>' : '<span class="badge bg-success">Activo</span>');
                                ?>
                                <tr class="<?php echo ($vencido || $agotado) ? 'opacity-50' : ''; ?>">
                                    <td class="ps-4 fw-bold text-primary"><?php echo $c['codigo']; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $c['descuento_porcentaje']; ?>% OFF</span></td>
                                    <td>
                                        <?php echo $estado; ?>
                                        <div class="small text-muted">Vence: <?php echo date('d/m/y', strtotime($venc)); ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="gestionar_cupones.php?borrar=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></a>
                                    </td>
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
</body>
</html>