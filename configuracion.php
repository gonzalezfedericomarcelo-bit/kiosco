<?php
// configuracion.php - VERSIÓN CORREGIDA: GUARDA EL VALOR DEL PUNTO
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre_negocio'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $cuit = $_POST['cuit'];
    $mensaje = $_POST['mensaje_ticket'];
    
    $color_nav = $_POST['color_barra_nav'];
    $color_btn = $_POST['color_botones'];
    $color_bg = $_POST['color_fondo'];
    
    $color_sec = $_POST['color_secundario'];
    $dir_deg = $_POST['direccion_degradado'];
    
    // Checkboxs
    $mod_cli = isset($_POST['modulo_clientes']) ? 1 : 0;
    $mod_stk = isset($_POST['modulo_stock']) ? 1 : 0;
    $mod_rep = isset($_POST['modulo_reportes']) ? 1 : 0;
    $mod_fid = isset($_POST['modulo_fidelizacion']) ? 1 : 0;

    // CONFIGURACIONES GLOBALES (Vencimiento y Puntos)
    $dias_alerta = !empty($_POST['dias_alerta_vencimiento']) ? $_POST['dias_alerta_vencimiento'] : 30;
    
    // AQUÍ ESTABA EL ERROR: Faltaba capturar este dato
    $dinero_punto = !empty($_POST['dinero_por_punto']) ? $_POST['dinero_por_punto'] : 100;

    // LOGICA SUBIDA LOGO
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $directorio = "uploads/";
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "logo_" . time() . "." . $extension;
        $ruta_final = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $ruta_final)) {
            $stmtLogo = $conexion->prepare("UPDATE configuracion SET logo_url = ? WHERE id = 1");
            $stmtLogo->execute([$ruta_final]);
        }
    }

    // ACTUALIZAR TODO (Incluyendo dinero_por_punto)
    $sql = "UPDATE configuracion SET 
            nombre_negocio=?, direccion_local=?, telefono_whatsapp=?, 
            cuit=?, mensaje_ticket=?,
            color_barra_nav=?, color_botones=?, color_fondo=?, color_secundario=?, direccion_degradado=?,
            modulo_clientes=?, modulo_stock=?, modulo_reportes=?, modulo_fidelizacion=?,
            dias_alerta_vencimiento=?, dinero_por_punto=? 
            WHERE id=1";
            
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        $nombre, $direccion, $telefono, $cuit, $mensaje, 
        $color_nav, $color_btn, $color_bg, $color_sec, $dir_deg, 
        $mod_cli, $mod_stk, $mod_rep, $mod_fid, 
        $dias_alerta, $dinero_punto
    ]);
    
    $msg = "Configuración guardada correctamente.";
}

