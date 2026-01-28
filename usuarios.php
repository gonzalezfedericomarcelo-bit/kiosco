<?php
// usuarios.php - GESTIÓN DE USUARIOS Y ROLES
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) y Dueño (2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php");
    exit;
}

// 1. PROCESAR ALTA DE USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $_POST['nombre'];
    $user = $_POST['usuario'];
    $pass = $_POST['password']; // Contraseña plana
    $rol = $_POST['id_rol'];

    // Validar que el usuario no exista
    $check = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->execute([$user]);
    
    if ($check->rowCount() > 0) {
        $error = "El nombre de usuario ya existe.";
    } else {
        // Encriptar contraseña
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre_completo, usuario, password, id_rol, activo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conexion->prepare($sql);
        if ($stmt->execute([$nombre, $user, $hash, $rol])) {
            header("Location: usuarios.php?msg=creado");
            exit;
        } else {
            $error = "Error al crear usuario.";
        }
    }
}

// 2. CAMBIAR ESTADO (ACTIVAR/DESACTIVAR) - NO BORRAMOS PARA NO ROMPER VENTAS
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $estado_actual = $_GET['estado'];
    $nuevo_estado = ($estado_actual == 1) ? 0 : 1;
    
    // No permitir desactivarse a uno mismo
    if ($id != $_SESSION['usuario_id']) {
        $stmt = $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $id]);
    }
    header("Location: usuarios.php");
    exit;
}

// OBTENER LISTAS
$lista_usuarios = $conexion->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.id_rol = r.id ORDER BY u.id ASC")->fetchAll();
$lista_roles = $conexion->query("SELECT * FROM roles")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-secondary"><i class="bi bi-person-badge-fill"></i> Usuarios y Permisos</h4>
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                <i class="bi bi-plus-lg"></i> NUEVO USUARIO
            </button>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($lista_usuarios as $u): ?>
                            <tr class="<?php echo $u->activo == 0 ? 'table-secondary opacity-75' : ''; ?>">
                                <td class="fw-bold ps-3"><?php echo $u->usuario; ?></td>
                                <td><?php echo $u->nombre_completo; ?></td>
                                <td>
                                    <?php 
                                    $badge = 'bg-secondary';
                                    if($u->id_rol == 1) $badge = 'bg-danger'; // SuperAdmin
                                    if($u->id_rol == 2) $badge = 'bg-primary'; // Dueño
                                    if($u->id_rol == 3) $badge = 'bg-info text-dark'; // Empleado
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $u->rol_nombre; ?></span>
                                </td>
                                <td>
                                    <?php if($u->activo == 1): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($u->id != $_SESSION['usuario_id']): ?>
                                        <a href="usuarios.php?cambiar_estado=<?php echo $u->id; ?>&estado=<?php echo $u->activo; ?>" 
                                           class="btn btn-sm <?php echo $u->activo ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                           title="<?php echo $u->activo ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="bi <?php echo $u->activo ? 'bi-person-x-fill' : 'bi-person-check-fill'; ?>"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">Tú</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="crear_usuario" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nombre y Apellido</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Juan Vendedor">
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small">Usuario (Login)</label>
                                <input type="text" name="usuario" class="form-control" required placeholder="juan2026">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small">Contraseña</label>
                                <input type="password" name="password" class="form-control" required placeholder="******">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Asignar Rol</label>
                            <select name="id_rol" class="form-select" required>
                                <?php foreach($lista_roles as $r): ?>
                                    <option value="<?php echo $r->id; ?>"><?php echo $r->nombre; ?> - <?php echo $r->descripcion; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold">GUARDAR USUARIO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'creado') Swal.fire('Éxito', 'Usuario creado correctamente', 'success');
    </script>
</body>
</html>