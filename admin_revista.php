<?php
// admin_revista.php - VERSIÓN FULL RESTAURADA
require_once 'includes/db.php';

$mensaje = '';
$tipo_mensaje = '';

// --- AUTO-CORRECCIÓN SILENCIOSA DE COLUMNAS FALTANTES ---
// Esto asegura que si falta una columna nueva en tu DB, se cree sola y no rompa nada.
try {
    $cols = [
        "tapa_overlay DECIMAL(3,2) DEFAULT '0.4'",
        "tapa_tit_color VARCHAR(20) DEFAULT '#ffde00'",
        "tapa_sub_color VARCHAR(20) DEFAULT '#ffffff'",
        "bienv_overlay DECIMAL(3,2) DEFAULT '0.0'",
        "bienv_tit_color VARCHAR(20) DEFAULT '#333333'",
        "bienv_txt_color VARCHAR(20) DEFAULT '#555555'",
        "fuente_global VARCHAR(50) DEFAULT 'Poppins'",
        "img_tapa VARCHAR(255) DEFAULT ''",
        "img_bienvenida VARCHAR(255) DEFAULT ''",
        "tapa_banner_color VARCHAR(20) DEFAULT '#ffffff'",
        "tapa_banner_opacity DECIMAL(3,2) DEFAULT '0.90'",
        "bienv_bg_color VARCHAR(20) DEFAULT '#ffffff'"
    ];
    // Asegurar tabla config
    $conexion->exec("CREATE TABLE IF NOT EXISTS revista_config (id INT PRIMARY KEY)");
    $conexion->exec("INSERT INTO revista_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id");
    
    // Asegurar columnas
    foreach($cols as $col) {
        try { $conexion->exec("ALTER TABLE revista_config ADD COLUMN $col"); } catch(Exception $e){}
    }
    // Asegurar tabla paginas
    $conexion->exec("CREATE TABLE IF NOT EXISTS revista_paginas (id INT PRIMARY KEY AUTO_INCREMENT, nombre_referencia VARCHAR(100), posicion INT DEFAULT 5, imagen_url VARCHAR(255), boton_texto VARCHAR(50), boton_link VARCHAR(255), activa TINYINT DEFAULT 1)");
} catch(Exception $e) {}
// ---------------------------------------------------------

// 1. GUARDAR CONFIGURACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_config') {
    $titulo = $_POST['titulo_tapa'] ?? '';
    $subtitulo = $_POST['subtitulo_tapa'] ?? '';
    $bienvenida_tit = $_POST['bienvenida_tit'] ?? '';
    $bienvenida_text = $_POST['bienvenida_text'] ?? '';
    
    // Estilos
    $tapa_color = $_POST['tapa_banner_color'] ?? '#ffffff';
    $tapa_opac = $_POST['tapa_banner_opacity'] ?? '0.9';
    $bienv_color = $_POST['bienv_bg_color'] ?? '#ffffff';
    $tapa_overlay = $_POST['tapa_overlay'] ?? '0.4';
    $tapa_tit_color = $_POST['tapa_tit_color'] ?? '#ffde00';
    $tapa_sub_color = $_POST['tapa_sub_color'] ?? '#ffffff';
    $bienv_overlay = $_POST['bienv_overlay'] ?? '0.0';
    $bienv_tit_color = $_POST['bienv_tit_color'] ?? '#333333';
    $bienv_txt_color = $_POST['bienv_txt_color'] ?? '#555555';
    $fuente_global = $_POST['fuente_global'] ?? 'Poppins';

    // Imágenes (Mantener anterior si no se sube nueva)
    $stmt_actual = $conexion->query("SELECT img_tapa, img_bienvenida FROM revista_config WHERE id=1");
    $actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
    
    $ruta_tapa = $actual['img_tapa'] ?? '';
    if (!empty($_FILES['img_tapa']['name'])) {
        $dir = 'img/revista/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_tapa_' . basename($_FILES['img_tapa']['name']);
        if(move_uploaded_file($_FILES['img_tapa']['tmp_name'], $dir . $nombre)) $ruta_tapa = $dir . $nombre;
    }

    $ruta_bienv = $actual['img_bienvenida'] ?? '';
    if (!empty($_FILES['img_bienvenida']['name'])) {
        $dir = 'img/revista/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_bienv_' . basename($_FILES['img_bienvenida']['name']);
        if(move_uploaded_file($_FILES['img_bienvenida']['tmp_name'], $dir . $nombre)) $ruta_bienv = $dir . $nombre;
    }

    $sql = "UPDATE revista_config SET 
            titulo_tapa=?, subtitulo_tapa=?, texto_bienvenida_titulo=?, texto_bienvenida_cuerpo=?,
            tapa_banner_color=?, tapa_banner_opacity=?, bienv_bg_color=?,
            img_tapa=?, img_bienvenida=?, tapa_overlay=?, tapa_tit_color=?, tapa_sub_color=?,
            bienv_overlay=?, bienv_tit_color=?, bienv_txt_color=?, fuente_global=?
            WHERE id=1";
    
    $stmt = $conexion->prepare($sql);
    if($stmt->execute([$titulo, $subtitulo, $bienvenida_tit, $bienvenida_text, $tapa_color, $tapa_opac, $bienv_color, $ruta_tapa, $ruta_bienv, $tapa_overlay, $tapa_tit_color, $tapa_sub_color, $bienv_overlay, $bienv_tit_color, $bienv_txt_color, $fuente_global])) {
        $mensaje = 'Diseño guardado correctamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al guardar.';
        $tipo_mensaje = 'danger';
    }
}

