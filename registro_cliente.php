<?php
// registro_cliente.php - INTEGRACIÓN TOTAL CON PUNTOS POR QR
session_start();
require_once 'includes/db.php';

// 1. Si el cliente ya está logueado, lo mandamos a la tienda
if (isset($_SESSION['cliente_id'])) { 
    header("Location: tienda.php"); 
    exit; 
}

// 2. Capturamos la referencia de venta del QR
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
        $error = "Faltan datos obligatorios (Nombre, DNI y Contraseña).";
    } else {
        // Verificar si el DNI ya existe
        $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE dni = ?");
        $stmtCheck->execute([$dni]);
        
        if ($stmtCheck->rowCount() > 0) {
            $error = "El DNI ya está registrado. Por favor, inicia sesión.";
        } else {
            try {
                $conexion->beginTransaction();

                // A. Crear el nuevo cliente
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("INSERT INTO clientes (nombre, dni, telefono, password, puntos_acumulados, fecha_registro) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$nombre, $dni, $telefono, $hash]);
                $nuevo_cliente_id = $conexion->lastInsertId();

                // B. LÓGICA DE PUNTOS POR TICKET (Solo una vez)
                if ($ref_venta > 0) {
                    // Buscamos la venta y verificamos que el id_cliente sea 1 (Consumidor Final)
                    // Esto garantiza que el ticket no haya sido usado antes por otro cliente registrado
                    $stmtVenta = $conexion->prepare("SELECT total, id_cliente FROM ventas WHERE id = ?");
                    $stmtVenta->execute([$ref_venta]);
                    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

                    if ($venta && $venta['id_cliente'] == 1) {
                        // 1. Asignamos la venta al nuevo cliente
                        $conexion->prepare("UPDATE ventas SET id_cliente = ? WHERE id = ?")->execute([$nuevo_cliente_id, $ref_venta]);

                        // 2. Calculamos los puntos (usando tu ratio de la config)
                        $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
                        $ratio = ($conf && $conf['dinero_por_punto'] > 0) ? $conf['dinero_por_punto'] : 100;
                        
                        $puntos_ganados = floor($venta['total'] / $ratio);
                        
                        if ($puntos_ganados > 0) {
                            // Sumamos los puntos al cliente
                            $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos_ganados, $nuevo_cliente_id]);
                            
                            // Registramos el movimiento
                            $desc_mov = "Puntos recuperados de Ticket #" . $ref_venta;
                            $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, id_venta, tipo, monto, descripcion, fecha) VALUES (?, ?, 'puntos', 0, ?, NOW())")
                                     ->execute([$nuevo_cliente_id, $ref_venta, $desc_mov]);
                        }
                    }
                }

                $conexion->commit();
                
                // C. Loguear automáticamente
                $_SESSION['cliente_id'] = $nuevo_cliente_id;
                $_SESSION['cliente_nombre'] = $nombre;
                $mensaje_exito = "¡Registro exitoso!";

            } catch (Exception $e) {
                $conexion->rollBack();
                $error = "Error en el sistema: " . $e->getMessage();
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
    <title>Registro de Cliente - Peca's Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-registro { width: 100%; max-width: 400px; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: #fff; }
        .btn-registro { background-color: #007bff; border: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="card-registro">
        <?php if ($mensaje_exito): ?>
            <div class="text-center">
                <h2 class="text-success">✔</h2>
                <h4><?php echo $mensaje_exito; ?></h4>
                <?php if ($puntos_ganados > 0): ?>
                    <p class="alert alert-info">¡Has sumado <strong><?php echo $puntos_ganados; ?></strong> puntos!</p>
                <?php endif; ?>
                <a href="tienda.php" class="btn btn-primary w-100 mt-3">Ir a la Tienda</a>
            </div>
        <?php else: ?>
            <h4 class="text-center mb-4">Crear mi Cuenta</h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger small"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="ref_venta_hidden" value="<?php echo $ref_venta; ?>">

                <div class="mb-3">
                    <label class="form-label small">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small">DNI (Será tu usuario)</label>
                    <input type="number" name="dni" class="form-control" placeholder="Sin puntos ni espacios" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Teléfono / WhatsApp</label>
                    <input type="tel" name="telefono" class="form-control" placeholder="Cód. área + número">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-registro w-100 py-2">
                    <?php echo ($ref_venta > 0) ? "REGISTRARME Y SUMAR PUNTOS" : "REGISTRARME"; ?>
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></small>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>