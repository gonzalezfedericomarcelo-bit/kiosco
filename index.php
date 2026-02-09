<?php
// index.php - LOGIN DUEÑO (DISEÑO GLASSMORPHISM AZUL/VIOLETA)
session_start();
require_once 'includes/db.php'; // Agregado para leer el nombre

// 1. OBTENER NOMBRE DEL NEGOCIO
$conf = $conexion->query("SELECT nombre_negocio FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$nombre_negocio = $conf['nombre_negocio'] ?? 'Kiosco Manager';

// Si ya está logueado
if (isset($_SESSION['usuario_id'])) { header("Location: dashboard.php"); exit; }

$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'vacios') $error = "⚠️ Completa todos los campos";
    if ($_GET['error'] == 'inactivo') $error = "⛔ Usuario desactivado";
    if ($_GET['error'] == 'incorrecto') $error = "❌ Datos incorrectos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Admin - <?php echo htmlspecialchars($nombre_negocio); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* PALETA AZUL/VIOLETA QUE TE GUSTÓ */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
            color: white;
        }
        .form-floating { color: #333; }
        .form-control {
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
        }
        .btn-glass {
            background: #ffde00; /* AMARILLO ACENTO */
            color: #333;
            font-weight: 800;
            border: none;
            padding: 12px;
            border-radius: 50px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            background: #ffe63b;
        }
        .icon-box {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

    <div class="glass-card text-center">
        <div class="icon-box">
            <i class="bi bi-shop-window"></i>
        </div>
        <h3 class="fw-bold mb-1 text-uppercase"><?php echo htmlspecialchars($nombre_negocio); ?></h3>
        <p class="mb-4 opacity-75 small">Acceso exclusivo personal</p>
        
        <?php if($error): ?>
            <div class="alert alert-danger border-0 shadow-sm py-2 mb-3 small fw-bold rounded-3">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth_login.php">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required autofocus>
                <label for="usuario"><i class="bi bi-person-fill me-2"></i>Usuario</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                <label for="password"><i class="bi bi-lock-fill me-2"></i>Contraseña</label>
            </div>
            
            <button type="submit" class="btn btn-glass w-100 mb-3">
                INGRESAR <i class="bi bi-arrow-right-short"></i>
            </button>
        </form>
        
        <div class="mt-3 opacity-50 small">
            &copy; <?php echo date('Y'); ?> Sistema de Gestión
        </div>
    </div>

</body>
</html>