<?php
// producto_formulario.php - CON AUDITORÍA (Diseño Original Intacto)
session_start();
require_once 'includes/db.php';

// SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php?error=acceso_denegado");
    exit;
}

$id = $_GET['id'] ?? null;
$producto = null;
$mensaje_exito = false;

// CARGAR DATOS SI ES EDICIÓN
if ($id) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
}

$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$proveedores = $conexion->query("SELECT * FROM proveedores")->fetchAll();

// PROCESAR GUARDADO
// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo_barras'];
    $desc = $_POST['descripcion'];
    $cat = $_POST['id_categoria'];
    $prov = $_POST['id_proveedor'];
    $costo = $_POST['precio_costo'];
    $venta = $_POST['precio_venta'];
    // NUEVO: Capturar precio oferta
    $oferta = !empty($_POST['precio_oferta']) ? $_POST['precio_oferta'] : null;
    
    $stock = $_POST['stock_actual'];
    $minimo = $_POST['stock_minimo'];
    $img = $_POST['imagen_url'];
    
    $vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $dias_alerta = !empty($_POST['dias_alerta']) ? $_POST['dias_alerta'] : null;
    
    $web = isset($_POST['es_destacado_web']) ? 1 : 0;
    $celiaco = isset($_POST['es_apto_celiaco']) ? 1 : 0;
    $vegano = isset($_POST['es_apto_vegano']) ? 1 : 0;

    try {
        if ($id) {
            // ACTUALIZAR (Incluye precio_oferta)
            $sql = "UPDATE productos SET codigo_barras=?, descripcion=?, id_categoria=?, id_proveedor=?, 
                    precio_costo=?, precio_venta=?, precio_oferta=?, stock_actual=?, stock_minimo=?, imagen_url=?, 
                    fecha_vencimiento=?, dias_alerta=?, es_destacado_web=?, es_apto_celiaco=?, es_apto_vegano=? WHERE id=?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$codigo, $desc, $cat, $prov, $costo, $venta, $oferta, $stock, $minimo, $img, $vencimiento, $dias_alerta, $web, $celiaco, $vegano, $id]);
        } else {
            // CREAR (Incluye precio_oferta)
            $sql = "INSERT INTO productos (codigo_barras, descripcion, id_categoria, id_proveedor, 
                    precio_costo, precio_venta, precio_oferta, stock_actual, stock_minimo, imagen_url, fecha_vencimiento, dias_alerta,
                    es_destacado_web, es_apto_celiaco, es_apto_vegano) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$codigo, $desc, $cat, $prov, $costo, $venta, $oferta, $stock, $minimo, $img, $vencimiento, $dias_alerta, $web, $celiaco, $vegano]);
        }
        $mensaje_exito = true;
    } catch (PDOException $e) {
        $error = "Error en base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { background: white; border-bottom: 1px solid #eee; padding: 20px; border-radius: 15px 15px 0 0 !important; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #555; }
        .btn-guardar { padding: 12px 30px; font-weight: bold; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-primary fw-bold">
                            <?php echo $id ? '✏️ Editar Producto' : '✨ Nuevo Producto'; ?>
                        </h4>
                        <a href="productos.php" class="btn btn-outline-secondary btn-sm">Cancelar y Volver</a>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="formProducto">
                            
                            <h6 class="text-uppercase text-muted mb-3 small fw-bold">Información Básica</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Código de Barras</label>
                                    <input type="text" class="form-control" name="codigo_barras" 
                                           value="<?php echo $producto->codigo_barras ?? ''; ?>" autofocus required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Nombre / Descripción</label>
                                    <input type="text" class="form-control" name="descripcion" 
                                           value="<?php echo $producto->descripcion ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" name="id_categoria" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($categorias as $c): ?>
                                            <option value="<?php echo $c->id; ?>" <?php if(($producto->id_categoria ?? 0) == $c->id) echo 'selected'; ?>>
                                                <?php echo $c->nombre; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Proveedor</label>
                                    <select class="form-select" name="id_proveedor">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($proveedores as $p): ?>
                                            <option value="<?php echo $p->id; ?>" <?php if(($producto->id_proveedor ?? 0) == $p->id) echo 'selected'; ?>>
                                                <?php echo $p->empresa; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <hr class="text-muted opacity-25">

                            <h6 class="text-uppercase text-muted mb-3 small fw-bold">Inventario y Finanzas</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Costo ($)</label>
                                    <input type="number" step="0.01" class="form-control bg-light" name="precio_costo" 
                                           value="<?php echo $producto->precio_costo ?? ''; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-success">Venta ($)</label>
                                    <input type="number" step="0.01" class="form-control border-success" name="precio_venta" 
                                           value="<?php echo $producto->precio_venta ?? ''; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-danger fw-bold">¡Oferta! ($)</label>
                                    <input type="number" step="0.01" class="form-control border-danger bg-light" name="precio_oferta" 
                                           value="<?php echo $producto->precio_oferta ?? ''; ?>" placeholder="Opcional">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Stock Real</label>
                                    <input type="number" step="0.001" class="form-control fw-bold" name="stock_actual" 
                                           value="<?php echo $producto->stock_actual ?? ''; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-danger">Mínimo</label>
                                    <input type="number" step="0.001" class="form-control" name="stock_minimo" 
                                           value="<?php echo $producto->stock_minimo ?? '5'; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label text-warning">Vencimiento</label>
                                    <input type="date" class="form-control border-warning" name="fecha_vencimiento" 
                                           value="<?php echo $producto->fecha_vencimiento ?? ''; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-warning" style="font-size: 0.8rem;">Avisar (Días)</label>
                                    <input type="number" class="form-control" name="dias_alerta" 
                                           value="<?php echo $producto->dias_alerta ?? ''; ?>" placeholder="Global">
                                </div>
                            </div>

                            <hr class="text-muted opacity-25">

                            <h6 class="text-uppercase text-muted mb-3 small fw-bold">Configuración Góndola Web</h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">URL Imagen</label>
                                    <input type="text" class="form-control" name="imagen_url" 
                                           value="<?php echo $producto->imagen_url ?? ''; ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-2 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="es_destacado_web" <?php if($producto->es_destacado_web ?? 0) echo 'checked'; ?>>
                                        <label class="form-check-label">Destacar en Portada</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-2 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="es_apto_celiaco" <?php if($producto->es_apto_celiaco ?? 0) echo 'checked'; ?>>
                                        <label class="form-check-label">Apto Celíaco</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-2 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="es_apto_vegano" <?php if($producto->es_apto_vegano ?? 0) echo 'checked'; ?>>
                                        <label class="form-check-label">Apto Vegano</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 text-end">
                                <button type="submit" class="btn btn-primary btn-guardar shadow">
                                    <i class="bi bi-check-lg"></i> GUARDAR CAMBIOS
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        <?php if($mensaje_exito): ?>
        Swal.fire({
            title: '¡Perfecto!',
            text: 'El producto se ha guardado correctamente.',
            icon: 'success',
            confirmButtonText: 'Genial',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'productos.php';
            }
        });
        <?php endif; ?>

        <?php if(isset($error)): ?>
        Swal.fire({ title: 'Error', text: '<?php echo $error; ?>', icon: 'error' });
        <?php endif; ?>
    </script>
</body>
</html>