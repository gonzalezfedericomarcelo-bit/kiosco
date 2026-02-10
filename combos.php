<?php
// combos.php - EDITOR PROFESIONAL CON IMÁGENES Y CATEGORÍAS (VERSIÓN ESTANDARIZADA)
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// --- FUNCIONES AUXILIARES ---
function procesarImagenBase64($base64, $url_texto, $actual) {
    if (!empty($base64)) {
        $data = explode(',', $base64);
        $decoded = base64_decode($data[1]);
        $nombre = 'combo_' . time() . '.png';
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        file_put_contents('uploads/' . $nombre, $decoded);
        return 'uploads/' . $nombre;
    }
    return (!empty($url_texto)) ? $url_texto : $actual;
}

// --- LOGICA BACKEND ---

// 1. CREAR COMBO
if (isset($_POST['crear_combo'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $precio_oferta = !empty($_POST['precio_oferta']) ? $_POST['precio_oferta'] : NULL;
    $codigo = !empty($_POST['codigo']) ? $_POST['codigo'] : 'COMBO-' . time();
    $cat = $_POST['id_categoria'];
    $destacado = isset($_POST['es_destacado']) ? 1 : 0;
    $es_ilimitado = isset($_POST['es_ilimitado']) ? 1 : 0;
    $f_ini = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-d');
    $f_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-d');

    $img = procesarImagenBase64($_POST['imagen_base64'], $_POST['imagen_url_texto'], 'default.jpg');

    try {
        $conexion->beginTransaction();
        // A. Insertar en tabla combos
        $stmt = $conexion->prepare("INSERT INTO combos (nombre, precio, codigo_barras, activo, fecha_inicio, fecha_fin, es_ilimitado) VALUES (?, ?, ?, 1, ?, ?, ?)");
        $stmt->execute([$nombre, $precio, $codigo, $f_ini, $f_fin, $es_ilimitado]);
        
        // B. Insertar en tabla productos (espejo)
        $sqlP = "INSERT INTO productos (descripcion, precio_venta, precio_oferta, codigo_barras, tipo, id_categoria, id_proveedor, stock_actual, activo, es_destacado_web, imagen_url) VALUES (?, ?, ?, ?, 'combo', ?, 1, 0, 1, ?, ?)";
        $conexion->prepare($sqlP)->execute([$nombre, $precio, $precio_oferta, $codigo, $cat, $destacado, $img]);
        
        $conexion->commit();
        header("Location: combos.php?msg=creado"); exit;
    } catch (Exception $e) { $conexion->rollBack(); die("Error: " . $e->getMessage()); }
}

// 2. EDITAR COMBO
if (isset($_POST['editar_combo'])) {
    $id = $_POST['id_combo'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $precio_oferta = !empty($_POST['precio_oferta']) ? $_POST['precio_oferta'] : NULL;
    $cat = $_POST['id_categoria'];
    $destacado = isset($_POST['es_destacado']) ? 1 : 0;
    $es_ilimitado = isset($_POST['es_ilimitado']) ? 1 : 0;
    $f_ini = $_POST['fecha_inicio'];
    $f_fin = $_POST['fecha_fin'];

    $img = procesarImagenBase64($_POST['imagen_base64'], $_POST['imagen_url_texto'], $_POST['imagen_actual']);

    try {
        $conexion->beginTransaction();
        // Recuperar código
        $stmtCod = $conexion->prepare("SELECT codigo_barras FROM combos WHERE id = ?");
        $stmtCod->execute([$id]);
        $cod = $stmtCod->fetchColumn();

        // A. Actualizar combos
        $conexion->prepare("UPDATE combos SET nombre=?, precio=?, fecha_inicio=?, fecha_fin=?, es_ilimitado=? WHERE id=?")
                 ->execute([$nombre, $precio, $f_ini, $f_fin, $es_ilimitado, $id]);

        // B. Actualizar productos
        $sqlP = "UPDATE productos SET descripcion=?, precio_venta=?, precio_oferta=?, id_categoria=?, es_destacado_web=?, imagen_url=? WHERE codigo_barras=?";
        $conexion->prepare($sqlP)->execute([$nombre, $precio, $precio_oferta, $cat, $destacado, $img, $cod]);

        $conexion->commit();
        header("Location: combos.php?msg=editado"); exit;
    } catch (Exception $e) { $conexion->rollBack(); die("Error: " . $e->getMessage()); }
}

// 3. ELIMINAR COMBO (Faltaba en tu código original, agrego lógica básica)
if (isset($_GET['eliminar_id'])) {
    $id_del = $_GET['eliminar_id'];
    try {
        $conexion->beginTransaction();
        $stmtC = $conexion->prepare("SELECT codigo_barras FROM combos WHERE id = ?");
        $stmtC->execute([$id_del]);
        $cod_del = $stmtC->fetchColumn();

        $conexion->prepare("DELETE FROM combos WHERE id = ?")->execute([$id_del]);
        $conexion->prepare("DELETE FROM productos WHERE codigo_barras = ?")->execute([$cod_del]);
        $conexion->commit();
        header("Location: combos.php?msg=eliminado"); exit;
    } catch (Exception $e) { $conexion->rollBack(); }
}
// 3. ELIMINAR COMBO COMPLETO
if (isset($_GET['eliminar_id'])) {
    $id_del = $_GET['eliminar_id'];
    try {
        $conexion->beginTransaction();
        
        // Obtenemos el código para borrar también el producto espejo
        $stmtC = $conexion->prepare("SELECT codigo_barras FROM combos WHERE id = ?");
        $stmtC->execute([$id_del]);
        $cod_del = $stmtC->fetchColumn();

        // Borramos items, combo y producto espejo
        $conexion->prepare("DELETE FROM combo_items WHERE id_combo = ?")->execute([$id_del]);
        $conexion->prepare("DELETE FROM combos WHERE id = ?")->execute([$id_del]);
        if($cod_del) {
            $conexion->prepare("DELETE FROM productos WHERE codigo_barras = ?")->execute([$cod_del]);
        }

        $conexion->commit();
        header("Location: combos.php?msg=eliminado"); exit;
    } catch (Exception $e) { 
        $conexion->rollBack(); 
        die("Error al eliminar: " . $e->getMessage()); 
    }
}

// 4. AGREGAR ITEMS MASIVOS AL COMBO
if (isset($_POST['agregar_item'])) {
    $cod_combo = $_POST['combo_codigo'];
    // Recibimos arrays en lugar de valores únicos
    $productos = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];

    // Necesitamos el ID del combo
    $stmtID = $conexion->prepare("SELECT id FROM combos WHERE codigo_barras = ?");
    $stmtID->execute([$cod_combo]);
    $id_combo = $stmtID->fetchColumn();

    if ($id_combo && count($productos) > 0) {
        try {
            $conexion->beginTransaction();
            $stmtAdd = $conexion->prepare("INSERT INTO combo_items (id_combo, id_producto, cantidad) VALUES (?, ?, ?)");
            
            for ($i = 0; $i < count($productos); $i++) {
                $id_prod = $productos[$i];
                $cant = $cantidades[$i];
                
                if(!empty($id_prod) && $cant > 0) {
                    $stmtAdd->execute([$id_combo, $id_prod, $cant]);
                }
            }
            $conexion->commit();
            header("Location: combos.php?msg=item_agregado"); exit;
        } catch (Exception $e) {
            $conexion->rollBack();
        }
    }
}

// 5. BORRAR ITEM DEL COMBO (Faltaba esto)
if (isset($_GET['borrar_item'])) {
    $id_item = $_GET['borrar_item'];
    $conexion->prepare("DELETE FROM combo_items WHERE id = ?")->execute([$id_item]);
    header("Location: combos.php?msg=item_borrado"); exit;
}
// --- CONSULTAS ---
$sqlCombos = "SELECT c.*, p.precio_oferta, p.imagen_url, p.id_categoria, p.es_destacado_web 
              FROM combos c 
              LEFT JOIN productos p ON c.codigo_barras = p.codigo_barras 
              WHERE c.activo=1 ORDER BY c.id DESC";
$combos = $conexion->query($sqlCombos)->fetchAll(PDO::FETCH_ASSOC);

$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll(PDO::FETCH_ASSOC);
$productos_lista = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 AND tipo != 'combo' ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

// Datos de recetas
$recetas_data = [];
foreach($combos as $c) {
    $stmtItems = $conexion->prepare("SELECT ci.id, ci.cantidad, p.descripcion, p.precio_venta FROM combo_items ci JOIN productos p ON ci.id_producto = p.id WHERE ci.id_combo = ?");
    $stmtItems->execute([$c['id']]);
    $recetas_data[$c['id']] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}

// --- CÁLCULOS PARA WIDGETS ---
$total_combos = count($combos);
$combos_oferta = 0;
$combos_destacados = 0;
foreach($combos as $c) {
    if(!empty($c['precio_oferta']) && $c['precio_oferta'] > 0) $combos_oferta++;
    if($c['es_destacado_web'] == 1) $combos_destacados++;
}
?>

<?php include 'includes/layout_header.php'; ?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* BANNER ESTANDARIZADO (40px) */
    .header-blue {
        background-color: #102A57; /* Azul Institucional */
        color: white;
        padding: 40px 0;
        margin-bottom: 30px;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
        position: relative;
        overflow: hidden;
    }
    .bg-icon-large {
        position: absolute; top: 50%; right: 20px;
        transform: translateY(-50%) rotate(-10deg);
        font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
    }
    /* Widgets */
    .stat-card {
        border: none; border-radius: 15px; padding: 15px 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
        background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

    /* Estilos específicos de Combos */
    .card-combo { border: 0; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; transition: 0.3s; background: white; }
    .card-combo:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .img-combo-box { height: 160px; width: 100%; background: #fff; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid #f0f0f0; padding: 10px; }
    .img-combo-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
    .price-tag { font-size: 1.4rem; font-weight: 800; color: #198754; }
    .old-price { text-decoration: line-through; color: #999; font-size: 0.9rem; }
    .btn-action { width: 35px; height: 35px; border-radius: 10px; border: 0; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .preview-img-modal { width: 120px; height: 120px; object-fit: contain; border: 2px dashed #ddd; padding: 5px; border-radius: 10px; background: white; }
</style>

<div class="header-blue">
    <i class="bi bi-basket2-fill bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold mb-0">Mis Packs y Combos</h2>
                <p class="opacity-75 mb-0">Gestión de ofertas y promociones</p>
            </div>
            <div>
                <button class="btn btn-light text-dark fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="bi bi-plus-lg me-2"></i> Nuevo Combo
                </button>
            </div>
        </div>

        <div class="row g-3">
            
            <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarCombos('todos')" style="cursor: pointer;">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Combos</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $total_combos; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-basket"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarCombos('oferta')" style="cursor: pointer;">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">En Oferta</h6>
                        <h2 class="mb-0 fw-bold text-danger"><?php echo $combos_oferta; ?></h2>
                    </div>
                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-percent"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarCombos('destacado')" style="cursor: pointer;">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Destacados Web</h6>
                        <h2 class="mb-0 fw-bold text-warning"><?php echo $combos_destacados; ?></h2>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    
    <div class="row g-3">
        <?php foreach($combos as $c): 
            $items = $recetas_data[$c['id']];
            $jsonC = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
        ?>
                <div class="col-12 col-md-6 col-lg-4 item-combo" data-oferta="<?php echo ($c['precio_oferta'] > 0) ? '1' : '0'; ?>" data-destacado="<?php echo ($c['es_destacado_web'] == 1) ? '1' : '0'; ?>">
                <div class="card-combo h-100 d-flex flex-column">
                <div class="img-combo-box">
                    <img src="<?php echo $c['imagen_url'] ?: 'img/no-image.png'; ?>" loading="lazy">
                    <?php if($c['es_destacado_web']): ?>
                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 shadow-sm"><i class="bi bi-star-fill"></i></span>
                    <?php endif; ?>
                </div>
                
                <div class="p-3 flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold m-0 text-truncate" style="max-width: 180px;"><?php echo $c['nombre']; ?></h5>
                            <small class="text-muted font-monospace"><?php echo $c['codigo_barras']; ?></small>
                        </div>
                        <div class="text-end">
                            <?php if($c['precio_oferta']): ?>
                                <div class="old-price">$<?php echo number_format($c['precio'], 0); ?></div>
                                <div class="price-tag text-danger">$<?php echo number_format($c['precio_oferta'], 0); ?></div>
                            <?php else: ?>
                                <div class="price-tag">$<?php echo number_format($c['precio'], 0); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <?php if($c['es_ilimitado']): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">ILIMITADO</span>
                        <?php else: ?>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill">Vigente: <?php echo date('d/m', strtotime($c['fecha_inicio'])); ?> al <?php echo date('d/m', strtotime($c['fecha_fin'])); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="bg-light p-2 rounded small border">
                        <b class="d-block mb-1 border-bottom pb-1 text-muted">Contenido:</b>
                        <?php if(empty($items)): ?>
                            <span class="text-muted fst-italic">Vacío. Agrega productos.</span>
                        <?php else: ?>
                            <?php foreach($items as $i): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><span class="fw-bold"><?php echo $i['cantidad']; ?>x</span> <?php echo $i['descripcion']; ?></span>
                                    <a href="combos.php?borrar_item=<?php echo $i['id']; ?>" class="text-danger" title="Quitar item"><i class="bi bi-x-circle-fill"></i></a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-3 bg-white d-flex justify-content-end gap-2 border-top">
                    <button class="btn-action bg-warning-subtle text-warning-emphasis" onclick='abrirEditar(<?php echo $jsonC; ?>)' title="Editar"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action bg-success-subtle text-success-emphasis" onclick='abrirAgregar(<?php echo $c['id']; ?>, "<?php echo $c['nombre']; ?>", "<?php echo $c['codigo_barras']; ?>")' title="Items"><i class="bi bi-plus-lg"></i></button>
                    <button class="btn-action bg-danger-subtle text-danger" onclick="borrarPack(<?php echo $c['id']; ?>)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="crear_combo" value="1">
            <input type="hidden" name="imagen_base64" id="c_base64">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Nuevo Combo / Oferta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 row">
                <div class="col-md-4 text-center border-end">
                    <label class="fw-bold d-block mb-2">Imagen</label>
                    <img src="img/no-image.png" id="c_preview" class="preview-img-modal mb-2">
                    <input type="file" class="form-control form-control-sm" onchange="prepararCrop(this, 'c')" accept="image/*">
                    <input type="text" name="imagen_url_texto" class="form-control form-control-sm mt-2" placeholder="O pegar URL HTTPS">
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-12"><label class="fw-bold">Nombre del Combo</label><input type="text" name="nombre" class="form-control" required></div>
                        <div class="col-6"><label class="fw-bold">Precio Venta ($)</label><input type="number" name="precio" class="form-control" required></div>
                        <div class="col-6"><label class="fw-bold text-danger">Precio Oferta ($)</label><input type="number" name="precio_oferta" class="form-control border-danger"></div>
                        <div class="col-6">
                            <label class="fw-bold">Categoría</label>
                            <select name="id_categoria" class="form-select" required>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="fw-bold">Código Barras</label><input type="text" name="codigo" class="form-control" placeholder="Opcional"></div>
                        <div class="col-12 bg-light p-3 rounded">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="es_ilimitado" id="c_ilim" checked onchange="toggleDates('c')">
                                <label class="form-check-label fw-bold">Disponibilidad Ilimitada</label>
                            </div>
                            <div class="row g-2" id="c_dates" style="display:none">
                                <div class="col-6"><small>Desde</small><input type="date" name="fecha_inicio" class="form-control"></div>
                                <div class="col-6"><small>Hasta</small><input type="date" name="fecha_fin" class="form-control"></div>
                            </div>
                            <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="es_destacado" value="1"><label class="form-check-label small">Destacar en Tienda Web</label></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary w-100 fw-bold py-3">GUARDAR COMBO</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="editar_combo" value="1">
            <input type="hidden" name="id_combo" id="e_id">
            <input type="hidden" name="imagen_actual" id="e_actual">
            <input type="hidden" name="imagen_base64" id="e_base64">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold">Editar Configuración de Combo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 row">
                <div class="col-md-4 text-center border-end">
                    <label class="fw-bold d-block mb-2">Imagen</label>
                    <img src="" id="e_preview" class="preview-img-modal mb-2">
                    <input type="file" class="form-control form-control-sm" onchange="prepararCrop(this, 'e')" accept="image/*">
                    <input type="text" name="imagen_url_texto" class="form-control form-control-sm mt-2" placeholder="Nueva URL HTTPS">
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-12"><label class="fw-bold">Nombre</label><input type="text" name="nombre" id="e_nombre" class="form-control" required></div>
                        <div class="col-6"><label class="fw-bold">Precio ($)</label><input type="number" name="precio" id="e_precio" class="form-control" required></div>
                        <div class="col-6"><label class="fw-bold text-danger">Precio Oferta ($)</label><input type="number" name="precio_oferta" id="e_oferta" class="form-control border-danger"></div>
                        <div class="col-6">
                            <label class="fw-bold">Categoría</label>
                            <select name="id_categoria" id="e_cat" class="form-select">
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 bg-light p-3 rounded">
                            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="es_ilimitado" id="e_ilim" onchange="toggleDates('e')"><label class="form-check-label fw-bold">Ilimitado</label></div>
                            <div class="row g-2" id="e_dates">
                                <div class="col-6"><small>Desde</small><input type="date" name="fecha_inicio" id="e_ini" class="form-control"></div>
                                <div class="col-6"><small>Hasta</small><input type="date" name="fecha_fin" id="e_fin" class="form-control"></div>
                            </div>
                            <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="es_destacado" id="e_dest" value="1"><label class="form-check-label small">Destacar Web</label></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-warning w-100 fw-bold py-3">GUARDAR CAMBIOS</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCrop" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 bg-dark"><img id="imageToCrop" src="" style="max-width: 100%;"></div>
            <div class="modal-footer bg-dark border-0">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="btnRecortar">RECORTAR Y LISTO</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="agregar_item" value="1">
            <input type="hidden" name="combo_codigo" id="add_cod">
            
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Armar contenido de: <span id="add_nom"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Agrega todos los productos que componen este combo.</p>
                
                <div id="contenedor-items">
                    </div>

                <button type="button" class="btn btn-outline-success btn-sm mt-3 fw-bold rounded-pill" onclick="agregarFila()">
                    <i class="bi bi-plus-circle"></i> Agregar otra línea
                </button>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold px-4">GUARDAR TODO</button>
            </div>
        </form>
    </div>
</div>

<template id="rowTemplate">
    <div class="row g-2 mb-2 align-items-end row-item">
        <div class="col-8">
            <label class="small fw-bold text-muted">Producto</label>
            <select name="productos[]" class="form-select select2-dinamico" required style="width:100%">
                <option value="">Buscar...</option>
                <?php foreach($productos_lista as $p): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo $p['descripcion']; ?> (Stock: <?php echo floatval($p['stock_actual']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <label class="small fw-bold text-muted">Cant.</label>
            <input type="number" name="cantidades[]" class="form-control text-center fw-bold" value="1" min="1" step="1">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger w-100" onclick="eliminarFila(this)" title="Quitar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let cropper;
    let prefijoGlobal = '';
    const modalCrop = new bootstrap.Modal(document.getElementById('modalCrop'));

    $(document).ready(function() {
        $('.select2').select2({ dropdownParent: $('#modalAgregar') });
    });

    function toggleDates(p) {
        $(`#${p}_dates`).toggle(!$(`#${p}_ilim`).is(':checked'));
    }

    function abrirAgregar(id, nom, cod) {
        $('#add_nom').text(nom); $('#add_cod').val(cod);
        new bootstrap.Modal(document.getElementById('modalAgregar')).show();
    }

    function abrirEditar(obj) {
        $('#e_id').val(obj.id); $('#e_nombre').val(obj.nombre); $('#e_precio').val(obj.precio);
        $('#e_oferta').val(obj.precio_oferta); $('#e_actual').val(obj.imagen_url);
        $('#e_preview').attr('src', obj.imagen_url || 'img/no-image.png');
        $('#e_cat').val(obj.id_categoria);
        $('#e_dest').prop('checked', obj.es_destacado_web == 1);
        $('#e_ilim').prop('checked', obj.es_ilimitado == 1);
        if(obj.es_ilimitado != 1) { $('#e_ini').val(obj.fecha_inicio); $('#e_fin').val(obj.fecha_fin); $('#e_dates').show(); } else { $('#e_dates').hide(); }
        new bootstrap.Modal(document.getElementById('modalEditar')).show();
    }

    function prepararCrop(input, p) {
        if (input.files && input.files[0]) {
            prefijoGlobal = p;
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#imageToCrop').attr('src', e.target.result);
                modalCrop.show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#modalCrop').on('shown.bs.modal', function() {
        cropper = new Cropper(document.getElementById('imageToCrop'), { aspectRatio: 1, viewMode: 1 });
    }).on('hidden.bs.modal', function() {
        cropper.destroy();
    });

    $('#btnRecortar').click(function() {
        let canvas = cropper.getCroppedCanvas({ width: 600, height: 600 });
        let base64 = canvas.toDataURL('image/png');
        $(`#${prefijoGlobal}_preview`).attr('src', base64);
        $(`#${prefijoGlobal}_base64`).val(base64);
        modalCrop.hide();
    });

    function borrarPack(id) {
        Swal.fire({ title: '¿Eliminar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar' }).then((r) => { if(r.isConfirmed) window.location.href='combos.php?eliminar_id='+id; });
    }
    function filtrarCombos(tipo) {
        $('.item-combo').each(function() {
            let mostrar = false;
            if(tipo === 'oferta' && $(this).data('oferta') == 1) mostrar = true;
            else if(tipo === 'destacado' && $(this).data('destacado') == 1) mostrar = true;
            else if(tipo === 'todos') mostrar = true; // Por si quieres un botón reset

            // Lógica de toggle: si ya estaba filtrado y clicleo de nuevo, muestro todo? 
            // Hagámoslo simple: Click filtra. Para ver todos, recargar o limpiar.
            // Mejor: Si ocultamos los que NO coinciden.
            
            if(mostrar) $(this).fadeIn();
            else $(this).fadeOut();
        });
        
        // Mensaje visual opcional (toast)
        const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
        Toast.fire({icon: 'info', title: 'Filtrando: ' + tipo});
    }
    // --- Lógica para Agregar Filas Dinámicas ---

    function abrirAgregar(id, nom, cod) {
        $('#add_nom').text(nom);
        $('#add_cod').val(cod);
        
        // Limpiar contenedor y agregar la primera fila vacía
        $('#contenedor-items').html('');
        agregarFila(); 
        
        new bootstrap.Modal(document.getElementById('modalAgregar')).show();
    }

    function agregarFila() {
        // 1. Obtener el contenido del template
        const template = document.getElementById('rowTemplate');
        const clone = template.content.cloneNode(true);
        
        // 2. Agregarlo al DOM
        $('#contenedor-items').append(clone);

        // 3. Inicializar Select2 SOLO en el nuevo elemento agregado
        // Buscamos el último select agregado que tenga la clase select2-dinamico
        $('#contenedor-items .select2-dinamico:last').select2({
            dropdownParent: $('#modalAgregar'),
            width: '100%'
        });
    }

    function eliminarFila(btn) {
        // Solo eliminamos si hay más de una fila, o si quieres permitir dejarlo vacío, quita el if.
        if ($('#contenedor-items .row-item').length > 1) {
            $(btn).closest('.row-item').remove();
        } else {
            // Si es la última, solo reseteamos valores
            let fila = $(btn).closest('.row-item');
            fila.find('select').val('').trigger('change');
            fila.find('input').val(1);
        }
    }
</script>

<?php include 'includes/layout_footer.php'; ?>