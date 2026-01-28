<?php
// roles.php - GESTIÓN VISUAL DE PERMISOS (CHECKBOXES)
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo si tiene permiso 'ver_usuarios' O es SuperAdmin
if (!isset($_SESSION['usuario_id']) || (!in_array('ver_usuarios', $_SESSION['permisos'] ?? []) && $_SESSION['rol'] != 1)) {
    header("Location: dashboard.php"); exit;
}

// 1. GUARDAR CAMBIOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_permisos'])) {
    $id_rol = $_POST['id_rol'];
    $permisos_seleccionados = $_POST['permisos'] ?? []; // Array de IDs

    try {
        $conexion->beginTransaction();
        
        // A. Borrar permisos anteriores de este rol
        $stmtDel = $conexion->prepare("DELETE FROM rol_permisos WHERE id_rol = ?");
        $stmtDel->execute([$id_rol]);

        // B. Insertar los nuevos (marcados en checkbox)
        if(!empty($permisos_seleccionados)) {
            $sql = "INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (?, ?)";
            $stmtIns = $conexion->prepare($sql);
            foreach($permisos_seleccionados as $p_id) {
                $stmtIns->execute([$id_rol, $p_id]);
            }
        }
        
        $conexion->commit();
        header("Location: roles.php?msg=guardado"); exit;
    } catch (Exception $e) {
        $conexion->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// DATOS
$roles = $conexion->query("SELECT * FROM roles")->fetchAll();
$todos_permisos = $conexion->query("SELECT * FROM permisos ORDER BY descripcion ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles y Permisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-secondary"><i class="bi bi-shield-check"></i> Administrador de Permisos</h4>
            <a href="usuarios.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-people"></i> Ir a Usuarios</a>
        </div>

        <div class="row">
            <?php foreach($roles as $rol): 
                // Contar permisos activos para mostrar lindo
                $cant = $conexion->query("SELECT COUNT(*) FROM rol_permisos WHERE id_rol = " . $rol->id)->fetchColumn();
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between">
                        <span><?php echo $rol->nombre; ?></span>
                        <span class="badge bg-light text-dark border"><?php echo $cant; ?> Accesos</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small"><?php echo $rol->descripcion; ?></p>
                        <button class="btn btn-primary w-100 fw-bold btn-sm" onclick="editarPermisos(<?php echo $rol->id; ?>, '<?php echo $rol->nombre; ?>')">
                            <i class="bi bi-check2-square"></i> CONFIGURAR PERMISOS
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalPermisos" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Editando: <span id="lblRolNombre" class="fw-bold text-warning"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="formPermisos">
                        <input type="hidden" name="guardar_permisos" value="1">
                        <input type="hidden" name="id_rol" id="inputRolID">
                        
                        <p class="small text-muted mb-3">Selecciona qué puede ver y hacer este rol:</p>
                        
                        <div class="list-group">
                            <?php foreach($todos_permisos as $p): ?>
                                <label class="list-group-item d-flex gap-3">
                                    <input class="form-check-input flex-shrink-0" type="checkbox" name="permisos[]" 
                                           value="<?php echo $p->id; ?>" id="perm_<?php echo $p->id; ?>" style="font-size: 1.2em;">
                                    <span class="pt-1 form-checked-content">
                                        <strong><?php echo $p->descripcion; ?></strong>
                                        <small class="d-block text-muted" style="font-size: 0.75rem;">Clave: <?php echo $p->clave; ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success fw-bold">GUARDAR CAMBIOS</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEl = document.getElementById('modalPermisos');
        const modal = new bootstrap.Modal(modalEl);
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'guardado') Swal.fire('Guardado', 'Permisos actualizados correctamente.', 'success');

        function editarPermisos(idRol, nombreRol) {
            document.getElementById('lblRolNombre').innerText = nombreRol;
            document.getElementById('inputRolID').value = idRol;

            // Limpiar checkboxes
            document.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = false);

            // Cargar permisos actuales vía AJAX (Pequeño script inline para simplicidad)
            fetch('acciones/get_permisos_rol.php?id=' + idRol)
                .then(r => r.json())
                .then(ids => {
                    ids.forEach(id => {
                        let chk = document.getElementById('perm_' + id);
                        if(chk) chk.checked = true;
                    });
                    modal.show();
                })
                .catch(e => Swal.fire('Error', 'No se pudieron cargar los permisos', 'error'));
        }
    </script>
</body>
</html>