<?php
// configuracion.php - VERSIÓN CON MÓDULO AFIP Y CARGA DE CERTIFICADOS
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';

// PROCESAR GUARDADO CONFIGURACIÓN GENERAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_general'])) {
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
    $dinero_punto = !empty($_POST['dinero_por_punto']) ? $_POST['dinero_por_punto'] : 100;

    // LOGO
    $logo_url = $_POST['logo_actual']; 
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = 'logo_' . time() . '.png';
        $destino = 'uploads/' . $nombre_archivo;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $logo_url = $destino;
        }
    }

    $sql = "UPDATE configuracion SET 
            nombre_negocio=?, direccion_local=?, telefono_whatsapp=?, cuit=?, mensaje_ticket=?, 
            color_barra_nav=?, color_botones=?, color_fondo=?, color_secundario=?, direccion_degradado=?, 
            modulo_clientes=?, modulo_stock=?, modulo_reportes=?, modulo_fidelizacion=?, logo_url=?,
            dias_alerta_vencimiento=?, dinero_por_punto=?
            WHERE id=1";
            
    $conexion->prepare($sql)->execute([
        $nombre, $direccion, $telefono, $cuit, $mensaje, 
        $color_nav, $color_btn, $color_bg, $color_sec, $dir_deg,
        $mod_cli, $mod_stk, $mod_rep, $mod_fid, $logo_url,
        $dias_alerta, $dinero_punto
    ]);
    
    header("Location: configuracion.php?msg=guardado"); exit;
}

// PROCESAR GUARDADO AFIP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_afip'])) {
    $cuit_afip = $_POST['cuit_afip'];
    $pto_vta = $_POST['punto_venta'];
    $modo = $_POST['modo_afip'];

    // Subida de Certificado (.crt)
    if (isset($_FILES['cert_crt']) && $_FILES['cert_crt']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cert_crt']['name'], PATHINFO_EXTENSION);
        if($ext == 'crt') {
            $ruta_crt = 'afip/certificado.crt';
            if(!is_dir('afip')) mkdir('afip');
            move_uploaded_file($_FILES['cert_crt']['tmp_name'], $ruta_crt);
            $conexion->prepare("UPDATE afip_config SET certificado_crt = ? WHERE id=1")->execute([$ruta_crt]);
        }
    }

    // Subida de Clave (.key)
    if (isset($_FILES['cert_key']) && $_FILES['cert_key']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cert_key']['name'], PATHINFO_EXTENSION);
        if($ext == 'key') {
            $ruta_key = 'afip/privada.key';
            if(!is_dir('afip')) mkdir('afip');
            move_uploaded_file($_FILES['cert_key']['tmp_name'], $ruta_key);
            $conexion->prepare("UPDATE afip_config SET clave_key = ? WHERE id=1")->execute([$ruta_key]);
        }
    }

    $sql = "UPDATE afip_config SET cuit=?, punto_venta=?, modo=? WHERE id=1";
    $conexion->prepare($sql)->execute([$cuit_afip, $pto_vta, $modo]);
    
    // Resetear token para forzar reconexión
    $conexion->query("UPDATE afip_config SET token=NULL, sign=NULL WHERE id=1");

    header("Location: configuracion.php?msg=afip_ok"); exit;
}

