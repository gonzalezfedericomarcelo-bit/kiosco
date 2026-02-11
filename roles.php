<?php
// roles.php - GESTIÓN DE PERMISOS CON DISEÑO UNIFICADO Y FIX DE JS
session_start();

// Buscador de conexión estándar
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
// SEGURIDAD: Solo Roles 1 (SuperAdmin) y 2 (Dueño) pueden entrar a esta página
$id_user_sesion = $_SESSION['usuario_id'];
$stmtCheck = $conexion->prepare("SELECT id_rol FROM usuarios WHERE id = ?");
$stmtCheck->execute([$id_user_sesion]);
$rol_usuario_actual = $stmtCheck->fetchColumn();

if($rol_usuario_actual > 2) { header("Location: dashboard.php"); exit; }
// 1. PROCESAR GUARDADO DE PERMISOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_permisos'])) {
    // Validamos Token CSRF (Sello de seguridad)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de seguridad: Solicitud no autorizada.");
    }

    $id_rol = intval($_POST['id_rol']);
    $permisos = $_POST['permisos'] ?? [];

    // PROTECCIÓN DE JERARQUÍA: Solo el SuperAdmin (1) puede modificar al SuperAdmin
    if ($id_rol == 1 && $rol_usuario_actual != 1) {
        header("Location: roles.php?err=jerarquia"); exit;
    }

    try {
        $conexion->beginTransaction();
        $conexion->prepare("DELETE FROM rol_permisos WHERE id_rol = ?")->execute([$id_rol]);
        
        $stmtIns = $conexion->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (?, ?)");
        foreach($permisos as $p_id) { 
            $stmtIns->execute([$id_rol, intval($p_id)]); 
        }
        
        $conexion->commit();
        header("Location: roles.php?msg=guardado"); exit;
   } catch (Exception $e) { 
        $conexion->rollBack(); 
        $error = "Error: " . $e->getMessage(); 
    }
}

