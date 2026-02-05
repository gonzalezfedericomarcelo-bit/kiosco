<?php
// perfil.php - FINAL: USUARIO BLOQUEADO Y DATOS RECUPERADOS
session_start();

// Buscador de DB robusto
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id_usuario = $_SESSION['usuario_id'];
// Forzamos rol numérico por seguridad
$rol_usuario = (isset($_SESSION['rol']) && is_numeric($_SESSION['rol'])) ? $_SESSION['rol'] : 3;
$mensaje = '';

// 1. PROCESAR GUARDADO DE DATOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_datos'])) {
    // NOTA: Ya no leemos 'usuario' del POST porque no se debe editar.
    $nombre = trim($_POST['nombre']);
    $email = $_POST['email'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    
    // Lógica de Foto
    if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        // Intentamos guardar en 'uploads/' para compatibilidad
        $carpeta = "uploads/"; 
        if (!file_exists($carpeta)) { mkdir($carpeta, 0777, true); }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "user_" . $id_usuario . "_" . time() . "." . $ext;
        
        if(move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $carpeta . $nombre_foto)) {
            // Actualizamos todo MENOS el usuario
            $sql = "UPDATE usuarios SET nombre_completo=?, email=?, whatsapp=?, foto_perfil=? WHERE id=?";
            $conexion->prepare($sql)->execute([$nombre, $email, $whatsapp, $nombre_foto, $id_usuario]);
            $_SESSION['foto_perfil'] = $nombre_foto; 
        }
    } else {
        // Actualizamos todo MENOS el usuario y la foto
        $sql = "UPDATE usuarios SET nombre_completo=?, email=?, whatsapp=? WHERE id=?";
        $conexion->prepare($sql)->execute([$nombre, $email, $whatsapp, $id_usuario]);
    }
    
    // Actualizar nombre en sesión para que el dashboard se refresque
    $_SESSION['nombre'] = $nombre;
    header("Location: perfil.php?msg=datos_ok"); exit;
}

// 2. CAMBIAR CONTRASEÑA
if (isset($_POST['btn_pass'])) {
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];
    
    $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $pass_hash = $stmt->fetchColumn();

    if (password_verify($actual, $pass_hash)) {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $id_usuario]);
        header("Location: perfil.php?msg=pass_ok"); exit;
    } else {
        $mensaje = 'error_pass';
    }
}

// OBTENER DATOS DEL USUARIO (Usamos FETCH_ASSOC para asegurar compatibilidad)
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

// Si por alguna razón falla, evitamos error fatal
if (!$u) { die("Error: No se encontró el usuario en la base de datos."); }

// --- LÓGICA DE FOTO (Recuperación inteligente) ---
$foto_url = "img/no-image.png";
if (!empty($u['foto_perfil'])) {
    if (file_exists("uploads/" . $u['foto_perfil'])) {
        $foto_url = "uploads/" . $u['foto_perfil'];
    } elseif (file_exists("img/usuarios/" . $u['foto_perfil'])) {
        $foto_url = "img/usuarios/" . $u['foto_perfil'];
    }
}

// --- LÓGICA DE FIRMA (Visualizar actual) ---
$ruta_firma = "";
$tiene_firma = false;