// OBTENER DATOS ACTUALES
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración Maestra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --color-nav: <?php echo $conf['color_barra_nav'] ?? '#212529'; ?>;
            --color-btn: <?php echo $conf['color_botones'] ?? '#0d6efd'; ?>;
            --color-bg: <?php echo $conf['color_fondo'] ?? '#f8f9fa'; ?>;
            --color-sec: <?php echo $conf['color_secundario'] ?? '#0dcaf0'; ?>;
            --deg-dir: <?php echo $conf['direccion_degradado'] ?? '135deg'; ?>;
        }
        body { background-color: var(--color-bg); }
        .preview-banner { 
            background: linear-gradient(var(--deg-dir), var(--color-btn), var(--color-sec)); 
            color: white; padding: 20px; border-radius: 10px; text-align: center; font-weight: bold;
        }
        .logo-container { background: #e9ecef; border: 1px dashed #ced4da; padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 10px; }
        .logo-preview-img { max-height: 80px; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <?php if(isset($msg)): ?>
                    <div class="alert alert-success"><?php echo $msg; ?></div>
                <?php endif; ?>

                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-gear-fill"></i> CONFIGURACIÓN DEL SISTEMA
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <h5 class="text-primary border-bottom pb-2 mb-3">1. Identidad del Negocio</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4 text-center">
                                    <label class="form-label fw-bold">Logo</label><br>
                                    <div class="logo-container">
                                        <?php if(!empty($conf['logo_url']) && file_exists($conf['logo_url'])): ?>
                                            <img src="<?php echo $conf['logo_url']; ?>" class="logo-preview-img img-fluid">
                                        <?php else: ?>
                                            <span class="text-muted small">Sin Logo</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="logo" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Nombre de Fantasía</label>
                                            <input type="text" name="nombre_negocio" class="form-control" value="<?php echo $conf['nombre_negocio']; ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Dirección</label>
                                            <input type="text" name="direccion" class="form-control" value="<?php echo $conf['direccion_local']; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">WhatsApp</label>
                                            <input type="text" name="telefono" class="form-control" value="<?php echo $conf['telefono_whatsapp']; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">CUIT / RUT</label>
                                            <input type="text" name="cuit" class="form-control" value="<?php echo $conf['cuit'] ?? ''; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Mensaje Ticket</label>
                                            <input type="text" name="mensaje_ticket" class="form-control" value="<?php echo $conf['mensaje_ticket'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3">2. Personalización Visual</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Barra Navegación</label>
                                    <input type="color" name="color_barra_nav" class="form-control form-control-color w-100" value="<?php echo $conf['color_barra_nav'] ?? '#212529'; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Fondo Pantalla</label>
                                    <input type="color" name="color_fondo" class="form-control form-control-color w-100" value="<?php echo $conf['color_fondo'] ?? '#f8f9fa'; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Color Botones</label>
                                    <input type="color" id="inputColorBtn" name="color_botones" class="form-control form-control-color w-100" value="<?php echo $conf['color_botones'] ?? '#0d6efd'; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Color Secundario</label>
                                    <input type="color" id="inputColorSec" name="color_secundario" class="form-control form-control-color w-100" value="<?php echo $conf['color_secundario'] ?? '#0dcaf0'; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Dirección Degradado</label>
                                    <select id="selectDirDeg" name="direccion_degradado" class="form-select">
                                        <option value="to right" <?php if(($conf['direccion_degradado']??'')=='to right') echo 'selected'; ?>>➡️ Horizontal</option>
                                        <option value="to left" <?php if(($conf['direccion_degradado']??'')=='to left') echo 'selected'; ?>>⬅️ Horizontal Inv</option>
                                        <option value="to bottom" <?php if(($conf['direccion_degradado']??'')=='to bottom') echo 'selected'; ?>>⬇️ Vertical</option>
                                        <option value="135deg" <?php if(($conf['direccion_degradado']??'')=='135deg') echo 'selected'; ?>>↘️ Diagonal</option>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <div id="bannerPreview" class="preview-banner shadow-sm">VISTA PREVIA DE TU BANNER WEB</div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3">3. Módulos y Funciones</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input me-3" type="checkbox" name="modulo_clientes" id="mod_cli" <?php echo ($conf['modulo_clientes']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_cli">👥 Clientes</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input me-3" type="checkbox" name="modulo_stock" id="mod_stk" <?php echo ($conf['modulo_stock']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_stk">📦 Stock</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input me-3" type="checkbox" name="modulo_reportes" id="mod_rep" <?php echo ($conf['modulo_reportes']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_rep">📊 Reportes</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input me-3" type="checkbox" name="modulo_fidelizacion" id="mod_fid" <?php echo ($conf['modulo_fidelizacion']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_fid">⭐ Fidelización</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <div class="p-3 border rounded bg-light border-primary">
                                        <label class="form-label fw-bold text-dark"><i class="bi bi-star-fill text-warning"></i> Configuración de Puntos (Club)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">Sumar 1 Punto cada</span>
                                            <span class="input-group-text bg-white fw-bold">$</span>
                                            <input type="number" name="dinero_por_punto" class="form-control fw-bold" value="<?php echo $conf['dinero_por_punto'] ?? 100; ?>">
                                        </div>
                                        <small class="text-muted">Ejemplo: Si pones 500, el cliente debe gastar $500 para ganar 1 punto.</small>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <div class="p-3 border rounded bg-light border-warning">
                                        <label class="form-label fw-bold text-dark"><i class="bi bi-calendar-range"></i> Vencimientos</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">Avisar</span>
                                            <input type="number" name="dias_alerta_vencimiento" class="form-control fw-bold" value="<?php echo $conf['dias_alerta_vencimiento'] ?? 30; ?>">
                                            <span class="input-group-text bg-white">días antes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 fw-bold py-3"><i class="bi bi-save"></i> GUARDAR CONFIGURACIÓN</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const colorBtn = document.getElementById('inputColorBtn');
        const colorSec = document.getElementById('inputColorSec');
        const dirDeg = document.getElementById('selectDirDeg');
        const banner = document.getElementById('bannerPreview');
        function updateBanner() { banner.style.background = `linear-gradient(${dirDeg.value}, ${colorBtn.value}, ${colorSec.value})`; }
        colorBtn.addEventListener('input', updateBanner); colorSec.addEventListener('input', updateBanner); dirDeg.addEventListener('change', updateBanner);
    </script>
</body>
</html>