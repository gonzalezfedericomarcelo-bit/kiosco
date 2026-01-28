<?php
// registro_cliente.php - PÚBLICO (NO REQUIERE LOGIN)
// Se conecta usando tu archivo db.php de la raíz
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("Error: No se encuentra db.php");
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Campos basados en tu tabla 'clientes'
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $whatsapp = trim($_POST['whatsapp']);
    $direccion = trim($_POST['direccion'] ?? '');

    // Validación básica
    if (empty($nombre) || empty($dni)) {
        $mensaje = "El Nombre y el DNI son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Verificar si ya existe (DNI es UNIQUE en tu SQL)
            $stmtCheck = $conexion->prepare("SELECT id FROM clientes WHERE dni_cuit = ?");
            $stmtCheck->execute([$dni]);
            
            if ($stmtCheck->rowCount() > 0) {
                $mensaje = "Ya estás registrado con ese DNI.";
                $tipo_mensaje = "warning";
            } else {
                // Insertamos en la tabla 'clientes' usando tus columnas exactas
                // Default: foto_perfil='default_user.png', recibir_notificaciones=1 (según tu SQL)
                $sql = "INSERT INTO clientes (nombre, dni_cuit, whatsapp, direccion, fecha_registro, foto_perfil) 
                        VALUES (?, ?, ?, ?, NOW(), 'default_user.png')";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([$nombre, $dni, $whatsapp, $direccion]);

                $mensaje = "¡Bienvenido/a al Club! Ya estás registrado.";
                $tipo_mensaje = "success";
            }
        } catch (Exception $e) {
            $mensaje = "Error al registrar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-registro { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 100%; max-width: 400px; overflow: hidden; }
        .card-header { background: white; border-bottom: none; padding-top: 30px; text-align: center; }
        .btn-registro { background: #0d6efd; border: none; padding: 12px; font-weight: bold; font-size: 1.1rem; border-radius: 10px; width: 100%; }
        .form-control { padding: 12px; border-radius: 10px; background: #f8f9fa; border: 1px solid #eee; }
        .form-control:focus { background: white; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); }
    </style>
</head>
<body>

    <div class="card-registro">
        <div class="card-header">
            <h3 class="fw-bold text-primary mb-1">¡Unite al Club!</h3>
            <p class="text-muted small">Registrate para sumar puntos y descuentos</p>
        </div>
        <div class="card-body p-4">
            
            <?php if($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> text-center shadow-sm border-0">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if($tipo_mensaje !== 'success'): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Nombre y Apellido</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Perez" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">DNI (Sin puntos)</label>
                    <input type="number" name="dni" class="form-control" placeholder="Ej: 30123456" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">WhatsApp (Opcional)</label>
                    <input type="text" name="whatsapp" class="form-control" placeholder="Ej: 11 1234 5678">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Barrio / Dirección (Opcional)</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Ej: Centro">
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-registro text-white">REGISTRARME AHORA</button>
                </div>
            </form>
            <?php else: ?>
                <div class="text-center py-4">
                    <div style="font-size: 4rem;">🎉</div>
                    <h5 class="fw-bold mt-3">¡Registro Exitoso!</h5>
                    <p class="text-muted">Ya podés dar tu DNI en caja para sumar puntos.</p>
                    <a href="registro_cliente.php" class="btn btn-outline-primary mt-3">Volver al inicio</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light text-center py-3 border-0">
            <small class="text-muted opacity-75">Sistema Kiosco</small>
        </div>
    </div>

</body>
</html>