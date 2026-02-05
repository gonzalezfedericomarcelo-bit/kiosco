<?php
// usuarios.php - REDISEÑADO, RESPONSIVO Y CON BOTÓN A ROLES
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// Solo Admin/Dueño
$id_user = $_SESSION['usuario_id'];
$stmtCheck = $conexion->prepare("SELECT id_rol FROM usuarios WHERE id = ?");
$stmtCheck->execute([$id_user]);
$rol_actual = $stmtCheck->fetchColumn();

if($rol_actual > 2) { header("Location: dashboard.php"); exit; }

// PROCESAR ALTA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $user = trim($_POST['usuario']);
    $pass = $_POST['password'];
    $rol = $_POST['id_rol'];

    $check = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->execute([$user]);
    
    if ($check->rowCount() > 0) {
        $error = "El usuario ya existe.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre_completo, usuario, password, id_rol, activo) VALUES (?, ?, ?, ?, 1)";
        if ($conexion->prepare($sql)->execute([$nombre, $user, $hash, $rol])) {
            header("Location: usuarios.php?msg=creado"); exit;
        } else { $error = "Error al crear usuario."; }
    }
}

// CAMBIAR ESTADO
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $st = $_GET['estado'] ? 0 : 1;
    if ($id != $id_user) {
        $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$st, $id]);
    }
    header("Location: usuarios.php"); exit;
}

$usuarios = $conexion->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.id_rol = r.id ORDER BY u.id ASC")->fetchAll();
$roles = $conexion->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; }
        .card-user { border: none; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s; background: white; }
        .card-user:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .avatar-circle { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #495057; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        /* Botón roles destacado */
        .btn-roles { background: #343a40; color: white; border: none; border-radius: 10px; padding: 12px; width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .btn-roles:hover { background: #212529; color: white; transform: scale(1.02); }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-md-6">
                <h3 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary"></i> Equipo de Trabajo</h3>
                <p class="text-muted small mb-0">Gestiona accesos y perfiles.</p>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <a href="roles.php" class="btn-roles shadow">
                        <span><i class="bi bi-shield-lock-fill me-2"></i> GESTIONAR PERMISOS DE ROLES</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger shadow-sm mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-user h-100 border-primary border-2 border-dashed bg-light" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
                        <div class="rounded-circle bg-white text-primary shadow-sm p-3 mb-3">
                            <i class="bi bi-plus-lg fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-primary">Nuevo Usuario</h5>
                        <small class="text-muted">Crear cuenta para empleado o admin</small>
                    </div>
                </div>
            </div>

            <?php foreach($usuarios as $u): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-user h-100 <?php echo $u->activo==0 ? 'opacity-75 bg-light' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3 shadow-sm">
                                    <?php echo strtoupper(substr($u->usuario, 0, 1)); ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($u->nombre_completo); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($u->usuario); ?></small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <?php if($u->id != $id_user): ?>
                                    <li><a class="dropdown-item <?php echo $u->activo ? 'text-danger' : 'text-success'; ?>" 
                                           href="usuarios.php?cambiar_estado=<?php echo $u->id; ?>&estado=<?php echo $u->activo; ?>">
                                        <i class="bi <?php echo $u->activo ? 'bi-slash-circle' : 'bi-check-circle'; ?>"></i> 
                                        <?php echo $u->activo ? 'Desactivar Cuenta' : 'Activar Cuenta'; ?>
                                    </a></li>
                                    <?php else: ?>
                                    <li><span class="dropdown-item text-muted">No puedes desactivarte</span></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span class="badge <?php echo ($u->id_rol==1)?'bg-danger':(($u->id_rol==2)?'bg-primary':'bg-info text-dark'); ?> rounded-pill px-3">
                                <?php echo $u->rol_nombre; ?>
                            </span>
                            <?php if($u->activo): ?>
                                <small class="text-success fw-bold"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> Activo</small>
                            <?php else: ?>
                                <small class="text-secondary fw-bold"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> Inactivo</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="crear_usuario" value="1">
                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Nombre Real</label>
                            <input type="text" name="nombre" class="form-control form-control-lg" required placeholder="Ej: Juan Perez">
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="fw-bold small text-muted">Usuario Login</label>
                                <input type="text" name="usuario" class="form-control" required placeholder="juan23">
                            </div>
                            <div class="col-6">
                                <label class="fw-bold small text-muted">Contraseña</label>
                                <input type="password" name="password" class="form-control" required placeholder="******">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold small text-muted">Rol / Permisos</label>
                            <select name="id_rol" class="form-select" required>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?php echo $r->id; ?>"><?php echo $r->nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2">CREAR USUARIO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if(new URLSearchParams(window.location.search).get('msg') === 'creado') {
            Swal.fire('¡Listo!', 'El usuario ha sido creado correctamente.', 'success');
        }
    </script>
</body>
</html>
