<?php
// roles.php - GESTOR DE PERMISOS CORREGIDO
session_start();
require_once 'includes/db.php';

// Seguridad básica
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_permisos'])) {
    $id_rol = $_POST['id_rol'];
    $permisos = $_POST['permisos'] ?? [];

    try {
        $conexion->beginTransaction();
        $conexion->prepare("DELETE FROM rol_permisos WHERE id_rol = ?")->execute([$id_rol]);
        $stmtIns = $conexion->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (?, ?)");
        foreach($permisos as $p_id) { $stmtIns->execute([$id_rol, $p_id]); }
        $conexion->commit();
        header("Location: roles.php?msg=guardado"); exit;
    } catch (Exception $e) { $conexion->rollBack(); $error = $e->getMessage(); }
}

$roles = $conexion->query("SELECT * FROM roles")->fetchAll();
$permisos_all = $conexion->query("SELECT * FROM permisos ORDER BY descripcion ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Roles y Permisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="bi bi-shield-check"></i> Roles y Permisos</h4>
            <a href="usuarios.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <div class="row g-3">
            <?php foreach($roles as $rol): 
                $cant = $conexion->query("SELECT COUNT(*) FROM rol_permisos WHERE id_rol = " . $rol->id)->fetchColumn();
            ?>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold"><?php echo $rol->nombre; ?></h5>
                        <p class="text-muted small"><?php echo $rol->descripcion; ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-light text-dark border"><?php echo $cant; ?> permisos</span>
                            <button class="btn btn-primary btn-sm fw-bold" onclick="editar(<?php echo $rol->id; ?>, '<?php echo $rol->nombre; ?>')">
                                CONFIGURAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalRoles" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Permisos: <span id="lblRol" class="text-warning"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="formRoles">
                        <input type="hidden" name="guardar_permisos" value="1">
                        <input type="hidden" name="id_rol" id="idRolInput">
                        <div class="list-group">
                            <?php foreach($permisos_all as $p): ?>
                                <label class="list-group-item d-flex gap-2">
                                    <input class="form-check-input flex-shrink-0" type="checkbox" name="permisos[]" value="<?php echo $p->id; ?>" id="chk_<?php echo $p->id; ?>">
                                    <span><?php echo $p->descripcion; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold" onclick="document.getElementById('formRoles').submit()">GUARDAR</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('modalRoles'));
        if(new URLSearchParams(window.location.search).get('msg') === 'guardado') Swal.fire('Éxito', 'Permisos actualizados', 'success');

        function editar(id, nombre) {
            document.getElementById('lblRol').innerText = nombre;
            document.getElementById('idRolInput').value = id;
            document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false);

            // CORRECCIÓN: Llamada al archivo en raíz
            fetch('get_permisos_rol.php?id=' + id)
                .then(r => r.json())
                .then(ids => {
                    ids.forEach(pid => {
                        let chk = document.getElementById('chk_' + pid);
                        if(chk) chk.checked = true;
                    });
                    modal.show();
                })
                .catch(e => Swal.fire('Error', 'No se cargaron los permisos. Verifica get_permisos_rol.php', 'error'));
        }
    </script>
</body>
</html>
