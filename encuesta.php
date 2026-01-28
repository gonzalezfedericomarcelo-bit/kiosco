<?php
// encuesta.php - FORMULARIO PÚBLICO (DISEÑO MEJORADO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN A PRUEBA DE FALLOS
$rutas_db = ['db.php', 'includes/db.php'];
$conectado = false;
foreach ($rutas_db as $ruta) {
    if (file_exists($ruta)) { require_once $ruta; $conectado = true; break; }
}

if (!$conectado) die("<div style='color:red; text-align:center; padding:20px;'>Error Crítico: No se encuentra db.php. Verifique la carpeta.</div>");

$mensaje = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nivel = $_POST['nivel'] ?? 0;
    $com = trim($_POST['comentario']);
    $nom = trim($_POST['nombre']);
    $cont = trim($_POST['contacto']);
    
    if($nivel > 0) {
        try {
            if(empty($nom)) $nom = 'Anónimo';
            
            $sql = "INSERT INTO encuestas (nivel, comentario, cliente_nombre, contacto, fecha) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nivel, $com, $nom, $cont]);
            
            $mensaje = "¡Gracias! Tu opinión fue guardada.";
            $tipo_msg = "success";
        } catch (Exception $e) {
            $mensaje = "Error DB: " . $e->getMessage();
            $tipo_msg = "danger";
        }
    } else {
        $mensaje = "Por favor selecciona una carita.";
        $tipo_msg = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta de Satisfacción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .card-encuesta { border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 480px; overflow: hidden; background: white; }
        .card-header { background: #fff; border-bottom: none; padding-top: 30px; text-align: center; }
        .emoji-container { display: flex; justify-content: space-between; padding: 20px 10px; }
        .emoji-label { font-size: 3rem; cursor: pointer; transition: 0.3s; opacity: 0.4; filter: grayscale(100%); }
        .emoji-label:hover { transform: scale(1.2); opacity: 0.8; }
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + label { opacity: 1; filter: grayscale(0%); transform: scale(1.3); }
        .form-control { background-color: #f8f9fa; border: 1px solid #eee; border-radius: 10px; padding: 12px; }
        .form-control:focus { background-color: #fff; box-shadow: 0 0 0 3px rgba(13,110,253,0.1); }
        .btn-enviar { border-radius: 12px; padding: 12px; font-weight: bold; letter-spacing: 1px; font-size: 1.1rem; }
    </style>
</head>
<body>

    <div class="card-encuesta">
        <?php if($tipo_msg == 'success'): ?>
            <div class="p-5 text-center">
                <div style="font-size: 5rem;">🎉</div>
                <h2 class="fw-bold mt-3 text-dark">¡Recibido!</h2>
                <p class="text-muted mb-4">Gracias por ayudarnos a mejorar.</p>
                <a href="encuesta.php" class="btn btn-outline-primary rounded-pill px-4">Volver</a>
            </div>
        <?php else: ?>
            <div class="card-header">
                <h3 class="fw-bold text-dark">¿Cómo te atendimos?</h3>
                <p class="text-muted small">Selecciona una opción</p>
            </div>
            
            <div class="card-body p-4">
                <?php if($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_msg; ?> text-center mb-4 border-0 shadow-sm"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="emoji-container mb-3">
                        <div><input type="radio" name="nivel" value="1" id="e1"><label for="e1" class="emoji-label">😡</label></div>
                        <div><input type="radio" name="nivel" value="2" id="e2"><label for="e2" class="emoji-label">☹️</label></div>
                        <div><input type="radio" name="nivel" value="3" id="e3"><label for="e3" class="emoji-label">😐</label></div>
                        <div><input type="radio" name="nivel" value="4" id="e4"><label for="e4" class="emoji-label">🙂</label></div>
                        <div><input type="radio" name="nivel" value="5" id="e5"><label for="e5" class="emoji-label">😍</label></div>
                    </div>

                    <div class="mb-3">
                        <textarea name="comentario" class="form-control" rows="3" placeholder="¿Algún comentario? (Opcional)"></textarea>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <input type="text" name="nombre" class="form-control" placeholder="Tu Nombre">
                        </div>
                        <div class="col-6">
                            <input type="text" name="contacto" class="form-control" placeholder="WhatsApp">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-enviar">ENVIAR OPINIÓN</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>