<?php
// roles.php - VERSIÓN DEFINITIVA (TODO EN UNO - SIN ARCHIVOS EXTERNOS)
session_start();
require_once 'includes/db.php';

// Seguridad
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 1. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_permisos'])) {
    $id_rol = $_POST['id_rol'];
    $permisos = $_POST['permisos'] ?? [];

    try {
        $conexion->beginTransaction();
        // Borrar viejos
        $conexion->prepare("DELETE FROM rol_permisos WHERE id_rol = ?")->execute([$id_rol]);
        
        // Insertar nuevos
        $stmtIns = $conexion->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (?, ?)");
        foreach($permisos as $p_id) { 
            $stmtIns->execute([$id_rol, $p_id]); 
        }
        
        $conexion->commit();
        header("Location: roles.php?msg=guardado"); exit;
    } catch (Exception $e) { 
        $conexion->rollBack(); 
        $error = "Error: " . $e->getMessage(); 
    }
}

// 2. OBTENER DATOS (Roles y Permisos)
$roles = $conexion->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
$permisos_all = $conexion->query("SELECT * FROM permisos ORDER BY categoria DESC, descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. MAPEAR PERMISOS ACTUALES (Para JS)
// Creamos un array PHP que tenga: [ id_rol => [1, 5, 20...], id_rol_2 => [...] ]
$mapa_permisos = [];
$relaciones = $conexion->query("SELECT id_rol, id_permiso FROM rol_permisos")->fetchAll(PDO::FETCH_ASSOC);
foreach($relaciones as $r) {
    $mapa_permisos[$r['id_rol']][] = $r['id_permiso'];
}
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
    <style>
        /* Estilos visuales para facilitar la lectura */
        .cat-header { background: #e9ecef; padding: 8px 15px; border-radius: 5px; color: #0d6efd; font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        .permiso-item { cursor: pointer; transition: 0.2s; }
        .permiso-item:hover { background-color: #f8f9fa; }
        .card-rol { transition: transform 0.2s; border-top: 4px solid gray; }
        .card-rol:hover { transform: translateY(-5px); }
        .border-rol-1 { border-top-color: #dc3545 !important; } /* Admin */
        .border-rol-2 { border-top-color: #0d6efd !important; } /* Dueño */
        .border-rol-3 { border-top-color: #198754 !important; } /* Empleado */
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="bi bi-shield-lock-fill text-primary"></i> Roles y Permisos</h4>
            <a href="usuarios.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <div class="row g-4">
            <?php foreach($roles as $rol): 
                $cant = count($mapa_permisos[$rol['id']] ?? []);
                $clase_borde = 'border-rol-' . $rol['id']; // Color según ID
            ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 card-rol <?php echo $clase_borde; ?>">
                    <div class="card-body text-center">
                        <h4 class="fw-bold mb-1"><?php echo $rol['nombre']; ?></h4>
                        <p class="text-muted small"><?php echo $rol['descripcion']; ?></p>
                        
                        <div class="d-grid mt-3">
                            <button class="btn btn-outline-dark btn-sm fw-bold" onclick="abrirModal(<?php echo $rol['id']; ?>, '<?php echo $rol['nombre']; ?>')">
                                <i class="bi bi-gear-fill me-1"></i> CONFIGURAR PERMISOS
                            </button>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-secondary rounded-pill"><?php echo $cant; ?> permisos activos</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalRoles" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Configurando: <span id="lblRol" class="text-warning fw-bold"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="formRoles">
                        <input type="hidden" name="guardar_permisos" value="1">
                        <input type="hidden" name="id_rol" id="idRolInput">
                        
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="marcarTodos(true)">Marcar Todos</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">Desmarcar</button>
                        </div>

                        <div class="row">
                            <?php 
                            $cat_actual = '';
                            foreach($permisos_all as $p): 
                                // Agrupar por Categoría
                                if($p['categoria'] != $cat_actual):
                                    $cat_actual = $p['categoria'];
                                    echo '<div class="col-12 cat-header"><i class="bi bi-folder-fill me-1"></i> ' . $cat_actual . '</div>';
                                endif;
                            ?>
                                <div class="col-md-6">
                                    <label class="list-group-item d-flex gap-2 border-0 permiso-item align-items-center">
                                        <input class="form-check-input flex-shrink-0 border-secondary" type="checkbox" name="permisos[]" value="<?php echo $p['id']; ?>" id="chk_<?php echo $p['id']; ?>">
                                        <span class="small user-select-none" style="line-height: 1.2;"><?php echo $p['descripcion']; ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold px-4" onclick="document.getElementById('formRoles').submit()">
                        <i class="bi bi-check-lg"></i> GUARDAR CAMBIOS
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Cargamos los permisos desde PHP directamente a una variable JS
        // Esto elimina la necesidad de fetch() y archivos externos.
        const PERMISOS_ASIGNADOS = <?php echo json_encode($mapa_permisos); ?>;
        
        const modal = new bootstrap.Modal(document.getElementById('modalRoles'));

        // Mensaje de éxito
        if(new URLSearchParams(window.location.search).get('msg') === 'guardado') {
            Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Permisos actualizados correctamente', timer: 1500, showConfirmButton: false });
        }

        // Función para abrir el modal y marcar los checkboxes
        function abrirModal(idRol, nombreRol) {
            document.getElementById('lblRol').innerText = nombreRol;
            document.getElementById('idRolInput').value = idRol;
            
            // 1. Desmarcar todo primero
            document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false);

            // 2. Buscar los permisos de este rol en la variable JS
            const misPermisos = PERMISOS_ASIGNADOS[idRol] || [];

            // 3. Marcar los que correspondan
            misPermisos.forEach(pid => {
                let check = document.getElementById('chk_' + pid);
                if(check) check.checked = true;
            });

            modal.show();
        }

        function marcarTodos(estado) {
            document.querySelectorAll('input[name="permisos[]"]').forEach(c => c.checked = estado);
        }
    </script>
</body>
</html>