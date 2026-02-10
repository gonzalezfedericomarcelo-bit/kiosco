<?php
// productos.php - RÉPLICA VISUAL EXACTA (Barra Filtros + Tarjetas Amplias)
session_start();
// Ajuste de ruta de db.php
$ruta_db = file_exists('includes/db.php') ? 'includes/db.php' : (file_exists('../includes/db.php') ? '../includes/db.php' : 'db.php');
require_once $ruta_db;

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// --- LÓGICA DE NEGOCIO (INTACTA) ---

// 1. LOGICA TOGGLE RÁPIDO (Estado)
if(isset($_GET['toggle_id'])) {
    $id_tog = $_GET['toggle_id'];
    $st_act = $_GET['estado']; 
    $nuevo = $st_act == 1 ? 0 : 1;
    $conexion->prepare("UPDATE productos SET activo = ? WHERE id = ?")->execute([$nuevo, $id_tog]);
    header("Location: productos.php"); exit;
}

// 2. PROCESAR BAJA
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    $stmtCheck = $conexion->prepare("SELECT codigo_barras, tipo FROM productos WHERE id = ?");
    $stmtCheck->execute([$id_borrar]);
    $prodData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($prodData && $prodData['tipo'] === 'combo') {
        $conexion->prepare("DELETE FROM combos WHERE codigo_barras = ?")->execute([$prodData['codigo_barras']]);
    }

    try {
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id_borrar]);
        header("Location: productos.php?msg=borrado"); exit;
    } catch (PDOException $e) {
        $error = "No se puede eliminar: el producto está vinculado a ventas.";
    }
}

// 3. OBTENER DATOS
$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$sql = "SELECT p.*, c.nombre as cat, cb.fecha_inicio, cb.fecha_fin, cb.es_ilimitado FROM productos p LEFT JOIN categorias c ON p.id_categoria=c.id LEFT JOIN combos cb ON p.codigo_barras = cb.codigo_barras ORDER BY p.id DESC";
$productos = $conexion->query($sql)->fetchAll();
// --- CÁLCULOS PARA WIDGETS (Surgical Injection) ---
$total_prod = count($productos);
$bajo_stock = 0;
$valor_inventario = 0;

foreach($productos as $p) {
    // Si usas fetchAll() por defecto puede ser array u objeto, detectamos cuál es para no romper
    $stk = is_object($p) ? $p->stock_actual : $p['stock_actual'];
    $min = is_object($p) ? $p->stock_minimo : $p['stock_minimo'];
    $cost = is_object($p) ? $p->precio_costo : $p['precio_costo'];
    
    if($stk <= $min) $bajo_stock++;
    $valor_inventario += ($stk * $cost);
}
// --------------------------------------------------

// --- FIN LÓGICA PHP ---

