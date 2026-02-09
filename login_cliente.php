<?php
// login_cliente.php - LOGIN TIENDA (DISEÑO UNIFICADO AZUL/VIOLETA)
session_start();
require_once 'includes/db.php';

// OBTENER NOMBRE
$conf = $conexion->query("SELECT nombre_negocio FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$nombre_negocio = $conf['nombre_negocio'] ?? 'Mi Kiosco';

if (isset($_SESSION['cliente_id'])) { header("Location: tienda.php"); exit; }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = trim($_POST['dni']);
    $pass = $_POST['password'];

    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE dni = ?");
    $stmt->execute([$dni]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente && password_verify($pass, $cliente['password'])) {
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nombre'] = $cliente['nombre'];
        $_SESSION['cliente_puntos'] = $cliente['puntos_acumulados'];
        $_SESSION['cliente_foto'] = $cliente['foto_perfil'];
        header("Location: tienda.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar - <?php echo htmlspecialchars($nombre_negocio); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* MISMO DEGRADADO QUE EL INDEX */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            min-height: 500px;
            display: flex;
            margin: 20px;
        }
        .login-sidebar {
            /* Mantenemos la imagen que te gustó */
            background: url('https://images.unsplash.com/photo-1575224300306-1b8da36134ec?q=80&w=1000&auto=format&fit=crop') center/cover;
            width: 50%;
            position: relative;
            display: none; 
        }
        .login-sidebar::after {
            content: '';
            position: absolute; top:0; left:0; width:100%; height:100%;
            /* Filtro violeta suave sobre la foto */
            background: rgba(118, 75, 162, 0.4);
        }
        .login-sidebar-content {
            position: relative; z-index: 2; color: white;
            height: 100%; display: flex; flex-direction: column;
            justify-content: flex-end; padding: 40px;
        }
        .login-form {
            width: 100%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        @media(min-width: 768px) {
            .login-sidebar { display: block; }
            .login-form { width: 50%; }
        }
        .form-floating > .form-control { border-radius: 12px; border: 1px solid #eee; background: #f8f9fa; }
        .form-floating > .form-control:focus { border-color: #667eea; background: white; box-shadow: none; }
        
        .btn-primary-kiosco {
            /* BOTÓN AMARILLO CON TEXTO OSCURO (ALTO CONTRASTE) */
            background: #ffde00;
            color: #333;
            border: none;
            padding: 12px;
            border-radius: 50px;
            font-weight: 800;
            letter-spacing: 1px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-primary-kiosco:hover {
            transform: translateY(-2px);
            background: #ffe63b;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .logo-text { font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.5rem; color: #333; }
        .text-violet { color: #764ba2; }
    </style>
</head>
<body>

    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-sidebar">
            <div class="login-sidebar-content">
                <h2 class="fw-bold mb-1">¡Qué bueno verte!</h2>
                <p class="opacity-75">Tenemos cosas ricas esperándote.</p>
            </div>
        </div>

        <div class="login-form">
            <div class="text-center mb-4">
                <div class="logo-text mb-2 text-uppercase">
                    <i class="bi bi-shop text-violet me-2"></i><?php echo htmlspecialchars($nombre_negocio); ?>
                </div>
                <h4 class="fw-bold text-dark">Iniciar Sesión</h4>
                <p class="text-muted small">Ingresa con tu DNI para comprar</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small border-0 bg-danger text-white bg-opacity-75 mb-4">
                    <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" name="dni" placeholder="Tu DNI" required>
                    <label>Tu DNI</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    <label>Contraseña</label>
                </div>

                <button type="submit" class="btn btn-primary-kiosco w-100 mb-3">
                    ENTRAR AHORA
                </button>
            </form>

            <div class="text-center mt-3">
                <p class="small text-muted mb-1">¿Es tu primera vez?</p>
                <a href="registro_cliente.php" class="fw-bold text-decoration-none" style="color: #667eea;">
                    Crear una cuenta gratis
                </a>
                <div class="mt-3 border-top pt-3">
                    <a href="tienda.php" class="small text-secondary text-decoration-none">
                        <i class="bi bi-shop me-1"></i> Solo quiero mirar la tienda
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>