if ($rol_usuario <= 2) { // Admin (1) o Dueño (2)
    if(file_exists("img/firmas/firma_admin.png")) { 
        $ruta_firma = "img/firmas/firma_admin.png"; 
        $tiene_firma = true; 
    }
} else { // Empleado
    if(file_exists("img/firmas/usuario_{$id_usuario}.png")) { 
        $ruta_firma = "img/firmas/usuario_{$id_usuario}.png"; 
        $tiene_firma = true; 
    }
}
// Anti-caché para que se refresque al firmar de nuevo
if($tiene_firma) $ruta_firma .= "?t=" . time();
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
            transition: transform 0.2s; background: #eee;
        }
        .avatar-preview:hover { transform: scale(1.05); }
        
        /* Modal Firma */
        #modalFirma { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.95); }
        .canvas-container { position: relative; width: 95%; max-width: 600px; height: 40vh; margin: 20vh auto; background: white; border-radius: 15px; box-shadow: 0 0 25px rgba(255,255,255,0.1); overflow: hidden; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; touch-action: none; cursor: crosshair; }
        .botonera-firma { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); border-top: 1px solid #eee; z-index: 10; padding: 15px; display: flex; justify-content: center; gap: 10px; }
        .btn-cerrar-modal { position: absolute; top: 15px; right: 15px; background: #dc3545; color: white; border-radius: 50%; width: 40px; height: 40px; border: none; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; z-index: 20; }
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
                                <div class="position-relative d-inline-block">
                                    <img src="<?php echo $foto_url; ?>" id="imgPreview" class="avatar-preview mb-2" onclick="document.getElementById('inputFoto').click()">
                                    <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 shadow-sm" style="width: 35px; height: 35px;">
                                        <i class="bi bi-camera-fill text-primary" style="font-size: 1.2rem; line-height: 25px;"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">Toca la foto para cambiarla</div>
                                <input type="file" name="foto_perfil" id="inputFoto" class="d-none" accept="image/*" onchange="previewImage(event)">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted">Nombre Completo</label>
                                    <input type="text" name="nombre" class="form-control fw-bold" 
                                           value="<?php echo htmlspecialchars($u['nombre_completo']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted">Usuario (Login)</label>
                                    <input type="text" class="form-control fw-bold bg-secondary bg-opacity-10 text-muted" 
                                           value="<?php echo htmlspecialchars($u['usuario']); ?>" readonly>
                                    <div class="form-text small" style="font-size: 0.7rem;">No se puede cambiar el usuario.</div>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-4">
                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted">WhatsApp</label>
                                    <input type="text" name="whatsapp" class="form-control" 
                                           value="<?php echo htmlspecialchars($u['whatsapp'] ?? ''); ?>">
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
                            <span class="badge bg-success rounded-pill">Configurada</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark rounded-pill">Falta</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center p-4">
                        
                        <?php if($tiene_firma): ?>
                            <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                                <label class="small text-muted fw-bold d-block mb-2 text-start">Firma actual en sistema:</label>
                                <img src="<?php echo $ruta_firma; ?>" class="img-fluid" style="max-height: 100px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="mb-3 py-4 border rounded bg-light text-muted">
                                <i class="bi bi-exclamation-circle d-block fs-3 mb-2"></i>
                                No hay firma registrada.
                            </div>
                        <?php endif; ?>

                        <button type="button" class="btn btn-outline-dark w-100 py-3 fw-bold border-2" onclick="abrirModalFirma()">
                            <i class="bi bi-pencil-square fs-4 d-block mb-1"></i>
                            <?php echo $tiene_firma ? 'ACTUALIZAR FIRMA' : 'CREAR FIRMA'; ?>
                        </button>
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        <i class="bi bi-shield-lock me-2"></i> Seguridad
                    </div>
                    <div class="card-body">
                        <?php if($mensaje == 'error_pass') echo "<div class='alert alert-danger py-2 small fw-bold mb-3'>Contraseña actual incorrecta.</div>"; ?>
                        <form method="POST">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-12 mb-2">
                                    <label class="small fw-bold text-muted">Contraseña Actual</label>
                                    <input type="password" name="pass_actual" class="form-control" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="small fw-bold text-muted">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-control" required placeholder="Mínimo 4 caracteres">
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
            <div style="position: absolute; top:50%; width:100%; border-bottom: 2px dashed #ccc; pointer-events:none;"></div>
            <div class="botonera-firma">
                <button class="btn btn-outline-secondary px-4 fw-bold" onclick="signaturePad.clear()">BORRAR</button>
                <button class="btn btn-success px-5 fw-bold shadow" onclick="guardarFirma()">GUARDAR</button>
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
        if(urlParams.get('msg') === 'datos_ok') Swal.fire('Guardado', 'Datos actualizados correctamente.', 'success');
        if(urlParams.get('msg') === 'pass_ok') Swal.fire('Listo', 'Contraseña cambiada.', 'success');

        var modal = document.getElementById('modalFirma');
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)', penColor: 'rgb(0, 0, 0)' });

        function abrirModalFirma() {
            modal.style.display = 'block';
            setTimeout(() => {
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
            }, 100);
        }
        function cerrarModalFirma() { modal.style.display = 'none'; }

        function guardarFirma() {
            if (signaturePad.isEmpty()) return Swal.fire('Atención', 'Debes firmar antes de guardar.', 'warning');
            
            var dataURL = signaturePad.toDataURL('image/png');
            
            // Intentar guardar en la raíz o en acciones/
            fetch('guardar_firma.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'imgBase64=' + encodeURIComponent(dataURL)
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    cerrarModalFirma();
                    Swal.fire('Guardado', 'Firma actualizada.', 'success').then(() => location.reload());
                } else { Swal.fire('Error', 'No se pudo guardar.', 'error'); }
            })
            .catch(() => {
                fetch('acciones/guardar_firma.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'imgBase64=' + encodeURIComponent(dataURL)
                }).then(r=>r.json()).then(d=>{
                    if(d.status==='success'){ cerrarModalFirma(); location.reload(); }
                });
            });
        }
    </script>
</body>
</html>
