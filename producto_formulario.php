<?php
// producto_formulario.php - V3: CORREGIDO MENU, OFERTAS Y SALUD
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id = $_GET['id'] ?? null;
$producto = null;

// Lógica para cargar datos si es edición
if ($id) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
}

// OBTENER CATEGORÍAS Y PROVEEDORES
$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $conexion->query("SELECT * FROM proveedores")->fetchAll(PDO::FETCH_ASSOC);

// PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $descripcion = $_POST['descripcion'];
    
    // CORRECCIÓN 1: Manejo de NULL para claves foráneas (Evita error FK)
    $categoria = !empty($_POST['categoria']) ? $_POST['categoria'] : NULL;
    $proveedor = !empty($_POST['proveedor']) ? $_POST['proveedor'] : NULL;
    
    $costo = !empty($_POST['precio_costo']) ? $_POST['precio_costo'] : 0;
    $venta = !empty($_POST['precio_venta']) ? $_POST['precio_venta'] : 0;
    
    // CORRECCIÓN 3: OFERTA (Si está vacío o es 0, se guarda NULL para que no aplique)
    $oferta = (!empty($_POST['precio_oferta']) && $_POST['precio_oferta'] > 0) ? $_POST['precio_oferta'] : NULL;
    
    $stock = !empty($_POST['stock_actual']) ? $_POST['stock_actual'] : 0;
    $minimo = !empty($_POST['stock_minimo']) ? $_POST['stock_minimo'] : 5;
    
    // CORRECCIÓN 2: Recuperar tipo correctamente desde el input oculto
    $es_combo = (isset($_POST['tipo']) && $_POST['tipo'] == 'combo') ? 'combo' : 'unitario';
    
    // --- CORRECCIÓN 4: ETIQUETAS DE SALUD (Aseguramos 1 o 0) ---
    $es_vegano = isset($_POST['es_vegano']) ? 1 : 0;
    $es_celiaco = isset($_POST['es_celiaco']) ? 1 : 0; 
    
    // MANEJO DE IMAGEN
    $imagen_final = $_POST['imagen_actual'] ?? 'default.jpg';
    
    if (!empty($_POST['imagen_base64'])) {
        $data = $_POST['imagen_base64'];
        $image_array_1 = explode(";", $data);
        $image_array_2 = explode(",", $image_array_1[1]);
        $data = base64_decode($image_array_2[1]);
        
        $nombre_img = 'prod_' . time() . '_' . rand(100,999) . '.png';
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $ruta_destino = 'uploads/' . $nombre_img;
        
        if(file_put_contents($ruta_destino, $data)) {
            $imagen_final = $ruta_destino; 
        }
    } 
    elseif (!empty($_POST['imagen_url_texto'])) {
        $imagen_final = $_POST['imagen_url_texto'];
    }

    try {
        if ($id) {
            // ACTUALIZAR (Verificamos que todos los campos coincidan con la tabla)
            $sql = "UPDATE productos SET 
                    codigo_barras=?, descripcion=?, id_categoria=?, id_proveedor=?, 
                    precio_costo=?, precio_venta=?, precio_oferta=?, 
                    stock_actual=?, stock_minimo=?, tipo=?, imagen_url=?, 
                    es_vegano=?, es_celiaco=? 
                    WHERE id=?";
            $conexion->prepare($sql)->execute([$codigo, $descripcion, $categoria, $proveedor, $costo, $venta, $oferta, $stock, $minimo, $es_combo, $imagen_final, $es_vegano, $es_celiaco, $id]);
        } else {
            // CREAR
            $sql = "INSERT INTO productos (codigo_barras, descripcion, id_categoria, id_proveedor, precio_costo, precio_venta, precio_oferta, stock_actual, stock_minimo, tipo, imagen_url, activo, es_vegano, es_celiaco) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
            $conexion->prepare($sql)->execute([$codigo, $descripcion, $categoria, $proveedor, $costo, $venta, $oferta, $stock, $minimo, $es_combo, $imagen_final, $es_vegano, $es_celiaco]);
        }
        header("Location: productos.php"); exit;
    } catch (Exception $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $id ? 'Editar' : 'Nuevo'; ?> Producto</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <style>
        .img-container { max-height: 500px; display: block; }
        .preview-box { width: 200px; height: 200px; overflow: hidden; border: 2px dashed #ccc; margin: 0 auto; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .preview-img { max-width: 100%; max-height: 100%; }
        /* Estilo Checkboxes Salud */
        .check-card { border: 1px solid #dee2e6; padding: 10px; border-radius: 8px; transition: 0.2s; cursor: pointer; }
        .check-card:hover { background: #f8f9fa; }
        .form-check-input:checked + .form-check-label { font-weight: bold; color: #198754; }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5 pt-4"> <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><?php echo $id ? '<i class="bi bi-pencil-square"></i> Editar Producto' : '<i class="bi bi-plus-circle"></i> Nuevo Producto'; ?></span>
                        <a href="productos.php" class="btn btn-sm btn-light text-primary fw-bold"><i class="bi bi-arrow-left"></i> Volver</a>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="formProducto">
                            
                            <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen_url'] ?? 'default.jpg'; ?>">
                            <input type="hidden" name="imagen_base64" id="imagen_base64">
                            <input type="hidden" name="tipo" value="<?php echo $producto['tipo'] ?? 'unitario'; ?>">

                            <div class="row g-3">
                                <div class="col-12 text-center mb-3">
                                    <label class="form-label fw-bold d-block">Imagen del Producto</label>
                                    <div class="mb-3">
                                        <?php 
                                            $imgShow = $producto['imagen_url'] ?? 'default.jpg';
                                            if(strpos($imgShow, 'http') === false && file_exists($imgShow)) $imgSrc = $imgShow;
                                            elseif(strpos($imgShow, 'http') !== false) $imgSrc = $imgShow;
                                            else $imgSrc = 'https://via.placeholder.com/150?text=Sin+Imagen';
                                        ?>
                                        <img src="<?php echo $imgSrc; ?>" id="vista_previa_actual" class="img-thumbnail rounded shadow-sm" style="height: 150px; width: 150px; object-fit: contain; background: white;">
                                    </div>
                                    <label class="btn btn-outline-primary btn-sm fw-bold">
                                        <i class="bi bi-camera-fill"></i> Subir Foto PC
                                        <input type="file" id="inputImage" accept="image/png, image/jpeg, image/jpg" hidden>
                                    </label>
                                    <button type="button" class="btn btn-link btn-sm text-muted" onclick="document.getElementById('divUrl').classList.toggle('d-none')">Usar URL externa</button>
                                    <div id="divUrl" class="d-none mt-2">
                                        <input type="text" name="imagen_url_texto" class="form-control form-control-sm" placeholder="Pegar enlace HTTPS...">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Código de Barras</label>
                                    <input type="text" name="codigo" class="form-control" value="<?php echo $producto['codigo_barras'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Descripción / Nombre</label>
                                    <input type="text" name="descripcion" class="form-control" value="<?php echo $producto['descripcion'] ?? ''; ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">-- Sin Categoría --</option>
                                        <?php foreach($categorias as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo ($producto && $producto['id_categoria'] == $c['id']) ? 'selected' : ''; ?>>
                                                <?php echo $c['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Proveedor</label>
                                    <select name="proveedor" class="form-select">
                                        <option value="">-- Sin Proveedor --</option>
                                        <?php foreach($proveedores as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo ($producto && $producto['id_proveedor'] == $p['id']) ? 'selected' : ''; ?>>
                                                <?php echo $p['empresa']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 mt-3">
                                    <label class="form-label fw-bold text-success"><i class="bi bi-heart-pulse"></i> Etiquetas de Salud</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check check-card flex-fill">
                                            <input type="hidden" name="es_vegano" value="0">
                                            <input class="form-check-input" type="checkbox" name="es_vegano" id="chk_vegano" value="1" <?php echo (!empty($producto['es_vegano']) && $producto['es_vegano']==1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chk_vegano">🌱 Es Vegano</label>
                                        </div>
                                        <div class="form-check check-card flex-fill">
                                            <input type="hidden" name="es_celiaco" value="0">
                                            <input class="form-check-input" type="checkbox" name="es_celiaco" id="chk_celiaco" value="1" <?php echo (!empty($producto['es_celiaco']) && $producto['es_celiaco']==1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chk_celiaco">🌾 Sin TACC (Celíaco)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12"><hr></div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-muted">Costo ($)</label>
                                    <input type="number" step="0.01" name="precio_costo" class="form-control" value="<?php echo $producto['precio_costo'] ?? 0; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-success">Precio Venta ($)</label>
                                    <input type="number" step="0.01" name="precio_venta" class="form-control fw-bold border-success" value="<?php echo $producto['precio_venta'] ?? 0; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-danger">⚠️ Oferta ($)</label>
                                    <input type="number" step="0.01" name="precio_oferta" class="form-control border-danger" placeholder="Opcional" value="<?php echo $producto['precio_oferta'] ?? ''; ?>">
                                    <div class="form-text text-danger small" style="font-size:0.7rem">*Dejar vacío si no hay oferta</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Stock Actual</label>
                                    <input type="number" step="0.01" name="stock_actual" class="form-control" value="<?php echo $producto['stock_actual'] ?? 0; ?>">
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                                        <i class="bi bi-save"></i> GUARDAR PRODUCTO
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCrop" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-crop"></i> Ajustar Imagen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 text-center bg-secondary">
                    <div class="img-container">
                        <img id="imageToCrop" src="" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="cropImageBtn">LISTO</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let inputImage = document.getElementById('inputImage');
        let modalElement = document.getElementById('modalCrop');
        let imageToCrop = document.getElementById('imageToCrop');
        let cropBtn = document.getElementById('cropImageBtn');
        let vistaPrevia = document.getElementById('vista_previa_actual');
        let hiddenInput = document.getElementById('imagen_base64');
        let cropper;
        let modal = new bootstrap.Modal(modalElement);

        inputImage.addEventListener('change', function (e) {
            let files = e.target.files;
            if (files && files.length > 0) {
                let file = files[0];
                let url = URL.createObjectURL(file);
                imageToCrop.src = url;
                modal.show();
                inputImage.value = ''; 
            }
        });

        modalElement.addEventListener('shown.bs.modal', function () {
            cropper = new Cropper(imageToCrop, {
                aspectRatio: 1, viewMode: 1, autoCropArea: 0.9, dragMode: 'move', background: false
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (cropper) { cropper.destroy(); cropper = null; }
        });

        cropBtn.addEventListener('click', function () {
            if (cropper) {
                let canvas = cropper.getCroppedCanvas({ width: 800, height: 800, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
                let base64URL = canvas.toDataURL('image/png');
                vistaPrevia.src = base64URL;
                hiddenInput.value = base64URL;
                modal.hide();
            }
        });
    </script>
</body>
</html>