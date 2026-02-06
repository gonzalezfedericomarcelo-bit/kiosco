<?php
// admin_revista.php - VERSIÓN FINAL (FORMULARIOS INDEPENDIENTES)
require_once 'includes/db.php';

$mensaje = '';
$tipo_mensaje = '';

// --- AUTO-CORRECCIÓN DE COLUMNAS ---
try {
    $cols = [
        "tapa_overlay DECIMAL(3,2) DEFAULT '0.4'",
        "tapa_tit_color VARCHAR(20) DEFAULT '#ffde00'",
        "tapa_sub_color VARCHAR(20) DEFAULT '#ffffff'",
        "fuente_global VARCHAR(50) DEFAULT 'Poppins'",
        "img_tapa VARCHAR(255) DEFAULT ''",
        "tapa_banner_color VARCHAR(20) DEFAULT '#ffffff'",
        "tapa_banner_opacity DECIMAL(3,2) DEFAULT '0.90'"
    ];
    $conexion->exec("CREATE TABLE IF NOT EXISTS revista_config (id INT PRIMARY KEY)");
    $conexion->exec("INSERT INTO revista_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id");
    
    foreach($cols as $col) {
        try { $conexion->exec("ALTER TABLE revista_config ADD COLUMN $col"); } catch(Exception $e){}
    }
    $conexion->exec("CREATE TABLE IF NOT EXISTS revista_paginas (id INT PRIMARY KEY AUTO_INCREMENT, nombre_referencia VARCHAR(100), posicion INT DEFAULT 5, imagen_url VARCHAR(255), boton_texto VARCHAR(50), boton_link VARCHAR(255), activa TINYINT DEFAULT 1)");
} catch(Exception $e) {}

// 1. GUARDAR CONFIGURACIÓN (PORTADA Y ESTILOS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_config') {
    $titulo = $_POST['titulo_tapa'] ?? '';
    $subtitulo = $_POST['subtitulo_tapa'] ?? '';
    
    $tapa_color = $_POST['tapa_banner_color'] ?? '#ffffff';
    $tapa_opac = $_POST['tapa_banner_opacity'] ?? '0.9';
    $tapa_overlay = $_POST['tapa_overlay'] ?? '0.4';
    $tapa_tit_color = $_POST['tapa_tit_color'] ?? '#ffde00';
    $tapa_sub_color = $_POST['tapa_sub_color'] ?? '#ffffff';
    $fuente_global = $_POST['fuente_global'] ?? 'Poppins';

    // Imágenes
    $stmt_actual = $conexion->query("SELECT img_tapa FROM revista_config WHERE id=1");
    $actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
    
    $ruta_tapa = $actual['img_tapa'] ?? '';
    if (!empty($_FILES['img_tapa']['name'])) {
        $dir = 'img/revista/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_tapa_' . basename($_FILES['img_tapa']['name']);
        if(move_uploaded_file($_FILES['img_tapa']['tmp_name'], $dir . $nombre)) $ruta_tapa = $dir . $nombre;
    }

    $sql = "UPDATE revista_config SET 
            titulo_tapa=?, subtitulo_tapa=?,
            tapa_banner_color=?, tapa_banner_opacity=?,
            img_tapa=?, tapa_overlay=?, tapa_tit_color=?, tapa_sub_color=?,
            fuente_global=?
            WHERE id=1";
    
    $stmt = $conexion->prepare($sql);
    if($stmt->execute([$titulo, $subtitulo, $tapa_color, $tapa_opac, $ruta_tapa, $tapa_overlay, $tapa_tit_color, $tapa_sub_color, $fuente_global])) {
        $mensaje = '✅ Configuración y Portada guardadas.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = '❌ Error al guardar.';
        $tipo_mensaje = 'danger';
    }
}

