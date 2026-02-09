<?php
// combos.php - EDITOR PROFESIONAL CON IMÁGENES Y CATEGORÍAS
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

// --- CONSULTAS ---
// Traemos combos con sus datos de producto vinculados (oferta, imagen, categoria, destacado)
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Combos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 50px; }
        .card-combo { border: 0; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; transition: 0.3s; background: white; }
        .card-combo:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .img-combo-box { height: 160px; width: 100%; background: #fff; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid #f0f0f0; padding: 10px; }
        .img-combo-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .price-tag { font-size: 1.4rem; font-weight: 800; color: #198754; }
        .old-price { text-decoration: line-through; color: #999; font-size: 0.9rem; }
        .btn-action { width: 35px; height: 35px; border-radius: 10px; border: 0; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .preview-img-modal { width: 120px; height: 120px; object-fit: contain; border: 2px dashed #ddd; padding: 5px; border-radius: 10px; background: white; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0"><i class="bi bi-box-seam-fill text-primary"></i> Mis Packs y Combos</h2>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg"></i> NUEVO COMBO
            </button>
        </div>

        <div class="row g-3">
            <?php foreach($combos as $c): 
                $items = $recetas_data[$c['id']];
                $jsonC = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-combo h-100 d-flex flex-column">
                    <div class="img-combo-box">
                        <img src="<?php echo $c['imagen_url'] ?: 'img/no-image.png'; ?>" loading="lazy">
                        <?php if($c['es_destacado_web']): ?>
                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2"><i class="bi bi-star-fill"></i></span>
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

                        <div class="bg-light p-2 rounded small">
                            <b class="d-block mb-1 border-bottom pb-1">Contenido:</b>
                            <?php if(empty($items)): ?>
                                <span class="text-muted italic">Vacio. Agrega productos.</span>
                            <?php else: ?>
                                <?php foreach($items as $i): ?>
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo $i['cantidad']; ?>x <?php echo $i['descripcion']; ?></span>
                                        <a href="combos.php?borrar_item=<?php echo $i['id']; ?>" class="text-danger"><i class="bi bi-x-circle"></i></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-3 bg-light d-flex justify-content-end gap-2 border-top">
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
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow">
                <input type="hidden" name="agregar_item" value="1">
                <input type="hidden" name="combo_codigo" id="add_cod">
                <div class="modal-header bg-success text-white"><h5 class="modal-title fw-bold">Agregar Producto a <span id="add_nom"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <label class="fw-bold mb-1">Producto</label>
                    <select name="id_producto" class="form-select select2" required style="width:100%">
                        <option value="">Buscar...</option>
                        <?php foreach($productos_lista as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['descripcion']; ?> (Stock: <?php echo floatval($p['stock_actual']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label class="fw-bold mt-3 mb-1">Cantidad</label>
                    <input type="number" name="cantidad" class="form-control form-control-lg text-center fw-bold" value="1" min="1">
                </div>
                <div class="modal-footer"><button class="btn btn-success w-100 fw-bold">AGREGAR ITEM</button></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>
</html>