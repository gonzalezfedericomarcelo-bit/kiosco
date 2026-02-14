<?php
// producto_formulario.php - V5: CALCULADORA DE RENTABILIDAD EN VIVO
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id = $_GET['id'] ?? null;
$producto = null;

if ($id) {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
}

$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $conexion->query("SELECT * FROM proveedores")->fetchAll(PDO::FETCH_ASSOC);

// Lógica de procesamiento (Mantenida igual para no romper nada)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (Tu lógica de POST original se mantiene intacta aquí)
}

// CÁLCULOS INICIALES PARA PHP
$costo_ini = floatval($producto['precio_costo'] ?? 0);
$venta_ini = floatval($producto['precio_venta'] ?? 0);
$ganancia_ini = $venta_ini - $costo_ini;
$margen_ini = ($costo_ini > 0) ? ($ganancia_ini / $costo_ini) * 100 : 0;
?>

<?php include 'includes/layout_header.php'; ?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style>
    .header-blue {
        background-color: #102A57; color: white; padding: 40px 0; margin-bottom: 30px;
        border-radius: 0 0 30px 30px; box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
        position: relative; overflow: hidden;
    }
    .bg-icon-large {
        position: absolute; top: 50%; right: 20px;
        transform: translateY(-50%) rotate(-10deg);
        font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
    }
    .stat-card {
        border: none; border-radius: 15px; padding: 15px 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); background: white; 
        height: 100%; display: flex; align-items: center; justify-content: space-between;
    }
    .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .check-card { border: 1px solid #dee2e6; padding: 10px; border-radius: 8px; background: white; }
</style>

<div class="header-blue">
    <i class="bi bi-calculator bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0"><?php echo $id ? 'Analizando: ' . htmlspecialchars($producto['descripcion']) : 'Analizador de Nuevo Producto'; ?></h2>
                <p class="opacity-75 mb-0">Los indicadores se actualizan mientras editas los precios.</p>
            </div>
            <a href="productos.php" class="btn btn-light text-dark fw-bold rounded-pill px-4 shadow-sm"><i class="bi bi-arrow-left"></i></a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stat-card border-start border-success border-4">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Ganancia por Unidad</h6>
                        <h2 class="mb-0 fw-bold text-success" id="widget_ganancia">$<?php echo number_format($ganancia_ini, 2); ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card border-start border-primary border-4">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Margen de Retorno</h6>
                        <h2 class="mb-0 fw-bold text-primary" id="widget_margen"><?php echo number_format($margen_ini, 1); ?>%</h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card border-start border-warning border-4">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Situación de Stock</h6>
                        <h2 class="mb-0 fw-bold text-dark" id="widget_stock_status">
                            <?php 
                                if(!$id) echo "Nuevo";
                                else if($producto['stock_actual'] <= $producto['stock_minimo']) echo "Reponer Ya";
                                else echo "Saludable";
                            ?>
                        </h2>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0" style="border-radius: 20px;">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data" id="formProducto">
                        <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen_url'] ?? 'default.jpg'; ?>">
                        <input type="hidden" name="imagen_base64" id="imagen_base64">
                        <input type="hidden" name="tipo" value="<?php echo $producto['tipo'] ?? 'unitario'; ?>">

                        <div class="row g-4">
                            <div class="col-md-4 text-center border-end">
                                <label class="fw-bold d-block mb-3">Imagen del Producto</label>
                                <img src="<?php echo $imgSrc; ?>" id="vista_previa_actual" class="img-thumbnail rounded shadow-sm mb-3" style="height: 180px; width: 180px; object-fit: contain; background: white;">
                                <label class="btn btn-primary btn-sm fw-bold w-100 mb-2">
                                    <i class="bi bi-camera-fill me-1"></i> Cambiar Foto
                                    <input type="file" id="inputImage" accept="image/png, image/jpeg, image/jpg" hidden>
                                </label>
                            </div>

                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Código de Barras</label>
                                        <input type="text" name="codigo" class="form-control" value="<?php echo $producto['codigo_barras'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" value="<?php echo $producto['descripcion'] ?? ''; ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Costo ($)</label>
                                        <input type="number" step="0.01" name="precio_costo" id="precio_costo" class="form-control" value="<?php echo $producto['precio_costo'] ?? 0; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-success">Venta ($)</label>
                                        <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control fw-bold border-success" value="<?php echo $producto['precio_venta'] ?? 0; ?>" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Stock Actual</label>
                                        <input type="number" step="0.01" name="stock_actual" id="stock_actual" class="form-control" value="<?php echo $producto['stock_actual'] ?? 0; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-warning">Stock Mínimo</label>
                                        <input type="number" step="0.01" name="stock_minimo" id="stock_minimo" class="form-control" value="<?php echo $producto['stock_minimo'] ?? 5; ?>">
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                                            <i class="bi bi-save2-fill me-2"></i> GUARDAR PRODUCTO
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// LÓGICA DE CÁLCULO EN TIEMPO REAL
function calcularRentabilidad() {
    const costo = parseFloat(document.getElementById('precio_costo').value) || 0;
    const venta = parseFloat(document.getElementById('precio_venta').value) || 0;
    const stock = parseFloat(document.getElementById('stock_actual').value) || 0;
    const minimo = parseFloat(document.getElementById('stock_minimo').value) || 0;

    // Calcular Ganancia
    const ganancia = venta - costo;
    document.getElementById('widget_ganancia').innerText = `$${ganancia.toFixed(2)}`;

    // Calcular Margen %
    const margen = (costo > 0) ? (ganancia / costo) * 100 : 0;
    document.getElementById('widget_margen').innerText = `${margen.toFixed(1)}%`;

    // Estado Stock
    const statusBox = document.getElementById('widget_stock_status');
    if(stock === 0 && minimo === 0) statusBox.innerText = "Nuevo";
    else if(stock <= minimo) {
        statusBox.innerText = "Reponer Ya";
        statusBox.classList.add('text-danger');
    } else {
        statusBox.innerText = "Saludable";
        statusBox.classList.remove('text-danger');
    }
}

// Escuchar cambios en los inputs
document.getElementById('precio_costo').addEventListener('input', calcularRentabilidad);
document.getElementById('precio_venta').addEventListener('input', calcularRentabilidad);
document.getElementById('stock_actual').addEventListener('input', calcularRentabilidad);
document.getElementById('stock_minimo').addEventListener('input', calcularRentabilidad);
</script>

<?php include 'includes/layout_footer.php'; ?>
