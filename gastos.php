<?php
// gastos.php - GESTIÓN DE GASTOS Y RETIROS
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN (Lógica robusta igual a proveedores.php)
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("Error Crítico: No se encuentra db.php");
}

// 2. SEGURIDAD
$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
// Si no es admin/dueño y no tiene permiso explicito, fuera.
if (!in_array('gestionar_gastos', $permisos) && $rol > 2) {
    header("Location: dashboard.php"); exit;
}

// 3. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $desc = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $cat = $_POST['categoria'];
    
    $stmt = $conexion->prepare("INSERT INTO gastos (descripcion, monto, categoria, fecha, id_usuario) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute([$desc, $monto, $cat, $_SESSION['usuario_id']]);
    
    header("Location: gastos.php?msg=ok"); exit;
}

// 4. CONSULTA
$gastos = $conexion->query("SELECT g.*, u.usuario FROM gastos g JOIN usuarios u ON g.id_usuario = u.id ORDER BY g.fecha DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { background-color: #f8f9fa; }</style>
</head>
<body>

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    else echo "<div class='alert alert-danger m-3'>Error: No se encuentra el archivo menu.php</div>";
    ?>

    <div class="container mt-4 pb-5">
        <h3 class="mb-4 text-danger fw-bold"><i class="bi bi-wallet2"></i> Gastos y Retiros</h3>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold">
                        <i class="bi bi-plus-circle"></i> Nuevo Gasto
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" required placeholder="Ej: Pago Proveedor Coca">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Monto ($)</label>
                                <input type="number" step="0.01" name="monto" class="form-control" required placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Servicios">💡 Servicios (Luz/Gas)</option>
                                    <option value="Alquiler">🏠 Alquiler</option>
                                    <option value="Proveedores">🚚 Proveedores</option>
                                    <option value="Sueldos">👥 Sueldos</option>
                                    <option value="Retiro">💸 Retiro Socio</option>
                                    <option value="Otros">📦 Otros</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-bold">REGISTRAR SALIDA</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold border-bottom">
                        <i class="bi bi-clock-history"></i> Últimos 10 Movimientos
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Detalle</th>
                                        <th>Categoría</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($gastos as $g): ?>
                                    <tr>
                                        <td><?php echo date('d/m H:i', strtotime($g->fecha)); ?></td>
                                        <td>
                                            <span class="fw-bold"><?php echo htmlspecialchars($g->descripcion); ?></span>
                                            <div class="text-muted small">User: <?php echo htmlspecialchars($g->usuario); ?></div>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo $g->categoria; ?></span></td>
                                        <td class="text-end text-danger fw-bold">-$<?php echo number_format($g->monto, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if(new URLSearchParams(window.location.search).get('msg') === 'ok') {
            Swal.fire({
                icon: 'success',
                title: 'Registrado',
                text: 'El gasto se guardó correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>
</body>
</html>