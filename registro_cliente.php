<?php
// registro_cliente.php - CORREGIDO: Session Fix + Asignación Forzada de Puntos
session_start();
require_once 'includes/db.php';

// Si ya está logueado, ir directo a la tienda
if (isset($_SESSION['cliente_id'])) { 
    header("Location: tienda.php"); 
    exit; 
}

$ref_venta = isset($_GET['ref_venta']) ? intval($_GET['ref_venta']) : 0;
$mensaje_exito = "";
$puntos_ganados = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $venta_a_reclamar = isset($_POST['ref_venta_hidden']) ? intval($_POST['ref_venta_hidden']) : 0;

    if (empty($nombre) || empty($dni) || empty($password)) {
        $error = "Faltan datos obligatorios.";
    } else {
        // Verificar si DNI ya existe
        $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE dni = ?");
        $stmtCheck->execute([$dni]);
        if ($stmtCheck->rowCount() > 0) {
            $error = "El DNI ya está registrado. Intenta iniciar sesión.";
        } else {
            try {
                $conexion->beginTransaction();

                // 1. CREAR CLIENTE (Usamos la columna 'direccion' que YA EXISTE en tu base)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // NOTA: No insertamos dirección aquí para hacerlo rápido, se edita en el perfil
                $stmt = $conexion->prepare("INSERT INTO clientes (nombre, dni, telefono, password, puntos_acumulados, fecha_registro) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$nombre, $dni, $telefono, $hash]);
                $nuevo_cliente_id = $conexion->lastInsertId();

                // 2. ASIGNAR VENTA Y PUNTOS
                if ($venta_a_reclamar > 0) {
                    $stmtVenta = $conexion->prepare("SELECT total FROM ventas WHERE id = ?");
                    $stmtVenta->execute([$venta_a_reclamar]);
                    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

                    if ($venta) {
                        // FORZAMOS la venta al nuevo cliente (Corrección para que funcione tu prueba)
                        $conexion->prepare("UPDATE ventas SET id_cliente = ? WHERE id = ?")->execute([$nuevo_cliente_id, $venta_a_reclamar]);

                        // Calcular puntos (400 pesos = 1 punto según tu configuración)
                        $conf = $conexion->query("SELECT dinero_por_punto FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
                        $ratio = ($conf && $conf['dinero_por_punto'] > 0) ? $conf['dinero_por_punto'] : 400;
                        
                        $puntos_ganados = floor($venta['total'] / $ratio);
                        
                        if ($puntos_ganados > 0) {
                            $conexion->prepare("UPDATE clientes SET puntos_acumulados = puntos_acumulados + ? WHERE id = ?")->execute([$puntos_ganados, $nuevo_cliente_id]);
                            
                            // Guardar historial
                            $desc = "Puntos recuperados Ticket #" . $venta_a_reclamar;
                            $conexion->prepare("INSERT INTO movimientos_cc (id_cliente, tipo, monto, descripcion, fecha) VALUES (?, 'haber', 0, ?, NOW())")->execute([$nuevo_cliente_id, $desc]);
                        }
                    }
                }

                $conexion->commit();
                
                // 3. INICIAR SESIÓN MANUALMENTE (Esto faltaba o fallaba)
                $_SESSION['cliente_id'] = $nuevo_cliente_id;
                $_SESSION['cliente_nombre'] = $nombre;
                $_SESSION['cliente_puntos'] = $puntos_ganados;
                
                $mensaje_exito = "¡Cuenta Creada!";

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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-registro { max-width: 400px; width: 100%; border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .header-img { background: #0d6efd; padding: 30px; text-align: center; color: white; }
    </style>
</head>
<body>

    <?php if(!empty($mensaje_exito)): ?>
        <div class="card card-registro text-center p-4">
            <h1 class="text-success mb-3"><i class="bi bi-check-circle-fill"></i></h1>
            <h3>¡Bienvenido!</h3>
            
            <?php if($puntos_ganados > 0): ?>
                <div class="alert alert-warning fw-bold mt-3">
                    <i class="bi bi-star-fill"></i> Ganaste <?php echo $puntos_ganados; ?> Puntos
                </div>
            <?php else: ?>
                <p class="text-muted">Ya podés comprar y sumar puntos.</p>
            <?php endif; ?>

            <a href="tienda.php" class="btn btn-primary w-100 rounded-pill mt-3">IR A LA TIENDA</a>
        </div>

    <?php else: ?>
        <div class="card card-registro">
            <div class="header-img">
                <h4>Crear Cuenta</h4>
                <?php if($ref_venta > 0): ?>
                    <span class="badge bg-white text-primary mt-2">Ticket #<?php echo $ref_venta; ?> detectado</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if(isset($error)): ?><div class="alert alert-danger p-2 text-center small"><?php echo $error; ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="ref_venta_hidden" value="<?php echo $ref_venta; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DNI (Usuario)</label>
                        <input type="tel" name="dni" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">WhatsApp</label>
                        <input type="tel" name="telefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold">REGISTRARME</button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="auth_login.php" class="small text-decoration-none">Ya tengo cuenta</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>