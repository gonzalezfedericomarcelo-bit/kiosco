<?php
// perfil.php - FIRMA CENTRADA Y BOTÓN CERRAR ARREGLADO
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
$id_usuario = $_SESSION['usuario_id'];
$mensaje = '';

// AUTO-REPARACIÓN DE COLUMNAS (NO TOCAR)
try {
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL");
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(50) DEFAULT NULL");
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}

// 1. GUARDAR DATOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_datos'])) {
    $email = $_POST['email'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    
    if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $carpeta = "img/usuarios/";
        if (!file_exists($carpeta)) { mkdir($carpeta, 0777, true); }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "user_" . $id_usuario . "_" . time() . "." . $ext;
        
        if(move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $carpeta . $nombre_foto)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET email=?, whatsapp=?, foto_perfil=? WHERE id=?");
            $stmt->execute([$email, $whatsapp, $nombre_foto, $id_usuario]);
            $_SESSION['foto_perfil'] = $nombre_foto; 
        }
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET email=?, whatsapp=? WHERE id=?");
        $stmt->execute([$email, $whatsapp, $id_usuario]);
    }
    header("Location: perfil.php?msg=datos_ok"); exit;
}

// 2. PASSWORD
if (isset($_POST['btn_pass'])) {
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];
    $user = $conexion->query("SELECT password FROM usuarios WHERE id=$id_usuario")->fetch();

    if (password_verify($actual, $user->password)) {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $id_usuario]);
        header("Location: perfil.php?msg=pass_ok"); exit;
    } else {
        $mensaje = 'error_pass';
    }
}