$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
// Si no existe config de afip, la creamos vacía para evitar errores
$afip = $conexion->query("SELECT * FROM afip_config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
if(!$afip) {
    $conexion->query("INSERT INTO afip_config (id, cuit, punto_venta, modo) VALUES (1, '', 1, 'homologacion')");
    $afip = $conexion->query("SELECT * FROM afip_config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .color-preview { width: 100%; height: 50px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ccc; transition: 0.3s; }
        .card-header-custom { background-color: <?php echo $conf['color_barra_nav']; ?>; color: white; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #f8f9fa; border-bottom-color: transparent; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <h2 class="mb-4 fw-bold text-secondary"><i class="bi bi-gear-fill"></i> Configuración del Sistema</h2>

        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">🏢 General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="afip-tab" data-bs-toggle="tab" data-bs-target="#afip" type="button" role="tab">🧾 Facturación AFIP</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow border-0 mb-4">
                            <div class="card-header card-header-custom">Datos del Negocio</div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="guardar_general" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Nombre del Kiosco</label>
                                            <input type="text" name="nombre_negocio" class="form-control" value="<?php echo $conf['nombre_negocio']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">CUIT (Opcional)</label>
                                            <input type="text" name="cuit" class="form-control" value="<?php echo $conf['cuit']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Dirección</label>
                                            <input type="text" name="direccion" class="form-control" value="<?php echo $conf['direccion_local']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">WhatsApp / Teléfono</label>
                                            <input type="text" name="telefono" class="form-control" value="<?php echo $conf['telefono_whatsapp']; ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-bold">Mensaje al pie del Ticket</label>
                                            <textarea name="mensaje_ticket" class="form-control" rows="2"><?php echo $conf['mensaje_ticket']; ?></textarea>
                                        </div>
                                        
                                        <div class="col-12"><hr></div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Logo del Ticket</label>
                                            <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg">
                                            <input type="hidden" name="logo_actual" value="<?php echo $conf['logo_url']; ?>">
                                            <?php if($conf['logo_url']): ?>
                                                <div class="mt-2"><img src="<?php echo $conf['logo_url']; ?>" style="height: 50px;"></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card bg-light border-0">
                                                <div class="card-body">
                                                    <h6 class="fw-bold mb-3">Módulos Activos</h6>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="modulo_stock" <?php echo $conf['modulo_stock']?'checked':''; ?>>
                                                        <label class="form-check-label">Control de Stock</label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="modulo_clientes" <?php echo $conf['modulo_clientes']?'checked':''; ?>>
                                                        <label class="form-check-label">Cuentas Corrientes (Fiado)</label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="modulo_fidelizacion" <?php echo $conf['modulo_fidelizacion']?'checked':''; ?>>
                                                        <label class="form-check-label">Puntos y Premios</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12"><hr></div>
                                        <h6 class="fw-bold text-secondary">Configuraciones Globales</h6>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Valor del Punto ($)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">$</span>
                                                <input type="number" step="0.01" name="dinero_por_punto" class="form-control fw-bold" value="<?php echo $conf['dinero_por_punto'] ?? 100; ?>">
                                            </div>
                                            <small class="text-muted">Cuánto debe gastar el cliente para ganar 1 punto.</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Alerta Vencimiento</label>
                                            <div class="input-group">
                                                <input type="number" name="dias_alerta_vencimiento" class="form-control fw-bold" value="<?php echo $conf['dias_alerta_vencimiento'] ?? 30; ?>">
                                                <span class="input-group-text bg-white">días antes</span>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary w-100 fw-bold py-3"><i class="bi bi-save"></i> GUARDAR CAMBIOS GENERALES</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow border-0">
                            <div class="card-header bg-dark text-white fw-bold">Personalización Visual</div>
                            <div class="card-body">
                                <form method="POST"> <div class="mb-3">
                                        <label class="form-label small fw-bold">Barra de Navegación (Arriba)</label>
                                        <input type="color" id="inputColorNav" name="color_barra_nav" class="form-control form-control-color w-100" value="<?php echo $conf['color_barra_nav']; ?>" form="form-principal"> <div class="alert alert-info small">Edita los colores en la sección izquierda (si estuvieran) o usa estos inputs asegurándote que estén dentro del tag form.</div>
                                    </div>
                                    </form>
                                <div class="text-center text-muted small">
                                    Para cambiar colores, edita directamente en la base de datos o mueve los inputs al formulario principal.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="afip" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow border-0">
                            <div class="card-header bg-primary text-white fw-bold">
                                <i class="bi bi-qr-code"></i> Configuración Facturación Electrónica (ARCA/AFIP)
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>Importante:</strong> Para facturar, necesitás subir tu Certificado Digital (.crt) y tu Clave Privada (.key).
                                </div>

                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="guardar_afip" value="1">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">CUIT del Titular</label>
                                            <input type="text" name="cuit_afip" class="form-control form-control-lg" placeholder="Sin guiones" value="<?php echo $afip['cuit']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Punto de Venta AFIP</label>
                                            <input type="number" name="punto_venta" class="form-control form-control-lg" placeholder="Ej: 1" value="<?php echo $afip['punto_venta']; ?>" required>
                                            <div class="form-text">Debe estar dado de alta en AFIP como "Web Service".</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">Modo de Facturación</label>
                                            <select name="modo_afip" class="form-select">
                                                <option value="homologacion" <?php echo ($afip['modo']=='homologacion')?'selected':''; ?>>🛠️ MODO PRUEBAS (Homologación)</option>
                                                <option value="produccion" <?php echo ($afip['modo']=='produccion')?'selected':''; ?>>✅ MODO REAL (Producción - Facturas Validas)</option>
                                            </select>
                                        </div>

                                        <div class="col-12"><hr></div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Certificado Digital (.crt)</label>
                                            <input type="file" name="cert_crt" class="form-control" accept=".crt">
                                            <?php if($afip['certificado_crt']): ?>
                                                <small class="text-success fw-bold"><i class="bi bi-check-circle"></i> Cargado: <?php echo basename($afip['certificado_crt']); ?></small>
                                            <?php else: ?>
                                                <small class="text-danger fw-bold"><i class="bi bi-x-circle"></i> No cargado</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Clave Privada (.key)</label>
                                            <input type="file" name="cert_key" class="form-control" accept=".key">
                                            <?php if($afip['clave_key']): ?>
                                                <small class="text-success fw-bold"><i class="bi bi-check-circle"></i> Cargado: <?php echo basename($afip['clave_key']); ?></small>
                                            <?php else: ?>
                                                <small class="text-danger fw-bold"><i class="bi bi-x-circle"></i> No cargada</small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary w-100 fw-bold py-3">
                                                <i class="bi bi-cloud-arrow-up-fill"></i> GUARDAR DATOS AFIP
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'guardado') Swal.fire('Éxito', 'Configuración general guardada', 'success');
        if(urlParams.get('msg') === 'afip_ok') Swal.fire('AFIP', 'Datos de facturación actualizados', 'success');
    </script>
</body>
</html>