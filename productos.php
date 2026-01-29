<?php
// productos.php - CON SEMÁFORO REAL Y CÁLCULO DE GANANCIA
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// PROCESAR ALTA RÁPIDA (Mantengo tu lógica original)
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

// OBTENER CONFIG GLOBAL DE VENCIMIENTO
$config_db = $conexion->query("SELECT dias_alerta_vencimiento FROM configuracion WHERE id=1")->fetch();
$dias_global = $config_db->dias_alerta_vencimiento ?? 30;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .badge-vencido { background-color: #dc3545; color: white; animation: pulse 2s infinite; }
        .badge-proximo { background-color: #ffc107; color: black; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        /* Estilos para ganancia */
        .info-ganancia { font-size: 0.75rem; color: #6c757d; }
        .info-ganancia .pct { font-weight: bold; color: #198754; }
    </style>
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
                    <div class="col-md-3 text-end d-flex gap-2">
                        <a href="precios_masivos.php" class="btn btn-outline-danger" title="Aumento por Inflación">
                            <i class="bi bi-graph-up-arrow"></i>
                        </a>

                        <a href="producto_formulario.php" class="btn btn-success fw-bold w-100">
                            <i class="bi bi-plus-lg"></i> Nuevo
                        </a>
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
                                <th>Precio / Ganancia</th>
                                <th>Stock (Semáforo)</th>
                                <th>Estado Vencimiento</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hoy = date('Y-m-d');

                            foreach($productos as $p): 
                                // 1. LÓGICA DE ALERTA DE VENCIMIENTO (Original tuya)
                                $dias_aviso = ($p->dias_alerta > 0) ? $p->dias_alerta : $dias_global;
                                $fecha_alerta = date('Y-m-d', strtotime("+$dias_aviso days"));
                                
                                $estado_venc = '';
                                if($p->fecha_vencimiento) {
                                    if($p->fecha_vencimiento < $hoy) {
                                        $estado_venc = '<span class="badge badge-vencido"><i class="bi bi-exclamation-octagon"></i> VENCIDO</span>';
                                    } elseif($p->fecha_vencimiento <= $fecha_alerta) {
                                        $dias_restantes = (strtotime($p->fecha_vencimiento) - strtotime($hoy)) / 86400;
                                        $estado_venc = '<span class="badge badge-proximo"><i class="bi bi-clock-history"></i> Vence en '.ceil($dias_restantes).' días</span>';
                                    }
                                }

                                // 2. LÓGICA DE GANANCIA
                                $costo = floatval($p->precio_costo);
                                $venta = floatval($p->precio_venta);
                                $ganancia = $venta - $costo;
                                $margen = ($costo > 0) ? ($ganancia / $costo) * 100 : 100;

                                // 3. LÓGICA SEMÁFORO DE STOCK (Nueva)
                                $stk = floatval($p->stock_actual);
                                $min = floatval($p->stock_minimo);
                                
                                // Definimos color y mensaje según stock
                                if($stk <= $min) {
                                    // ROJO: Crítico
                                    $badgeStock = '<span class="badge bg-danger shadow-sm"><i class="bi bi-x-circle-fill"></i> '.$stk.' u.</span>';
                                } elseif($stk <= ($min * 2)) {
                                    // AMARILLO: Advertencia (Menos del doble del mínimo)
                                    $badgeStock = '<span class="badge bg-warning text-dark shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> '.$stk.' u.</span>';
                                } else {
                                    // VERDE: OK
                                    $badgeStock = '<span class="badge bg-success shadow-sm"><i class="bi bi-check-circle-fill"></i> '.$stk.' u.</span>';
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo $p->descripcion; ?></div>
                                    <small class="text-muted"><?php echo $p->codigo_barras; ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo $p->cat; ?></span></td>
                                
                                <td>
                                    <div class="fw-bold text-primary" style="font-size: 1.1em;">$<?php echo number_format($venta, 2); ?></div>
                                    <div class="info-ganancia" title="Costo: $<?php echo $costo; ?>">
                                        Gan: $<?php echo number_format($ganancia, 2); ?> <span class="pct">(<?php echo round($margen); ?>%)</span>
                                    </div>
                                </td>

                                <td>
                                    <?php echo $badgeStock; ?>
                                </td>

                                <td>
                                    <?php echo $estado_venc; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="producto_formulario.php?id=<?php echo $p->id; ?>" class="btn btn-sm btn-outline-primary border-0"><i class="bi bi-pencil-square"></i></a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const filtroTexto = document.getElementById('filtroTexto');
        const filtroCat = document.getElementById('filtroCat');
        const tabla = document.getElementById('tablaProductos');
        const filas = tabla.getElementsByTagName('tr');

        function filtrar() {
            let texto = filtroTexto.value.toLowerCase();
            let cat = filtroCat.value.toLowerCase();
            let visibles = 0;

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