require_once 'includes/layout_header.php'; 
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* FONDO GENERAL */
    body { background-color: #f4f6f9; }

    /* HEADER SECCIÓN (CATÁLOGO) */
    /* BANNER ESTANDARIZADO (Igual a Proveedores/Bienes) */
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
    /* Widgets en Banner */
    .stat-card {
        border: none; border-radius: 15px; padding: 15px 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
        background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

    /* Botones Integrados en Banner */
    .btn-banner {
        font-weight: 700; border: none; padding: 10px 20px; border-radius: 50px; 
        display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-size: 0.9rem;
    }
    .btn-banner-light { background: white; color: #102A57; }
    .btn-banner-light:hover { background: #f8f9fa; transform: translateY(-2px); color: #0d2145; }
    
    .btn-banner-gold { background: #ffc107; color: #212529; }
    .btn-banner-gold:hover { background: #ffca2c; transform: translateY(-2px); color: #000; }

    /* BARRA DE FILTROS (RÉPLICA EXACTA) */
    .filter-bar {
        background: white; border-radius: 12px; padding: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 30px;
        display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
    }
    .search-group { flex-grow: 1; min-width: 250px; position: relative; }
    .search-input {
        width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 10px 10px 40px;
        font-size: 0.95rem; color: #495057; background-color: #fff; transition: 0.2s;
    }
    .search-input:focus { border-color: #86b7fe; outline: 0; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem; }

    .filter-select {
        border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 35px 10px 15px;
        font-size: 0.95rem; color: #495057; background-color: #fff; cursor: pointer;
        min-width: 200px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px;
    }

    /* TARJETA PRODUCTO (RÉPLICA VISUAL) */
    .card-prod {
        background: white; border-radius: 12px; border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s;
        height: 100%; position: relative; overflow: hidden;
        display: flex; flex-direction: column;
    }
    .card-prod:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }

    /* IMAGEN AMPLIA */
    .img-area {
        height: 240px; /* MÁS GRANDE */
        padding: 20px;
        display: flex; align-items: center; justify-content: center;
        background: white; border-bottom: 1px solid #f8f9fa;
        position: relative; cursor: pointer;
    }
    .prod-img { max-height: 100%; max-width: 100%; object-fit: contain; transition: 0.3s; }
    .card-prod:hover .prod-img { transform: scale(1.05); }

    /* BADGES SUPERIORES */
    .badge-top-left { position: absolute; top: 15px; left: 15px; z-index: 10; }
    .badge-offer { background: #dc3545; color: white; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 5px rgba(220,53,69,0.3); }

    /* CUERPO DE TARJETA */
    .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
    
    .cat-label { font-size: 0.7rem; color: #adb5bd; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
    .prod-title { font-size: 1.1rem; font-weight: 700; color: #212529; margin-bottom: 5px; line-height: 1.3; }
    .prod-code { font-size: 0.8rem; color: #6c757d; margin-bottom: 15px; }

    /* PRECIOS */
    .price-block { margin-bottom: 15px; }
    .price-old { text-decoration: line-through; color: #adb5bd; font-size: 0.9rem; }
    .price-main { font-size: 1.8rem; font-weight: 800; color: #dc3545; line-height: 1; letter-spacing: -0.5px; }
    .price-normal { font-size: 1.8rem; font-weight: 800; color: #212529; line-height: 1; letter-spacing: -0.5px; }

    /* CAJA DE COSTOS (GRIS CLARO) */
    .financial-box {
        background: #f8f9fa; border-radius: 8px; padding: 10px 15px;
        display: flex; justify-content: space-between; align-items: flex-end;
        margin-bottom: 10px;
    }
    .cost-label { font-size: 0.75rem; color: #6c757d; display: block; margin-bottom: 2px; }
    .cost-val { font-size: 0.95rem; font-weight: 700; color: #212529; }
    .gain-label { font-size: 0.75rem; color: #198754; display: block; margin-bottom: 2px; text-align: right; }
    .gain-val { font-size: 0.95rem; font-weight: 700; color: #198754; }

    /* BARRA PROGRESO */
    .stock-progress { height: 6px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
    .progress-fill { height: 100%; border-radius: 10px; }
    .bg-blue-custom { background-color: #0d6efd; }

    /* FOOTER ACCIONES */
    .card-footer-actions {
        display: flex; justify-content: space-between; align-items: center; margin-top: auto;
    }
    
    /* SWITCH ESTILO IOS */
    .form-switch .form-check-input {
        width: 2.5em; height: 1.25em; cursor: pointer; border: none; background-color: #e9ecef;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
    }
    .form-switch .form-check-input:checked { background-color: #0d6efd; }

    /* BOTONES ICONO */
    .btn-icon-action {
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
        border-radius: 6px; border: 1px solid transparent; background: transparent; transition: 0.2s;
        color: #6c757d; font-size: 1.1rem;
    }
    .btn-edit:hover { border-color: #ffc107; color: #ffc107; background: #fffbf0; }
    .btn-del:hover { border-color: #dc3545; color: #dc3545; background: #fff5f5; }
    /* STICKY RESPONSIVE: Solo se pega en Desktop/Tablet, no en Celular */
    @media (min-width: 768px) {
        .sticky-desktop {
            position: -webkit-sticky;
            position: sticky;
            top: 70px;
            z-index: 90;
        }
    }
</style>

<div class="header-blue">
    <i class="bi bi-grid-3x3-gap-fill bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold mb-0">Catálogo de Productos</h2>
                <p class="opacity-75 mb-0">Administración de stock y precios</p>
            </div>
            <div class="d-flex gap-2">
                <a href="combos.php" class="btn-banner btn-banner-gold">
                    <i class="bi bi-box-seam-fill"></i> Combos
                </a>
                <a href="producto_formulario.php" class="btn-banner btn-banner-light">
                    <i class="bi bi-plus-lg"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="verTodos()" style="cursor: pointer;">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Productos</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $total_prod; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarStockBajo()" style="cursor: pointer;" id="widget-stock-bajo">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Stock Bajo</h6>
                        <h2 class="mb-0 fw-bold <?php echo ($bajo_stock > 0) ? 'text-danger' : 'text-dark'; ?>"><?php echo $bajo_stock; ?></h2>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Valor Stock (Costo)</h6>
                        <h2 class="mb-0 fw-bold text-success">$<?php echo number_format($valor_inventario, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
   

    <div class="filter-bar sticky-desktop" style="border-top-left-radius: 0; border-top-right-radius: 0; border-top: 1px solid #eee;">
        <div class="search-group">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="buscador" class="search-input" placeholder="Buscar nombre, código...">
        </div>

        <select id="filtroCat" class="filter-select">
            <option value="todos">📦 Todas las Categorías</option>
            <?php foreach($categorias as $c): ?>
                <option value="<?php echo $c->id; ?>"><?php echo $c->nombre; ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filtroEstado" class="filter-select">
            <option value="todos">⚡ Ver Todo</option>
            <option value="activos">✅ Solo Activos</option>
            <option value="pausados">⏸️ Pausados / Inactivos</option>
            <option value="bajo_stock">⚠️ Stock Bajo</option>
            <option value="vencimientos">📅 Por Vencer</option>
        </select>

        <select id="ordenarPor" class="filter-select">
            <option value="recientes">📅 Recientes</option>
            <option value="nombre_asc">A-Z Nombre</option>
            <option value="precio_alto">💲 Mayor Precio</option>
            <option value="precio_bajo">💲 Menor Precio</option>
        </select>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <small class="text-muted fw-bold"><span id="contadorVisible"><?php echo count($productos); ?></span> productos encontrados</small>
    </div>

    <div class="row g-4" id="gridProductos">
        <?php foreach($productos as $p): 
            $img = !empty($p->imagen_url) ? $p->imagen_url : '';
            // Cálculos
            $stock = floatval($p->stock_actual);
            $min = floatval($p->stock_minimo);
            $max_ref = $min > 0 ? $min * 4 : 50; 
            $pct = ($max_ref > 0) ? ($stock / $max_ref) * 100 : 0;
            if($pct > 100) $pct = 100;
            
            // Color Barra
            $colorBarra = '#198754'; // Verde
            if($stock <= $min * 2) $colorBarra = '#ffc107'; // Amarillo
            if($stock <= $min) $colorBarra = '#dc3545'; // Rojo
            if($p->tipo === 'combo') $colorBarra = '#0d6efd'; // Azul Combo

            // Costos y Ganancia
            $precioVenta = !empty($p->precio_oferta) && $p->precio_oferta > 0 ? $p->precio_oferta : $p->precio_venta;
            $costo = floatval($p->precio_costo);
            // Lógica simple para costo combo si es 0 (suma simple no incluida para no sobrecargar, se puede agregar)
            $ganancia = $precioVenta - $costo;

            // Filtros Data
            $claseCard = $p->activo ? '' : 'opacity-50 grayscale';
            $estadoData = $p->activo ? 'activos' : 'pausados';
            if($stock <= $min && $p->tipo !== 'combo') $estadoData .= ' bajo_stock';
        ?>
        <?php 
            // Detectamos si es bajo stock para el filtro del banner
            $es_bajo_stock = ($stock <= $min && $p->tipo !== 'combo');
        ?>
        <div class="col-12 col-md-6 col-xl-3 item-grid <?php echo $es_bajo_stock ? 'row-bajo-stock' : ''; ?>"
             data-nombre="<?php echo strtolower($p->descripcion); ?>" 
             data-codigo="<?php echo strtolower($p->codigo_barras); ?>"
             data-cat="<?php echo $p->id_categoria; ?>"
             data-estado="<?php echo $estadoData; ?>"
             data-precio="<?php echo $p->precio_venta; ?>"
             data-id="<?php echo $p->id; ?>">

            <div class="card-prod <?php echo $claseCard; ?>">
                
                <div class="badge-top-left">
                    <?php if(!empty($p->precio_oferta) && $p->precio_oferta > 0): ?>
                        <div class="badge-offer"><i class="bi bi-fire"></i> OFERTA</div>
                    <?php endif; ?>
                    <?php if($stock <= $min && $p->tipo !== 'combo'): ?>
                        <div class="badge bg-warning text-dark mt-1 shadow-sm" style="font-size:0.7rem; font-weight:700;"><i class="bi bi-exclamation-triangle-fill"></i> AGOTÁNDOSE</div>
                    <?php endif; ?>
                </div>

                <div class="img-area" onclick="abrirCamara(<?php echo $p->id; ?>)">
                    <?php if($img): ?>
                        <img src="<?php echo $img; ?>" class="prod-img" id="img-<?php echo $p->id; ?>">
                    <?php else: ?>
                        <i class="bi bi-camera text-muted fs-1 opacity-25"></i>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <div class="cat-label"><?php echo $p->cat ?? 'SIN CATEGORÍA'; ?></div>
                    <div class="prod-title text-truncate-2" title="<?php echo $p->descripcion; ?>">
                        <?php echo $p->descripcion; ?>
                    </div>
                    <?php if($p->tipo === 'combo'): ?>
                        <div class="prod-code text-primary fw-bold">COMBO-<?php echo $p->codigo_barras; ?></div>
                    <?php else: ?>
                        <div class="prod-code"><?php echo $p->codigo_barras; ?></div>
                    <?php endif; ?>

                    <div class="price-block">
                        <?php if(!empty($p->precio_oferta) && $p->precio_oferta > 0): ?>
                            <div class="price-old">$<?php echo number_format($p->precio_venta, 0, ',', '.'); ?></div>
                            <div class="price-main">$<?php echo number_format($p->precio_oferta, 0, ',', '.'); ?></div>
                        <?php else: ?>
                            <div class="price-normal">$<?php echo number_format($p->precio_venta, 0, ',', '.'); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-auto">
                        <div class="text-end mb-1">
                             <span style="font-size:0.85rem; font-weight:700; color:<?php echo $colorBarra; ?>;">
                                 <?php echo $stock; ?> u.
                             </span>
                         </div>

                        <div class="financial-box">
                            <div>
                                <span class="cost-label">Costo</span>
                                <span class="cost-val">$<?php echo number_format($costo, 0, ',', '.'); ?></span>
                            </div>
                            <div>
                                <span class="gain-label">Ganancia</span>
                                <span class="gain-val">$<?php echo number_format($ganancia, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="stock-progress">
                            <div class="progress-fill" style="width: <?php echo $pct; ?>%; background-color: <?php echo $colorBarra; ?>;"></div>
                        </div>

                        <div class="card-footer-actions">
                            <div class="form-check form-switch m-0" title="Activar / Desactivar">
                                <input class="form-check-input" type="checkbox" 
                                       onchange="window.location.href='productos.php?toggle_id=<?php echo $p->id; ?>&estado=<?php echo $p->activo; ?>'" 
                                       <?php echo $p->activo ? 'checked' : ''; ?>>
                            </div>
                            
                            <div class="d-flex gap-1">
                                <a href="producto_formulario.php?id=<?php echo $p->id; ?>" class="btn-icon-action btn-edit" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="productos.php?borrar=<?php echo $p->id; ?>" class="btn-icon-action btn-del" onclick="return confirm('¿Eliminar producto?')" title="Eliminar">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div id="noResults" class="text-center py-5 d-none">
        <h5 class="text-muted">No se encontraron productos</h5>
    </div>
</div>

<input type="file" id="inputImageRapido" accept="image/*" hidden>
<div class="modal fade" id="modalCropRapido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0"><div style="max-height:500px;"><img id="imageToCropRapido" style="max-width:100%;"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" id="btnGuardarFotoRapida">Guardar</button></div>
        </div>
    </div>
</div>

<script>
    // 1. FILTRADO (Javascript puro, rápido)
    const buscador = document.getElementById('buscador');
    const filtroCat = document.getElementById('filtroCat');
    const filtroEst = document.getElementById('filtroEstado');
    const orden = document.getElementById('ordenarPor');
    const grid = document.getElementById('gridProductos');
    const noRes = document.getElementById('noResults');
    const counter = document.getElementById('contadorVisible');

    function aplicarFiltros() {
        let txt = buscador.value.toLowerCase();
        let cat = filtroCat.value;
        let est = filtroEst.value;
        let sort = orden.value;
        
        let items = Array.from(document.querySelectorAll('.item-grid'));
        let visibles = 0;

        items.forEach(item => {
            let iNombre = item.dataset.nombre;
            let iCodigo = item.dataset.codigo;
            let iCat = item.dataset.cat;
            let iEst = item.dataset.estado; 

            let cumpleTxt = (iNombre.includes(txt) || iCodigo.includes(txt));
            let cumpleCat = (cat === 'todos' || iCat === cat);
            let cumpleEst = (est === 'todos' || iEst.includes(est));

            if(cumpleTxt && cumpleCat && cumpleEst) {
                item.classList.remove('d-none');
                visibles++;
            } else {
                item.classList.add('d-none');
            }
        });

        // Ordenamiento simple (DOM Reordering)
        items.sort((a, b) => {
            if(sort === 'nombre_asc') return a.dataset.nombre.localeCompare(b.dataset.nombre);
            if(sort === 'precio_alto') return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
            if(sort === 'precio_bajo') return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
            return parseInt(b.dataset.id) - parseInt(a.dataset.id); // Recientes
        });
        items.forEach(item => grid.appendChild(item));

        counter.innerText = visibles;
        if(visibles === 0) noRes.classList.remove('d-none');
        else noRes.classList.add('d-none');
    }

    buscador.addEventListener('keyup', aplicarFiltros);
    filtroCat.addEventListener('change', aplicarFiltros);
    filtroEst.addEventListener('change', aplicarFiltros);
    orden.addEventListener('change', aplicarFiltros);

    // 2. FOTO RÁPIDA
    let currentId = null;
    let cropper;
    const inputImg = document.getElementById('inputImageRapido');
    const modalEl = document.getElementById('modalCropRapido');
    const imgCrop = document.getElementById('imageToCropRapido');
    const modalObj = new bootstrap.Modal(modalEl);

    window.abrirCamara = function(id) { currentId = id; inputImg.click(); }
    inputImg.addEventListener('change', function(e) {
        if(e.target.files && e.target.files[0]) {
            imgCrop.src = URL.createObjectURL(e.target.files[0]);
            modalObj.show();
            inputImg.value = '';
        }
    });
    modalEl.addEventListener('shown.bs.modal', function() {
        cropper = new Cropper(imgCrop, { aspectRatio: 1, viewMode: 1, autoCropArea: 0.9 });
    });
    modalEl.addEventListener('hidden.bs.modal', function() { if(cropper) { cropper.destroy(); cropper = null; } });

    $('#btnGuardarFotoRapida').click(function() {
        if(!cropper) return;
        let canvas = cropper.getCroppedCanvas({ width: 800, height: 800 });
        $.post('acciones/subir_foto_rapida.php', {
            id_producto: currentId,
            imagen_base64: canvas.toDataURL('image/png')
        }, function(res) {
            if(res.status === 'success') {
                $('#img-' + currentId).attr('src', res.url + '?t=' + Date.now());
                modalObj.hide();
                Swal.fire({icon: 'success', title: 'Foto Actualizada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        }, 'json');
    });
    
</script>
<script>
    let filtroActivo = false;

    function filtrarStockBajo() {
        const filas = document.querySelectorAll('.item-grid'); // AHORA BUSCA LAS TARJETAS, NO FILAS
        const widget = document.getElementById('widget-stock-bajo');
        
        filtroActivo = !filtroActivo; // Alternar estado

        filas.forEach(fila => {
            if (filtroActivo) {
                // Si activamos el filtro, ocultamos las que NO tienen la clase stock-bajo
                if (!fila.classList.contains('row-bajo-stock')) {
                    fila.style.display = 'none';
                } else {
                    fila.style.display = '';
                }
            } else {
                // Si desactivamos, mostramos todo
                fila.style.display = '';
            }
        });

        // Feedback visual en el widget
        if(filtroActivo) {
            widget.style.border = "2px solid #ffc107";
            widget.style.backgroundColor = "#fff3cd";
        } else {
            widget.style.border = "none";
            widget.style.backgroundColor = "white";
        }
    }
    function verTodos() {
        // 1. Limpiar inputs de la barra de filtros
        document.getElementById('buscador').value = '';
        document.getElementById('filtroCat').value = 'todos';
        document.getElementById('filtroEstado').value = 'todos';
        
        // 2. Ejecutar el filtro principal (mostrará todo)
        aplicarFiltros();

        // 3. Si el filtro de "Stock Bajo" (Widget Amarillo) está activo, lo desactivamos
        if (typeof filtroActivo !== 'undefined' && filtroActivo) {
            filtrarStockBajo(); // Al llamarla de nuevo, se apaga sola
        }
    }
</script>

<?php require_once 'includes/layout_footer.php'; ?>