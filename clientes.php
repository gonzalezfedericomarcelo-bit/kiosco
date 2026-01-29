<?php
// clientes.php - ACTUALIZADO CON BOTÓN CTA. CTE.
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error: db.php no encontrado");

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); exit;
}

// 3. LOGICA GUARDAR / EDITAR / BORRAR (Mantenida intacta)
if (isset($_GET['borrar'])) {
    try {
        $conexion->prepare("DELETE FROM clientes WHERE id = ?")->execute([$_GET['borrar']]);
        header("Location: clientes.php?msg=eliminado"); exit;
    } catch (Exception $e) { /* Error silencioso o log */ }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $dni = $_POST['dni'];
    $telefono = $_POST['telefono'];
    $limite = $_POST['limite'];
    $id_edit = $_POST['id_edit'] ?? '';

    if (!empty($nombre)) {
        if ($id_edit) {
            $sql = "UPDATE clientes SET nombre=?, dni=?, telefono=?, limite_credito=? WHERE id=?";
            $conexion->prepare($sql)->execute([$nombre, $dni, $telefono, $limite, $id_edit]);
        } else {
            $sql = "INSERT INTO clientes (nombre, dni, telefono, limite_credito) VALUES (?, ?, ?, ?)";
            $conexion->prepare($sql)->execute([$nombre, $dni, $telefono, $limite]);
        }
        header("Location: clientes.php"); exit;
    }
}

// 4. LISTAR CLIENTES
$clientes = $conexion->query("SELECT * FROM clientes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow border-0 sticky-top" style="top: 90px;">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-person-plus"></i> Nuevo Cliente
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="id_edit" id="id_edit">
                            <div class="mb-2">
                                <label class="fw-bold small">Nombre Completo</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="small text-muted">DNI / Identificación</label>
                                <input type="text" name="dni" id="dni" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="small text-muted">Teléfono / WhatsApp</label>
                                <input type="text" name="telefono" id="telefono" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold small text-danger">Límite de Fiado ($)</label>
                                <input type="number" name="limite" id="limite" class="form-control" value="0">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary fw-bold" id="btn-guardar">GUARDAR CLIENTE</button>
                                <button type="button" onclick="limpiar()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people-fill text-primary"></i> Cartera de Clientes</span>
                        <span class="badge bg-secondary"><?php echo count($clientes); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Cliente</th>
                                        <th>Contacto</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($clientes) > 0): ?>
                                        <?php foreach($clientes as $c): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-uppercase"><?php echo $c['nombre']; ?></div>
                                                <small class="text-muted">DNI: <?php echo $c['dni'] ?: '--'; ?></small>
                                            </td>
                                            <td>
                                                <div class="small"><i class="bi bi-whatsapp text-success"></i> <?php echo $c['telefono'] ?: '--'; ?></div>
                                                <div class="small text-danger fw-bold" style="font-size: 0.75rem;">
                                                    Límite: $<?php echo number_format($c['limite_credito'],0,',','.'); ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="cuenta_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success border-0 me-1" title="Ver Cuenta y Cobrar">
                                                    <i class="bi bi-cash-stack"></i> Cobrar
                                                </a>

                                                <button class="btn btn-sm btn-outline-primary border-0" 
                                                    onclick="editar('<?php echo $c['id']; ?>','<?php echo $c['nombre']; ?>','<?php echo $c['dni']; ?>','<?php echo $c['telefono']; ?>','<?php echo $c['limite_credito']; ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="clientes.php?borrar=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('¿Borrar?');">
                                                    <i class="bi bi-trash3"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center py-4 text-muted">No hay clientes.</td></tr>
                                    <?php endif; ?>
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
        function editar(id,n,d,t,l){
            document.getElementById('id_edit').value = id;
            document.getElementById('nombre').value = n;
            document.getElementById('dni').value = d;
            document.getElementById('telefono').value = t;
            document.getElementById('limite').value = l;
            document.getElementById('btn-guardar').innerText = "ACTUALIZAR";
            document.getElementById('btn-guardar').classList.replace('btn-primary', 'btn-warning');
        }
        function limpiar(){
            document.querySelector('form').reset();
            document.getElementById('id_edit').value = '';
            document.getElementById('btn-guardar').innerText = "GUARDAR CLIENTE";
            document.getElementById('btn-guardar').classList.replace('btn-warning', 'btn-primary');
        }
    </script>
</body>
</html>