// 2. AGREGAR PÁGINA (ADS)
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
            $mensaje = '✅ Página agregada.'; $tipo_mensaje = 'success';
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
$revista_cfg = $conexion->query("SELECT * FROM revista_config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
if(!$revista_cfg) $revista_cfg = [];
$paginas = $conexion->query("SELECT * FROM revista_paginas ORDER BY posicion ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Revista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .preview-img { width: 100%; height: 120px; object-fit: cover; border: 2px solid #ddd; border-radius: 5px; background: #eee; }
        .card-header { font-weight: bold; text-transform: uppercase; }
        body { padding-bottom: 80px; }
    </style>
</head>
<body class="bg-light">

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container mt-4">
        <?php if($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="d-flex flex-column flex-md-row justify-content-between mb-4 gap-2">
            <h3><i class="bi bi-palette-fill"></i> Panel de Revista</h3>
            <a href="revista.php" target="_blank" class="btn btn-dark w-100 w-md-auto"><i class="bi bi-eye"></i> Ver Resultado</a>
        </div>

        <div class="row g-4">
            
            <div class="col-12 col-lg-5">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="guardar_config">
                    
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-dark text-white">Tipografía</div>
                        <div class="card-body py-2">
                            <select name="fuente_global" class="form-select form-select-sm">
                                <?php $f = $revista_cfg['fuente_global'] ?? 'Poppins'; ?>
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
                                <?php if(!empty($revista_cfg['img_tapa'])): ?>
                                    <img src="<?php echo $revista_cfg['img_tapa']; ?>?v=<?php echo time(); ?>" class="preview-img">
                                    <div class="small text-success mt-1"><i class="bi bi-check-circle"></i> Imagen cargada</div>
                                <?php else: ?>
                                    <div class="p-3 bg-light text-muted border rounded">Sin imagen actual</div>
                                <?php endif; ?>
                            </div>
                            
                            <label class="small fw-bold">Cambiar Imagen Fondo</label>
                            <input type="file" name="img_tapa" class="form-control form-control-sm mb-2">
                            
                            <label class="small fw-bold">Oscuridad (Overlay)</label>
                            <input type="range" name="tapa_overlay" class="form-range" min="0" max="0.9" step="0.1" value="<?php echo $revista_cfg['tapa_overlay'] ?? '0.4'; ?>">

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="small">Título</label>
                                    <input type="text" name="titulo_tapa" class="form-control form-control-sm" value="<?php echo $revista_cfg['titulo_tapa'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Color</label>
                                    <input type="color" name="tapa_tit_color" class="form-control form-control-color w-100" value="<?php echo $revista_cfg['tapa_tit_color'] ?? '#ffde00'; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Subtítulo</label>
                                    <input type="text" name="subtitulo_tapa" class="form-control form-control-sm" value="<?php echo $revista_cfg['subtitulo_tapa'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small">Color</label>
                                    <input type="color" name="tapa_sub_color" class="form-control form-control-color w-100" value="<?php echo $revista_cfg['tapa_sub_color'] ?? '#ffffff'; ?>">
                                </div>
                            </div>

                            <hr class="my-2">
                            <label class="small fw-bold">Fondo del Logo</label>
                            <div class="input-group input-group-sm">
                                <input type="color" name="tapa_banner_color" class="form-control form-control-color" value="<?php echo $revista_cfg['tapa_banner_color'] ?? '#ffffff'; ?>">
                                <select name="tapa_banner_opacity" class="form-select">
                                    <option value="0" <?php echo (($revista_cfg['tapa_banner_opacity']??'')=='0')?'selected':''; ?>>Invisible</option>
                                    <option value="0.9" <?php echo (($revista_cfg['tapa_banner_opacity']??'')=='0.9')?'selected':''; ?>>Visible</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 mb-4 shadow-sm">GUARDAR CONFIGURACIÓN</button>
                </form>
            </div>

            <div class="col-12 col-lg-7">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="nueva_pagina">
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white d-flex justify-content-between">
                            <span>Páginas Especiales / Ads</span>
                            <span class="badge bg-white text-danger"><?php echo count($paginas); ?></span>
                        </div>
                        <div class="card-body bg-light">
                            <div class="row g-2 mb-3 border-bottom pb-3 align-items-end">
                                
                                <div class="col-12 col-md-4">
                                    <label class="small fw-bold">Nombre Referencia</label>
                                    <input type="text" name="nombre" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="small fw-bold text-danger">Posición</label>
                                    <input type="number" name="posicion" class="form-control form-control-sm" required placeholder="0, 5...">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="small fw-bold">Imagen Publicidad</label>
                                    <input type="file" name="imagen" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-6 col-md-6">
                                    <input type="text" name="btn_txt" class="form-control form-control-sm" placeholder="Texto Botón (Opcional)">
                                </div>
                                <div class="col-6 col-md-6">
                                    <input type="text" name="btn_link" class="form-control form-control-sm" placeholder="Link Botón (Opcional)">
                                </div>
                                <div class="col-12 text-end mt-2">
                                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold w-100 w-md-auto">AGREGAR PÁGINA</button>
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
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>