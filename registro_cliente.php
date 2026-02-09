<?php
// registro_cliente.php - REGISTRO UNIFICADO
session_start();
require_once 'includes/db.php';

// OBTENER NOMBRE
$conf = $conexion->query("SELECT nombre_negocio FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$nombre_negocio = $conf['nombre_negocio'] ?? 'Mi Kiosco';

if (isset($_SESSION['cliente_id'])) { header("Location: tienda.php"); exit; }

$ref_venta = isset($_GET['ref_venta']) ? intval($_GET['ref_venta']) : (isset($_POST['ref_venta_hidden']) ? intval($_POST['ref_venta_hidden']) : 0);
$mensaje_exito = "";
$puntos_ganados = 0;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($dni) || empty($password)) {
        $error = "Faltan datos obligatorios.";
    } else {
        $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE dni = ?");
        $stmtCheck->execute([$dni]);
        
        if ($stmtCheck->rowCount() > 0) {
            $error = "Este DNI ya está registrado. <a href='login_cliente.php'>Inicia sesión aquí</a>.";
        } else {
            try {
                $conexion->beginTransaction();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("INSERT INTO clientes (nombre, dni, telefono, password, puntos_acumulados, fecha_registro) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$nombre, $dni, $telefono, $hash]);
                $nuevo_cliente_id = $conexion->lastInsertId();

                if ($ref_venta > 0) {
                    $stmtVenta = $conexion->prepare("SELECT total, id_cliente FROM ventas WHERE id = ?");
                    $stmtVenta->execute([$ref_venta]);
                    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

                    if ($venta && $venta['id_cliente'] == 1) {
                        $conexion->prepare("UPDATE ventas SET id_cliente = ? WHERE id = ?")->execute([$nuevo_cliente_id, $ref_venta]);
                        
                        $conf_pts = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
                        $ratio = ($conf_pts && $conf_pts['dinero_por_punto'] > 0) ? $conf_pts['dinero_por_punto'] : 100;
                        $puntos_ganados = floor($venta['total'] / $ratio);
                        
                        if ($puntos_ganados > 0) {
                            $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos_ganados, $nuevo_cliente_id]);
                            $desc_mov = "Puntos ticket #" . $ref_venta;
                            $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, tipo, monto, descripcion, fecha) VALUES (?, ?, 'puntos', 0, ?, NOW())")->execute([$nuevo_cliente_id, $ref_venta, $desc_mov]);
                        }
                    }
                }
                $conexion->commit();
                $_SESSION['cliente_id'] = $nuevo_cliente_id;
                $_SESSION['cliente_nombre'] = $nombre;
                $mensaje_exito = "¡Cuenta creada!";
            } catch (Exception $e) {
                $conexion->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - <?php echo htmlspecialchars($nombre_negocio); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* PALETA VIOLETA UNIFICADA */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        .header-bg {
            /* Degradado de cabecera en tonos azules/violetas */
            background: linear-gradient(to right, #667eea, #764ba2);
            height: 120px;
            width: 100%;
            position: absolute;
            top: 0; left: 0;
            border-radius: 0 0 50% 50%;
        }
        .avatar-placeholder {
            width: 80px; height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto;
            position: relative;
            top: 40px; 
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            font-size: 2.5rem; 
            color: #764ba2; /* Color icono violeta */
        }
        .form-content {
            padding: 3rem 2rem 2rem 2rem;
            margin-top: 20px;
        }
        .btn-register {
            background: #ffde00; /* AMARILLO ACENTO */
            color: #333;
            border: none;
            padding: 12px;
            border-radius: 50px;
            font-weight: 800;
            width: 100%;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn-register:hover {
            background: #ffe63b;
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            background: #fdfdfd;
            border: 1px solid #eee;
        }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2); }
        .success-box { text-align: center; padding: 40px; }
        .success-icon { font-size: 4rem; color: #28a745; animation: popIn 0.5s ease; }
        @keyframes popIn { 0% { transform: scale(0); } 80% { transform: scale(1.2); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

    <div class="register-card">
        <?php if ($mensaje_exito): ?>
            <div class="success-box">
                <div class="success-icon"><i class="bi bi-check-circle-fill"></i></div>
                <h2 class="fw-bold mt-3">¡Bienvenido/a!</h2>
                <p class="text-muted"><?php echo $mensaje_exito; ?></p>
                
                <?php if ($puntos_ganados > 0): ?>
                    <div class="alert alert-warning border-0 text-dark fw-bold animate__animated animate__pulse animate__infinite">
                        <i class="bi bi-star-fill text-warning"></i> ¡Sumaste <?php echo $puntos_ganados; ?> puntos!
                    </div>
                <?php endif; ?>
                
                <a href="tienda.php" class="btn btn-register mt-3">IR A COMPRAR</a>
            </div>
        <?php else: ?>
            
            <div class="header-bg"></div>
            <div class="text-center position-relative">
                <div class="avatar-placeholder">
                    <i class="bi bi-person-add"></i>
                </div>
            </div>

            <div class="form-content">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Crear Cuenta</h3>
                    <p class="text-muted small text-uppercase fw-bold"><?php echo htmlspecialchars($nombre_negocio); ?></p>
                    
                    <?php if ($ref_venta > 0): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-qr-code"></i> Ticket detectado: ¡Sumá tus puntos!</span>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger small py-2"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="ref_venta_hidden" value="<?php echo $ref_venta; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted ps-2">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted ps-2">DNI</label>
                            <input type="number" name="dni" class="form-control" placeholder="Sin puntos" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted ps-2">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="WhatsApp">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted ps-2">Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="********" required>
                    </div>

                    <button type="submit" class="btn btn-register shadow">
                        <?php echo ($ref_venta > 0) ? "REGISTRAR Y SUMAR PUNTOS" : "COMENZAR"; ?>
                    </button>
                </form>

                <div class="text-center mt-3 small">
                    ¿Ya tienes cuenta? <a href="login_cliente.php" class="text-dark fw-bold">Ingresar</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>