$usuario = $conexion->query("SELECT * FROM usuarios WHERE id=$id_usuario")->fetch();
$tiene_firma = file_exists("img/firmas/usuario_{$id_usuario}.png") || ($usuario->id_rol <= 2 && file_exists("img/firmas/firma_admin.png"));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .avatar-preview { 
            width: 130px; height: 130px; object-fit: cover; 
            border-radius: 50%; border: 4px solid white; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: pointer;
            transition: transform 0.2s;
        }
        .avatar-preview:hover { transform: scale(1.05); }
        
        /* --- ESTILOS CORREGIDOS DEL MODAL --- */
        #modalFirma { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.95); }
        
        .canvas-container { 
            position: relative; 
            width: 90%; max-width: 800px; height: 60vh; 
            margin: 10vh auto; /* Más centrado verticalmente */
            background: white; border-radius: 15px; 
            box-shadow: 0 0 25px rgba(255,255,255,0.1); 
            overflow: hidden; 
        }
        
        canvas { 
            position: absolute; top: 0; left: 0; 
            width: 100%; height: 100%; 
            touch-action: none; cursor: crosshair; 
            z-index: 1; 
        }
        
        /* LÍNEA EN EL MEDIO EXACTO */
        .linea-guia { 
            position: absolute; 
            top: 50%; /* CENTRO VERTICAL */
            left: 5%; right: 5%; 
            border-bottom: 2px dashed #ccc; 
            pointer-events: none; z-index: 0; 
        }
        
        /* TEXTO JUSTO DEBAJO DE LA LÍNEA */
        .texto-guia { 
            position: absolute; 
            top: 52%; 
            width: 100%; text-align: center; color: #ddd; 
            pointer-events: none; font-weight: bold; 
            text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem;
            z-index: 0; 
        }
        
        /* BOTÓN CERRAR ADENTRO DEL CUADRO */
        .btn-cerrar-modal { 
            position: absolute; 
            top: 15px; right: 15px; /* ADENTRO, NO AFUERA */
            background: #dc3545; color: white; 
            border-radius: 50%; width: 40px; height: 40px; 
            border: none; font-weight: bold; font-size: 1.2rem; 
            display: flex; align-items: center; justify-content: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
            z-index: 20; cursor: pointer;
        }
        .btn-cerrar-modal:hover { background: #bb2d3b; transform: scale(1.1); }

        .botonera-firma {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: rgba(255,255,255,0.95);
            border-top: 1px solid #eee;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-6">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-primary text-white fw-bold py-3">
                        <i class="bi bi-person-vcard me-2"></i> Mis Datos
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4 pt-2">
                                <?php 
                                    $foto_db = $usuario->foto_perfil ?? null;
                                    $foto = !empty($foto_db) ? "img/usuarios/".$foto_db : "img/no-image.png"; 
                                ?>
                                <div class="position-relative d-inline-block">
                                    <img src="<?php echo $foto; ?>" id="imgPreview" class="avatar-preview mb-2" onclick="document.getElementById('inputFoto').click()">
                                    <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 shadow-sm" style="width: 35px; height: 35px;">
                                        <i class="bi bi-camera-fill text-primary" style="font-size: 1.2rem; line-height: 25px;"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">Toca para cambiar foto</div>
                                <input type="file" name="foto_perfil" id="inputFoto" class="d-none" accept="image/*" onchange="previewImage(event)">
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold small text-muted">Nombre</label>
                                <input type="text" class="form-control bg-light fw-bold" value="<?php echo htmlspecialchars($usuario->nombre_completo); ?>" readonly>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold small text-muted">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $usuario->email ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold small text-muted">WhatsApp</label>
                                    <input type="text" name="whatsapp" class="form-control" value="<?php echo $usuario->whatsapp ?? ''; ?>">
                                </div>
                            </div>

                            <button type="submit" name="btn_datos" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                GUARDAR CAMBIOS
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pen me-2"></i> Firma Digital</span>
                        <?php if($tiene_firma): ?>
                            <span class="badge bg-success rounded-pill"><i class="bi bi-check-lg"></i> Lista</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark rounded-pill">Falta</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center p-4">
                        <p class="text-muted small mb-4">Firma aquí para que aparezca en tus reportes.</p>
                        <button type="button" class="btn btn-outline-dark w-100 py-3 fw-bold border-2" onclick="abrirModalFirma()">
                            <i class="bi bi-pencil-square fs-3 d-block mb-1"></i>
                            FIRMAR EN PANTALLA
                        </button>
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        <i class="bi bi-shield-lock me-2"></i> Seguridad
                    </div>
                    <div class="card-body">
                        <?php if($mensaje == 'error_pass') echo "<div class='alert alert-danger py-2 small fw-bold mb-3'>Contraseña incorrecta.</div>"; ?>
                        <form method="POST">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-12 mb-2">
                                    <label class="small fw-bold text-muted">Contraseña Actual</label>
                                    <input type="password" name="pass_actual" class="form-control" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="small fw-bold text-muted">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="btn_pass" class="btn btn-danger w-100 fw-bold">CAMBIAR</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="modalFirma">
        <div class="canvas-container">
            <button class="btn-cerrar-modal" onclick="cerrarModalFirma()"><i class="bi bi-x-lg"></i></button>
            
            <canvas id="signature-pad"></canvas>
            
            <div class="linea-guia"></div>
            <div class="texto-guia">FIRME SOBRE LA LÍNEA</div>
            
            <div class="botonera-firma p-3 d-flex gap-2 justify-content-center">
                <button class="btn btn-outline-secondary px-4 fw-bold" onclick="signaturePad.clear()">
                    <i class="bi bi-eraser"></i> Borrar
                </button>
                <button class="btn btn-success px-5 fw-bold shadow" onclick="guardarFirma()">
                    <i class="bi bi-check-lg"></i> GUARDAR
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    
    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){ document.getElementById('imgPreview').src = reader.result; }
            reader.readAsDataURL(event.target.files[0]);
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'datos_ok') Swal.fire('Guardado', 'Datos actualizados.', 'success');
        if(urlParams.get('msg') === 'pass_ok') Swal.fire('Listo', 'Contraseña cambiada.', 'success');

        var modal = document.getElementById('modalFirma');
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)', penColor: 'rgb(0, 0, 0)' });

        function abrirModalFirma() {
            modal.style.display = 'block';
            setTimeout(resizeCanvas, 100);
        }
        function cerrarModalFirma() { modal.style.display = 'none'; }
        
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }
        window.onresize = resizeCanvas;

        function guardarFirma() {
            if (signaturePad.isEmpty()) return Swal.fire('Aviso', 'Firma vacía.', 'warning');
            
            var dataURL = signaturePad.toDataURL('image/png');
            
            fetch('acciones/guardar_firma.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'imgBase64=' + encodeURIComponent(dataURL)
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    cerrarModalFirma();
                    Swal.fire('Guardado', 'Firma registrada.', 'success').then(() => location.reload());
                } else { 
                    Swal.fire('Error', data.msg, 'error'); 
                }
            })
            .catch(err => Swal.fire('Error', 'Problema de conexión.', 'error'));
        }
    </script>
</body>
</html>