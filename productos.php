<?php
// productos.php - CON FILTROS Y BUSCADOR REAL
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// PROCESAR ALTA
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "INSERT INTO productos (codigo_barras, descripcion, id_categoria, precio_costo, precio_venta, stock_actual, activo) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $conexion->prepare($sql)->execute([$_POST['codigo'], $_POST['descripcion'], $_POST['categoria'], $_POST['precio_costo'], $_POST['precio_venta'], $_POST['stock']]);
    header("Location: productos.php"); exit;
}
if (isset($_GET['borrar'])) {
    $conexion->query("UPDATE productos SET activo=0 WHERE id=" . $_GET['borrar']);
    header("Location: productos.php"); exit;
}

// OBTENER DATOS
$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$productos = $conexion->query("SELECT p.*, c.nombre as cat FROM productos p JOIN categorias c ON p.id_categoria=c.id WHERE p.activo=1 ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroTexto" class="form-control border-start-0" placeholder="Buscar por nombre o código...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="filtroCat" class="form-select">
                            <option value="">Todas las Categorías</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?php echo $c->nombre; ?>"><?php echo $c->nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-success fw-bold w-100" data-bs-toggle="modal" data-bs-target="#modalProd">
                            <i class="bi bi-plus-lg"></i> Nuevo Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaProductos">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Descripción</th>
                                <th>Categoría</th>
                                <th>Costo</th>
                                <th>Venta</th>
                                <th>Stock</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($productos as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo $p->descripcion; ?></div>
                                    <small class="text-muted"><?php echo $p->codigo_barras; ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo $p->cat; ?></span></td>
                                <td>$<?php echo $p->precio_costo; ?></td>
                                <td class="fw-bold text-primary">$<?php echo $p->precio_venta; ?></td>
                                <td>
                                    <span class="badge <?php echo $p->stock_actual <= $p->stock_minimo ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $p->stock_actual; ?> u.
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="productos.php?borrar=<?php echo $p->id; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('¿Eliminar?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noResults" class="text-center p-4 text-muted" style="display:none;">No se encontraron productos.</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProd" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Agregar Producto</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-bold">Código Barras</label><input type="text" name="codigo" class="form-control"></div>
                        <div class="mb-2"><label class="small fw-bold">Descripción *</label><input type="text" name="descripcion" class="form-control" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small fw-bold">Costo *</label><input type="number" step="0.01" name="precio_costo" class="form-control" required></div>
                            <div class="col-6"><label class="small fw-bold">Venta *</label><input type="number" step="0.01" name="precio_venta" class="form-control" required></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="small fw-bold">Stock Inicial</label><input type="number" name="stock" class="form-control" required></div>
                            <div class="col-6">
                                <label class="small fw-bold">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <?php foreach($categorias as $c): ?>
                                        <option value="<?php echo $c->id; ?>"><?php echo $c->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-success w-100 fw-bold">GUARDAR PRODUCTO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FILTRO JS INSTANTÁNEO
        const filtroTexto = document.getElementById('filtroTexto');
        const filtroCat = document.getElementById('filtroCat');
        const tabla = document.getElementById('tablaProductos');
        const filas = tabla.getElementsByTagName('tr');

        function filtrar() {
            let texto = filtroTexto.value.toLowerCase();
            let cat = filtroCat.value.toLowerCase();
            let visibles = 0;

            // Empezamos desde 1 para saltar el encabezado
            for (let i = 1; i < filas.length; i++) {
                let fila = filas[i];
                let celdaDesc = fila.getElementsByTagName('td')[0];
                let celdaCat = fila.getElementsByTagName('td')[1];
                
                if (celdaDesc && celdaCat) {
                    let txtValue = celdaDesc.textContent || celdaDesc.innerText;
                    let catValue = celdaCat.textContent || celdaCat.innerText;
                    
                    if (txtValue.toLowerCase().indexOf(texto) > -1 && (cat === "" || catValue.toLowerCase().indexOf(cat) > -1)) {
                        fila.style.display = "";
                        visibles++;
                    } else {
                        fila.style.display = "none";
                    }
                }
            }
            document.getElementById('noResults').style.display = visibles === 0 ? 'block' : 'none';
        }

        filtroTexto.addEventListener('keyup', filtrar);
        filtroCat.addEventListener('change', filtrar);
    </script>
</body>
</html>