// 2. AGREGAR PÁGINA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nueva_pagina') {
    $nombre = $_POST['nombre']; $posicion = (int)$_POST['posicion']; 
    $btn_txt = $_POST['btn_txt'] ?? ''; $btn_link = $_POST['btn_link'] ?? '';
    
    if (!empty($_FILES['imagen']['name'])) {
        $dir = 'img/revista/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ruta = $dir . time() . '_ads_' . basename($_FILES['imagen']['name']);
        
        if(move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $stmt = $conexion->prepare("INSERT INTO revista_paginas (nombre_referencia, posicion, imagen_url, boton_texto, boton_link) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $posicion, $ruta, $btn_txt, $btn_link]);
            $mensaje = 'Página agregada.'; $tipo_mensaje = 'success';
        }
    }
}

// 3. BORRAR
if (isset($_GET['borrar'])) {
    $id = (int)$_GET['borrar'];
    $conexion->query("DELETE FROM revista_paginas WHERE id=$id");
    header("Location: admin_revista.php"); exit;
}

// LEER DATOS
$conf = $conexion->query("SELECT * FROM revista_config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
if(!$conf) $conf = [];
$paginas = $conexion->query("SELECT * FROM revista_paginas ORDER BY posicion ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Revista Full</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preview-img { width: 100%; height: 120px; object-fit: cover; border: 2px solid #ddd; border-radius: 5px; background: #eee; }
        .card-header { font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body class="bg-light pb-5">
    <div class="container mt-4">
        <?php if($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between mb-4">
            <h3><i class="bi bi-palette-fill"></i> Panel de Revista</h3>
            <a href="revista.php" target="_blank" class="btn btn-dark"><i class="bi bi-eye"></i> Ver Resultado</a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar_config">
            
            <div class="row">
                <div class="col-lg-5">
                    
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-dark text-white">Tipografía</div>
                        <div class="card-body py-2">
                            <select name="fuente_global" class="form-select form-select-sm">
                                <?php $f = $conf['fuente_global'] ?? 'Poppins'; ?>
                                <option value="Poppins" <?php echo ($f=='Poppins')?'selected':''; ?>>Poppins (Moderna)</option>
                                <option value="Roboto" <?php echo ($f=='Roboto')?'selected':''; ?>>Roboto (Clásica)</option>
                                <option value="Anton" <?php echo ($f=='Anton')?'selected':''; ?>>Anton (Impacto)</option>
                            </select>
                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-primary text-white">1. Portada</div>
                        <div class="card-body">
                            <div class="mb-2 text-center">
                                <?php if(!empty($conf['img_tapa'])): ?><img src="<?php echo $conf['img_tapa']; ?>" class="preview-img"><?php endif; ?>
                            </div>
                            <label class="small fw-bold">Imagen Fondo</label>
                            <input type="file" name="img_tapa" class="form-control form-control-sm mb-2">
                            
                            <label class="small fw-bold">Oscuridad (Overlay)</label>
                            <input type="range" name="tapa_overlay" class="form-range" min="0" max="0.9" step="0.1" value="<?php echo $conf['tapa_overlay'] ?? '0.4'; ?>">

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="small">Título</label>
                                    <input type="text" name="titulo_tapa" class="form-control form-control-sm" value="<?php echo $conf['titulo_tapa'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Color</label>
                                    <input type="color" name="tapa_tit_color" class="form-control form-control-color w-100" value="<?php echo $conf['tapa_tit_color'] ?? '#ffde00'; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Subtítulo</label>
                                    <input type="text" name="subtitulo_tapa" class="form-control form-control-sm" value="<?php echo $conf['subtitulo_tapa'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Color</label>
                                    <input type="color" name="tapa_sub_color" class="form-control form-control-color w-100" value="<?php echo $conf['tapa_sub_color'] ?? '#ffffff'; ?>">
                                </div>
                            </div>

                            <hr class="my-2">
                            <label class="small fw-bold">Fondo del Logo</label>
                            <div class="input-group input-group-sm">
                                <input type="color" name="tapa_banner_color" class="form-control form-control-color" value="<?php echo $conf['tapa_banner_color'] ?? '#ffffff'; ?>">
                                <select name="tapa_banner_opacity" class="form-select">
                                    <option value="0" <?php echo (($conf['tapa_banner_opacity']??'')=='0')?'selected':''; ?>>Invisible</option>
                                    <option value="0.9" <?php echo (($conf['tapa_banner_opacity']??'')=='0.9')?'selected':''; ?>>Visible</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-success text-white">2. Bienvenida</div>
                        <div class="card-body">
                            <div class="mb-2 text-center">
                                <?php if(!empty($conf['img_bienvenida'])): ?><img src="<?php echo $conf['img_bienvenida']; ?>" class="preview-img"><?php endif; ?>
                            </div>
                            <label class="small fw-bold">Imagen (Vecino/Local)</label>
                            <input type="file" name="img_bienvenida" class="form-control form-control-sm mb-2">
                            
                            <label class="small fw-bold">Oscuridad Foto</label>
                            <input type="range" name="bienv_overlay" class="form-range" min="0" max="0.9" step="0.1" value="<?php echo $conf['bienv_overlay'] ?? '0.0'; ?>">

                            <div class="row g-2">
                                <div class="col-8">
                                    <input type="text" name="bienvenida_tit" class="form-control form-control-sm" placeholder="Título" value="<?php echo $conf['texto_bienvenida_titulo'] ?? ''; ?>">
                                </div>
                                <div class="col-4">
                                    <input type="color" name="bienv_tit_color" class="form-control form-control-color w-100" value="<?php echo $conf['bienv_tit_color'] ?? '#333333'; ?>">
                                </div>
                                <div class="col-8">
                                    <textarea name="bienvenida_text" class="form-control form-control-sm" rows="2" placeholder="Mensaje"><?php echo $conf['texto_bienvenida_cuerpo'] ?? ''; ?></textarea>
                                </div>
                                <div class="col-4">
                                    <input type="color" name="bienv_txt_color" class="form-control form-control-color w-100" value="<?php echo $conf['bienv_txt_color'] ?? '#555555'; ?>">
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <label class="small fw-bold">Color Fondo Panel Texto</label>
                                <input type="color" name="bienv_bg_color" class="form-control form-control-color w-100" value="<?php echo $conf['bienv_bg_color'] ?? '#ffffff'; ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 mb-4">GUARDAR CAMBIOS</button>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white d-flex justify-content-between">
                            <span>Páginas Especiales / Ads</span>
                            <span class="badge bg-white text-danger"><?php echo count($paginas); ?></span>
                        </div>
                        <div class="card-body bg-light">
                            <div class="row g-2 mb-3 border-bottom pb-3">
                                <input type="hidden" name="accion" value="nueva_pagina">
                                <div class="col-md-4">
                                    <label class="small fw-bold">Nombre</label>
                                    <input type="text" name="nombre" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="small fw-bold text-danger">Posición</label>
                                    <input type="number" name="posicion" class="form-control form-control-sm" required placeholder="0, 5...">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold">Imagen</label>
                                    <input type="file" name="imagen" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="btn_txt" class="form-control form-control-sm" placeholder="Texto Botón (Opcional)">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="btn_link" class="form-control form-control-sm" placeholder="Link Botón (Opcional)">
                                </div>
                                <div class="col-12 text-end mt-2">
                                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">AGREGAR PÁGINA</button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-white table-hover align-middle">
                                    <thead><tr><th>Pos</th><th>Img</th><th>Ref</th><th>Acción</th></tr></thead>
                                    <tbody>
                                        <?php foreach($paginas as $p): ?>
                                        <tr>
                                            <td class="fw-bold text-danger">#<?php echo $p['posicion']; ?></td>
                                            <td><img src="<?php echo $p['imagen_url']; ?>" style="height:40px; border-radius:4px;"></td>
                                            <td><?php echo $p['nombre_referencia']; ?></td>
                                            <td><a href="?borrar=<?php echo $p['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar?');"><i class="bi bi-trash"></i></a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($paginas)): ?><tr><td colspan="4" class="text-center text-muted">Sin páginas especiales</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>