// 2. OBTENER DATOS (Roles, Permisos y Estadísticas)
$roles = $conexion->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
// 2. OBTENER DATOS (Roles, Permisos y Estadísticas)
$roles = $conexion->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
$permisos_all = $conexion->query("SELECT * FROM permisos ORDER BY categoria DESC, descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);
$total_usuarios = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();

// 3. MAPEAR RELACIONES ACTUALES
$mapa_permisos = [];
$relaciones = $conexion->query("SELECT id_rol, id_permiso FROM rol_permisos")->fetchAll(PDO::FETCH_ASSOC);
foreach($relaciones as $r) {
    $mapa_permisos[$r['id_rol']][] = $r['id_permiso'];
}
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    /* Estilo idéntico a Auditoría */
    .header-blue { background-color: #102A57; color: white; padding: 40px 0; border-radius: 0 0 30px 30px; position: relative; overflow: hidden; margin-bottom: 25px; }
    .bg-icon-large { position: absolute; top: 50%; right: 20px; transform: translateY(-50%) rotate(-10deg); font-size: 10rem; opacity: 0.1; color: white; pointer-events: none; }
    .stat-card { border: none; border-radius: 15px; padding: 12px 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background: white; display: flex; align-items: center; justify-content: space-between; height: 100%; }
    
    /* Estilo de Tarjetas de Roles */
    .card-rol { border: none; border-radius: 20px; transition: 0.3s; border-top: 5px solid #6c757d; }
    .card-rol:hover { transform: translateY(-8px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .border-rol-1 { border-top-color: #dc3545 !important; } /* SuperAdmin */
    .border-rol-2 { border-top-color: #0d6efd !important; } /* Dueño */
    .border-rol-3 { border-top-color: #198754 !important; } /* Empleado */

    /* Estilo de Permisos en el Modal */
    .cat-header { background: #f8f9fa; padding: 10px 15px; border-radius: 10px; color: #102A57; font-weight: bold; margin-top: 20px; border-left: 4px solid #102A57; }
    .permiso-item { cursor: pointer; border-radius: 8px; transition: 0.2s; padding: 8px; display: flex; align-items: center; gap: 10px; border: 1px solid #eee; margin-bottom: 5px; }
    .permiso-item:hover { background-color: #eef2ff; }
    /* Agregá esto en el <style> de roles.php */
.border-rol-4 { border-top-color: #fd7e14 !important; } /* Naranja - Logística */
.border-rol-5 { border-top-color: #6f42c1 !important; } /* Púrpura - Auditor */
.border-rol-6 { border-top-color: #e83e8c !important; } /* Rosa - Marketing */
.border-rol-7 { border-top-color: #20c997 !important; } /* Turquesa - Supervisor */

</style>

<div class="header-blue">
    <i class="bi bi-shield-lock bg-icon-large"></i>
    <div class="container position-relative">
        <h2 class="fw-bold mb-4">Roles y Seguridad</h2>
        <div class="row g-3">
            <div class="col-md-4"><div class="stat-card"><div><small class="text-muted fw-bold d-block text-uppercase">Roles Definidos</small><h4 class="mb-0 fw-bold text-dark"><?php echo count($roles); ?></h4></div><i class="bi bi-person-badge text-primary fs-2 opacity-50"></i></div></div>
            <div class="col-md-4"><div class="stat-card"><div><small class="text-muted fw-bold d-block text-uppercase">Permisos Totales</small><h4 class="mb-0 fw-bold text-dark"><?php echo count($permisos_all); ?></h4></div><i class="bi bi-key text-success fs-2 opacity-50"></i></div></div>
            <div class="col-md-4"><div class="stat-card"><div><small class="text-muted fw-bold d-block text-uppercase">Usuarios Activos</small><h4 class="mb-0 fw-bold text-dark"><?php echo $total_usuarios; ?></h4></div><i class="bi bi-people text-info fs-2 opacity-50"></i></div></div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if(isset($_GET['err']) && $_GET['err'] == 'jerarquia'): ?>
        <div class='alert alert-danger border-0 shadow-sm fw-bold mb-3 small'>
            <i class='bi bi-shield-slash me-2'></i> No tienes nivel suficiente para modificar este rol.
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <?php foreach($roles as $rol): 
            $cant = count($mapa_permisos[$rol['id']] ?? []);
            $clase_borde = 'border-rol-' . $rol['id'];
        ?>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 card-rol <?php echo $clase_borde; ?>">
                <div class="card-body text-center p-4">
                    <div class="mb-3 text-primary"><i class="bi bi-shield-shaded fs-1"></i></div>
                    <h4 class="fw-bold mb-1"><?php echo $rol['nombre']; ?></h4>
                    <p class="text-muted small mb-4"><?php echo $rol['descripcion']; ?></p>
                    
                    <button type="button" class="btn btn-dark btn-sm fw-bold w-100 rounded-pill py-2" onclick="abrirModal(<?php echo $rol['id']; ?>, '<?php echo $rol['nombre']; ?>')">
                        <i class="bi bi-gear-fill me-1"></i> CONFIGURAR ACCESOS
                    </button>
                    
                    <div class="mt-3">
                        <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo $cant; ?> permisos activos</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalRoles" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold">Configurando: <span id="lblRol" class="text-warning"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="formRoles">
                    <input type="hidden" name="guardar_permisos" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id_rol" id="idRolInput">
                    
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="marcarTodos(true)">Marcar Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">Ninguno</button>
                    </div>

                    <div class="row g-2">
                        <?php 
                        $cat_actual = '';
                        foreach($permisos_all as $p): 
                            if($p['categoria'] != $cat_actual):
                                $cat_actual = $p['categoria'];
                                echo '<div class="col-12 cat-header"><i class="bi bi-folder2-open me-2"></i> ' . strtoupper($cat_actual) . '</div>';
                            endif;
                        ?>
                            <div class="col-md-6">
                                <label class="permiso-item">
                                    <input class="form-check-input border-secondary" type="checkbox" name="permisos[]" value="<?php echo $p['id']; ?>" id="chk_<?php echo $p['id']; ?>">
                                    <span class="small"><?php echo $p['descripcion']; ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-success fw-bold rounded-pill px-4" onclick="document.getElementById('formRoles').submit()">
                    <i class="bi bi-save me-1"></i> GUARDAR CAMBIOS
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>

<script>
    const PERMISOS_ASIGNADOS = <?php echo json_encode($mapa_permisos); ?>;
    
    function abrirModal(idRol, nombreRol) {
        document.getElementById('lblRol').innerText = nombreRol;
        document.getElementById('idRolInput').value = idRol;
        
        // 1. Desmarcar todos primero
        document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false);

        // 2. Marcar los que tiene este rol
        const misPermisos = PERMISOS_ASIGNADOS[idRol] || [];
        misPermisos.forEach(pid => {
            let check = document.getElementById('chk_' + pid);
            if(check) check.checked = true;
        });

        // 3. Abrir modal (Instanciando bootstrap solo cuando se necesita para evitar errores)
        const modalEl = document.getElementById('modalRoles');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }

    function marcarTodos(estado) {
        document.querySelectorAll('input[name="permisos[]"]').forEach(c => c.checked = estado);
    }

    // Alerta de éxito si existe en la URL
    if(new URLSearchParams(window.location.search).get('msg') === 'guardado') {
        Swal.fire({ icon: 'success', title: '¡Actualizado!', text: 'Permisos guardados correctamente', timer: 1500, showConfirmButton: false });
    }
</script>
