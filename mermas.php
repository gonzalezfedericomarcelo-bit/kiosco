<?php
// mermas.php - GESTIÓN DE ROTURAS Y VENCIMIENTOS
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
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
if (!in_array('gestionar_mermas', $permisos) && $rol > 2) {
    header("Location: dashboard.php"); exit;
}

// 3. PROCESAR BAJA
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

        $stmtUpdate = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        $stmtUpdate->execute([$cant, $id_prod]);
        
        $conexion->prepare("INSERT INTO auditoria (id_usuario, accion, detalles, fecha) VALUES (?, 'MERMA', ?, NOW())")
                 ->execute([$_SESSION['usuario_id'], "Merma ID Prod: $id_prod Cant: $cant"]);

        $conexion->commit();
        header("Location: mermas.php?msg=ok"); exit;
    } catch (Exception $e) {
        $conexion->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// 4. CONSULTAS
$productos = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 ORDER BY descripcion ASC")->fetchAll();
$mermas = $conexion->query("SELECT m.*, p.descripcion, u.usuario FROM mermas m JOIN productos p ON m.id_producto = p.id JOIN usuarios u ON m.id_usuario = u.id ORDER BY m.fecha DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mermas - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        <h3 class="mb-4 text-secondary fw-bold"><i class="bi bi-trash3"></i> Mermas y Roturas</h3>

        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-box-seam"></i> Dar de Baja Producto
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Seleccionar Producto</label>
                                <select name="id_producto" id="selectProducto" class="form-select" required>
                                    <option value="">Buscar...</option>
                                    <?php foreach($productos as $p): ?>
                                        <option value="<?php echo $p->id; ?>">
                                            <?php echo htmlspecialchars($p->descripcion); ?> (Stock: <?php echo $p->stock_actual; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Cantidad</label>
                                    <input type="number" step="0.01" name="cantidad" class="form-control" required>
                                </div>
                                <div class="col-6 mb-3">
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
                                <input type="text" name="nota_adicional" class="form-control" placeholder="Detalles...">
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100 fw-bold">CONFIRMAR BAJA</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold border-bottom">
                        <i class="bi bi-list-check"></i> Historial Reciente
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Producto</th>
                                        <th>Motivo</th>
                                        <th class="text-end">Cant.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($mermas as $m): ?>
                                    <tr>
                                        <td><?php echo date('d/m', strtotime($m->fecha)); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($m->descripcion); ?>
                                            <span class="d-block text-muted" style="font-size:0.7em">x <?php echo $m->usuario; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($m->motivo); ?></td>
                                        <td class="text-end text-danger fw-bold">-<?php echo floatval($m->cantidad); ?></td>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#selectProducto').select2({ theme: 'bootstrap-5' });
            if(new URLSearchParams(window.location.search).get('msg') === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Baja Exitosa',
                    text: 'El stock ha sido descontado',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    </script>
</body>
</html>