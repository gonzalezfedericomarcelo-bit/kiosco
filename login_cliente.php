<?php
// login_cliente.php - LOGIN SOLO PARA CLIENTES
session_start();
require_once 'includes/db.php';

// Si ya está logueado, lo mandamos a la tienda
if (isset($_SESSION['cliente_id'])) { header("Location: tienda.php"); exit; }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = trim($_POST['dni']);
    $pass = $_POST['password'];

    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE dni = ?");
    $stmt->execute([$dni]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente && password_verify($pass, $cliente['password'])) {
        // CREAR LA SESIÓN
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
    <title>Acceso Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { max-width: 400px; width: 100%; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="card card-login bg-white p-4">
        <h3 class="text-center fw-bold mb-4">¡Hola!</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger text-center py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">DNI</label>
                <input type="text" name="dni" class="form-control rounded-pill" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Contraseña</label>
                <input type="password" name="password" class="form-control rounded-pill" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">ENTRAR</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="registro_cliente.php" class="small text-decoration-none">Crear cuenta nueva</a>
            <br>
            <a href="tienda.php" class="small text-decoration-none text-muted">Volver a la tienda</a>
        </div>
    </div>
</body>
</html>