<?php
// configuracion.php - PANEL DE CONTROL MAESTRO (MARCA BLANCA)
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre_negocio'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $cuit = $_POST['cuit'];
    $color_nav = $_POST['color_barra_nav'];
    $color_btn = $_POST['color_botones'];
    $color_bg = $_POST['color_fondo'];
    
    // Checkboxs (si no están marcados, no llegan en el POST, así que asumimos 0)
    $mod_cli = isset($_POST['modulo_clientes']) ? 1 : 0;
    $mod_stk = isset($_POST['modulo_stock']) ? 1 : 0;
    $mod_rep = isset($_POST['modulo_reportes']) ? 1 : 0;
    $mod_fid = isset($_POST['modulo_fidelizacion']) ? 1 : 0;

    // LOGICA SUBIDA LOGO
    $logo_sql = ""; // Fragmento SQL si cambia el logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $directorio = "uploads/";
        if (!is_dir($directorio)) mkdir($directorio, 0777, true);
        
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "logo_" . time() . "." . $extension;
        $ruta_final = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $ruta_final)) {
            // Actualizamos también el campo logo
            $stmtLogo = $conexion->prepare("UPDATE configuracion SET logo_url = ? WHERE id = 1");
            $stmtLogo->execute([$ruta_final]);
        }
    }

    // ACTUALIZAR DATOS GENERALES
    $sql = "UPDATE configuracion SET 
            nombre_negocio=?, direccion_local=?, telefono_whatsapp=?, mensaje_ticket=?, 
            color_barra_nav=?, color_botones=?, color_fondo=?,
            modulo_clientes=?, modulo_stock=?, modulo_reportes=?, modulo_fidelizacion=?
            WHERE id=1";
            
    $stmt = $conexion->prepare($sql);
    // Nota: Reutilizo 'cuit' en 'mensaje_ticket' por ahora si no tenés columna CUIT, ajustalo si es necesario
    $stmt->execute([$nombre, $direccion, $telefono, $cuit, $color_nav, $color_btn, $color_bg, $mod_cli, $mod_stk, $mod_rep, $mod_fid]);
    
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
        /* PREVISUALIZACIÓN DE ESTILOS EN TIEMPO REAL */
        :root {
            --color-nav: <?php echo $conf['color_barra_nav'] ?? '#212529'; ?>;
            --color-btn: <?php echo $conf['color_botones'] ?? '#0d6efd'; ?>;
            --color-bg: <?php echo $conf['color_fondo'] ?? '#f8f9fa'; ?>;
        }
        body { background-color: var(--color-bg); }
        .preview-nav { background-color: var(--color-nav) !important; color: white; padding: 10px; border-radius: 5px; }
        .preview-btn { background-color: var(--color-btn) !important; color: white; border: none; }
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
                                    <label class="form-label fw-bold">Logo Actual</label><br>
                                    <?php if(!empty($conf['logo_url']) && file_exists($conf['logo_url'])): ?>
                                        <img src="<?php echo $conf['logo_url']; ?>" alt="Logo" class="img-thumbnail mb-2" style="max-height: 100px;">
                                    <?php else: ?>
                                        <div class="text-muted p-3 border rounded bg-light mb-2">Sin Logo</div>
                                    <?php endif; ?>
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
                                            <label class="form-label">WhatsApp / Tel</label>
                                            <input type="text" name="telefono" class="form-control" value="<?php echo $conf['telefono_whatsapp']; ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">CUIT / Mensaje Ticket</label>
                                            <input type="text" name="cuit" class="form-control" value="<?php echo $conf['mensaje_ticket']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3">2. Personalización Visual (Branding)</h5>
                            <div class="row g-3 mb-4 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label">Color Barra Navegación</label>
                                    <input type="color" name="color_barra_nav" class="form-control form-control-color w-100" value="<?php echo $conf['color_barra_nav'] ?? '#212529'; ?>" title="Elige color">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Color Botones Principales</label>
                                    <input type="color" name="color_botones" class="form-control form-control-color w-100" value="<?php echo $conf['color_botones'] ?? '#0d6efd'; ?>" title="Elige color">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Color Fondo Pantalla</label>
                                    <input type="color" name="color_fondo" class="form-control form-control-color w-100" value="<?php echo $conf['color_fondo'] ?? '#f8f9fa'; ?>" title="Elige color">
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="p-3 border rounded">
                                        <small class="text-muted">Previsualización:</small>
                                        <div class="preview-nav mt-2 mb-2">Barra de Navegación del Cliente</div>
                                        <button type="button" class="btn preview-btn">Botón Principal</button>
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-3">3. Módulos y Funciones</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input" type="checkbox" name="modulo_clientes" id="mod_cli" <?php echo ($conf['modulo_clientes']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_cli">👥 Gestión de Clientes / Fiado</label>
                                        <div class="small text-muted">Permite registrar clientes y cuentas corrientes.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input" type="checkbox" name="modulo_stock" id="mod_stk" <?php echo ($conf['modulo_stock']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_stk">📦 Control de Stock Avanzado</label>
                                        <div class="small text-muted">Alertas de stock bajo, vencimientos, combos.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input" type="checkbox" name="modulo_reportes" id="mod_rep" <?php echo ($conf['modulo_reportes']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_rep">📊 Reportes y Estadísticas</label>
                                        <div class="small text-muted">Gráficos de ventas y exportación a Excel.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch py-2 border rounded px-3 bg-white">
                                        <input class="form-check-input" type="checkbox" name="modulo_fidelizacion" id="mod_fid" <?php echo ($conf['modulo_fidelizacion']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="mod_fid">⭐ Club de Puntos (Fidelización)</label>
                                        <div class="small text-muted">Sumar puntos por compra y canjes.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-bold">
                                    <i class="bi bi-save"></i> GUARDAR CAMBIOS
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>