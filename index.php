<?php
// index.php - PANTALLA DE LOGIN
session_start();

// Si ya está logueado, lo mandamos directo al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (empty($usuario) || empty($password)) {
        $error = "Por favor completa todos los campos";
    } else {
        // Buscamos al usuario en la BD
        $stmt = $conexion->prepare("SELECT id, nombre_completo, password, id_rol, activo FROM usuarios WHERE usuario = :u LIMIT 1");
        $stmt->execute([':u' => $usuario]);
        $user = $stmt->fetch();

        // Verificamos contraseña encriptada
        if ($user && password_verify($password, $user->password)) {
            if ($user->activo == 0) {
                $error = "Tu usuario está desactivado. Contacta al dueño.";
            } else {
                // LOGIN EXITOSO: Guardamos datos en sesión
                $_SESSION['usuario_id'] = $user->id;
                $_SESSION['nombre'] = $user->nombre_completo;
                $_SESSION['rol'] = $user->id_rol;
                
                // Redirigir al panel principal
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Kiosco Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            height: 100vh;
            display: flex;
            align_items: center;
            justify_content: center;
        }
        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            font-weight: 700;
            color: #333;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h3 class="login-title">🛒 Kiosco Manager</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger text-center p-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ej: admin" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="******" required>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Ingresar</button>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">Sistema de Gestión V4.0</small>
        </div>
    </div>

</body>
</html>