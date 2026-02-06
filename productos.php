<?php
// productos.php - CATÁLOGO VISUAL CON MULTI-FILTROS EN TIEMPO REAL
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 1. LOGICA TOGGLE RÁPIDO (Estado)
if(isset($_GET['toggle_id'])) {
    $id_tog = $_GET['toggle_id'];
    $st_act = $_GET['estado']; 
    $nuevo = $st_act == 1 ? 0 : 1;
    $conexion->prepare("UPDATE productos SET activo = ? WHERE id = ?")->execute([$nuevo, $id_tog]);
    header("Location: productos.php"); exit;
}

// 2. PROCESAR BAJA (Archivar)
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    $stmt = $conexion->prepare("SELECT descripcion, codigo_barras FROM productos WHERE id = ?");
    $stmt->execute([$id_borrar]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if($prod) {
        $detalles = "Archivado/Eliminado: " . $prod['descripcion'];
        $conexion->prepare("INSERT INTO auditoria (fecha, id_usuario, accion, detalles) VALUES (NOW(), ?, 'BAJA_PRODUCTO', ?)")->execute([$_SESSION['usuario_id'], $detalles]);
    }
    // Baja lógica (activo = 0)
    $conexion->query("UPDATE productos SET activo=0 WHERE id=" . $id_borrar);
    header("Location: productos.php"); exit;
}

// 3. OBTENER DATOS (Traemos TODO para filtrar con JS)
$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();

// Consulta principal
$sql = "SELECT p.*, c.nombre as cat FROM productos p JOIN categorias c ON p.id_categoria=c.id ORDER BY p.id DESC";
$productos = $conexion->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* BARRA DE FILTROS FLOTANTE */
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid #eef0f3;
        }

        /* TARJETA DE PRODUCTO */
        .card-producto {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            height: 100%;
            position: relative;
        }
        
        .card-producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        /* ESTADO INACTIVO */
        .producto-inactivo {
            filter: grayscale(100%);
            opacity: 0.6;
        }
        .producto-inactivo:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
        .badge-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        /* IMAGEN */
        .img-wrapper {
            width: 100%;
            height: 220px;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f8f9fa;
            position: relative;
            padding: 15px;
        }
        .img-prod {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        .card-producto:hover .img-prod { transform: scale(1.08); }

        /* BOTON FLOTANTE DE CÁMARA */
        .btn-foto-rapida {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            opacity: 0;
            transition: all 0.2s;
            color: #0d6efd;
            cursor: pointer;
            z-index: 20;
        }
        .img-wrapper:hover .btn-foto-rapida { opacity: 1; }
        .btn-foto-rapida:hover { background: #0d6efd; color: white; transform: scale(1.1); }

        /* DATOS */
        .card-body { padding: 18px; }
        .precio-tag { font-size: 1.5rem; font-weight: 800; color: #212529; letter-spacing: -0.5px; }
        .cat-badge { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #adb5bd; letter-spacing: 0.8px; }
        
        /* STOCK BAR */
        .stock-track { height: 6px; background: #e9ecef; border-radius: 10px; margin-top: 12px; overflow: hidden; }
        .stock-fill { height: 100%; border-radius: 10px; transition: width 0.5s; }

        /* FOOTER TARJETA */
        .card-footer-custom {
            background: #fff;
            border-top: 1px solid #f8f9fa;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="bi bi-grid-fill text-primary"></i> Catálogo</h2>
                <span class="text-muted small">Administración Visual de Productos</span>
            </div>
            
            <a href="combos.php" class="btn btn-warning text-dark rounded-pill px-4 py-2 fw-bold shadow-sm me-2">
                <i class="bi bi-box-seam-fill me-1"></i> COMBOS
            </a>

            <a href="producto_formulario.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> NUEVO PRODUCTO
            </a>

            <a href="etiquetas_pdf.php" target="_blank" class="btn btn-info text-white rounded-pill px-4 py-2 fw-bold shadow-sm me-2">
                <i class="bi bi-printer-fill me-1"></i> ETIQUETAS
            </a>
        </div>

        <div class="filter-bar sticky-top" style="top: 10px; z-index: 100;">
            <div class="row g-3">
                
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" id="buscador" class="form-control bg-light border-start-0" placeholder="Buscar nombre, código...">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <select id="filtroCategoria" class="form-select bg-light">
                        <option value="todos">📦 Todas las Categorías</option>
                        <?php foreach($categorias as $c): ?>
                            <option value="<?php echo $c->id; ?>"><?php echo $c->nombre; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <select id="filtroEstado" class="form-select bg-light">
                        <option value="todos">⚡ Ver Todo</option>
                        <option value="vencimientos">📅 Próximos a Vencer</option> 
                        <option value="activos">✅ Solo Activos</option>
                        <option value="pausados">⏸️ Pausados / Inactivos</option>
                        <option value="bajo_stock">⚠️ Stock Bajo / Crítico</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <select id="ordenarPor" class="form-select bg-light">
                        <option value="recientes">📅 Recientes</option>
                        <option value="nombre_asc">Abc Nombre (A-Z)</option>
                        <option value="precio_alto">💲 Precio Mayor</option>
                        <option value="precio_bajo">💲 Precio Menor</option>
                        <option value="stock_bajo">📉 Menor Stock</option>
                    </select>
                </div>

            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <small class="text-muted fw-bold"><span id="contadorVisible"><?php echo count($productos); ?></span> productos encontrados</small>
            <small class="text-muted fst-italic">Mostrando resultados en tiempo real</small>
        </div>

        <div class="row g-3" id="gridProductos">
            
            <?php foreach($productos as $p): 
                // Preparar datos visuales
                $img = !empty($p->imagen_url) ? $p->imagen_url : 'img/producto_default.png';
                if(strpos($img, 'http') === false && !file_exists($img)) $img = 'https://via.placeholder.com/400x400?text=Sin+Imagen';

                // Cálculos de Stock
                $stock = floatval($p->stock_actual);
                $min = floatval($p->stock_minimo);
                $max_ref = $min * 5; 
                $pct = ($max_ref > 0) ? ($stock / $max_ref) * 100 : 0;
                if($pct > 100) $pct = 100;

                // Colores
                $colorStock = 'bg-success';
                // --- LOGICA VENCIMIENTO (AGREGAR ACA) ---
                $es_vencimiento = false;
                if(!empty($p->fecha_vencimiento)) {
                    $dias_alerta = 30; // Podes cambiar esto o leer de config si queres
                    $hoy = date('Y-m-d');
                    $limite = date('Y-m-d', strtotime("+$dias_alerta days"));
                    if($p->fecha_vencimiento >= $hoy && $p->fecha_vencimiento <= $limite && $p->activo) {
                        $es_vencimiento = true;
                    }
                }
                
                $txtStock = 'text-success';
                if($stock <= $min * 2) { $colorStock = 'bg-warning'; $txtStock = 'text-warning'; }
                if($stock <= $min) { $colorStock = 'bg-danger'; $txtStock = 'text-danger'; }

                // Clases Estado
                $claseCard = $p->activo ? '' : 'producto-inactivo';
                $estadoData = $p->activo ? 'activos' : 'pausados';
                if($stock <= $min) $estadoData .= ' bajo_stock';
                if(!empty($p->fecha_vencimiento)) {
                    // Usamos 30 días para coincidir con el dashboard
                    $fecha_limite = date('Y-m-d', strtotime("+30 days")); 
                    if($p->fecha_vencimiento >= date('Y-m-d') && $p->fecha_vencimiento <= $fecha_limite && $p->activo) {
                        $estadoData .= ' vencimientos'; // Esto es lo que lee el filtro
                    }
                }

                // --- NUEVO: Detectar Vencimientos (30 días) ---
                if(!empty($p->fecha_vencimiento)) {
                    $fecha_limite = date('Y-m-d', strtotime("+30 days"));
                    if($p->fecha_vencimiento >= date('Y-m-d') && $p->fecha_vencimiento <= $fecha_limite && $p->activo) {
                        $estadoData .= ' vencimientos';
                    }
                }
            ?>
            
            <div class="col-6 col-md-4 col-lg-3 item-grid" 
                 data-nombre="<?php echo strtolower($p->descripcion); ?>" 
                 data-codigo="<?php echo strtolower($p->codigo_barras); ?>"
                 data-cat="<?php echo $p->id_categoria; ?>"
                 data-estado="<?php echo $estadoData; ?>"
                 data-precio="<?php echo $p->precio_venta; ?>"
                 data-stock="<?php echo $stock; ?>"
                 data-id="<?php echo $p->id; ?>">

                <div class="card card-producto <?php echo $claseCard; ?>">
                    
                    <div class="badge-overlay">
                        <?php if(!$p->activo): ?>
                            <span class="badge bg-dark"><i class="bi bi-pause-fill"></i> PAUSADO</span>
                        <?php elseif($stock <= $min): ?>
                            <span class="badge bg-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> AGOTÁNDOSE</span>
                        <?php endif; ?>
                    </div>

                    <div class="img-wrapper">
                        <img src="<?php echo $img; ?>" class="img-prod" id="img-<?php echo $p->id; ?>" loading="lazy">
                        <div class="btn-foto-rapida" onclick="abrirCamara(<?php echo $p->id; ?>)" title="Cambiar Foto">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <div class="d-flex justify-content-between">
                            <div class="cat-badge"><?php echo $p->cat; ?></div>
                        </div>
                        
                        <h6 class="card-title text-truncate fw-bold mb-0 mt-1" title="<?php echo $p->descripcion; ?>">
                            <?php echo $p->descripcion; ?>
                        </h6>
                        <small class="text-muted font-monospace" style="font-size:0.75rem"><?php echo $p->codigo_barras; ?></small>

                        <div class="d-flex justify-content-between align-items-end mt-3">
                            <div class="precio-tag">$<?php echo number_format($p->precio_venta, 0, ',', '.'); ?></div>
                            <div class="text-end" style="font-size:0.8rem">
                                <span class="fw-bold <?php echo $txtStock; ?>"><?php echo $stock; ?> u.</span>
                            </div>
                        </div>
                        <?php if($es_vencimiento): ?>
                            <span class="badge bg-danger">Vence: <?php echo date('d/m', strtotime($p->fecha_vencimiento)); ?></span>
                        <?php endif; ?>    
                        <div class="stock-track" title="Nivel de Stock">
                            <div class="stock-fill <?php echo $colorStock; ?>" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </div>

                    <div class="card-footer-custom mt-3">
                        <div class="form-check form-switch" title="Activar / Desactivar">
                            <input class="form-check-input" type="checkbox" 
                                   onchange="window.location.href='productos.php?toggle_id=<?php echo $p->id; ?>&estado=<?php echo $p->activo; ?>'" 
                                   <?php echo $p->activo ? 'checked' : ''; ?>>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="producto_formulario.php?id=<?php echo $p->id; ?>" class="btn btn-sm btn-outline-secondary border-0" title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <a href="productos.php?borrar=<?php echo $p->id; ?>" class="btn btn-sm btn-outline-danger border-0" 
                               onclick="return confirm('¿Seguro quieres ocultar/archivar este producto?')" title="Eliminar">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div id="noResults" class="text-center py-5 d-none">
            <div class="mb-3"><i class="bi bi-emoji-frown display-1 text-muted opacity-25"></i></div>
            <h5 class="text-muted fw-bold">No encontramos productos</h5>
            <p class="text-muted">Intenta cambiar los filtros o la búsqueda.</p>
            <button class="btn btn-outline-primary rounded-pill" onclick="limpiarFiltros()">Limpiar Filtros</button>
        </div>

    </div>

    <input type="file" id="inputImageRapido" accept="image/png, image/jpeg, image/jpg" hidden>
    <div class="modal fade" id="modalCropRapido" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Recortar Imagen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 text-center bg-secondary">
                    <div style="max-height: 500px; display: block;">
                        <img id="imageToCropRapido" src="" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btnGuardarFotoRapida">GUARDAR</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // --- 1. LÓGICA DE FILTRADO MULTIPLE ---
        const buscador = document.getElementById('buscador');
        const filtroCat = document.getElementById('filtroCategoria');
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

            // 1. FILTRAR
            items.forEach(item => {
                let iNombre = item.dataset.nombre;
                let iCodigo = item.dataset.codigo;
                let iCat = item.dataset.cat;
                let iEst = item.dataset.estado; // string: "activos bajo_stock"

                let cumpleTxt = (iNombre.includes(txt) || iCodigo.includes(txt));
                let cumpleCat = (cat === 'todos' || iCat === cat);
                
                let cumpleEst = true;
                if(est !== 'todos') {
                    // Si el filtro es activos/pausados/bajo_stock, verificamos si esa palabra está en el dataset
                    if(!iEst.includes(est)) cumpleEst = false;
                }

                if(cumpleTxt && cumpleCat && cumpleEst) {
                    item.classList.remove('d-none');
                    visibles++;
                } else {
                    item.classList.add('d-none');
                }
            });

            // 2. ORDENAR (Solo los visibles o todos, visualmente se reordenan en el DOM)
            // Para mejor performance, ordenamos todos los items y los re-apendamos
            items.sort((a, b) => {
                let valA, valB;
                switch(sort) {
                    case 'nombre_asc':
                        return a.dataset.nombre.localeCompare(b.dataset.nombre);
                    case 'precio_alto':
                        return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
                    case 'precio_bajo':
                        return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
                    case 'stock_bajo':
                        return parseFloat(a.dataset.stock) - parseFloat(b.dataset.stock);
                    case 'recientes':
                    default:
                        // Asumimos que el ID más alto es más reciente (descendente)
                        return parseInt(b.dataset.id) - parseInt(a.dataset.id);
                }
            });

            // Re-inyectar en orden
            items.forEach(item => grid.appendChild(item));

            // UI
            counter.innerText = visibles;
            if(visibles === 0) noRes.classList.remove('d-none');
            else noRes.classList.add('d-none');
        }

        // Event Listeners para Filtros
        buscador.addEventListener('keyup', aplicarFiltros);
        filtroCat.addEventListener('change', aplicarFiltros);
        filtroEst.addEventListener('change', aplicarFiltros);
        orden.addEventListener('change', aplicarFiltros);

        function limpiarFiltros() {
            buscador.value = '';
            filtroCat.value = 'todos';
            filtroEst.value = 'todos';
            orden.value = 'recientes';
            aplicarFiltros();
        }

        // --- 2. LÓGICA DE CÁMARA RÁPIDA (IGUAL QUE ANTES) ---
        let currentId = null;
        let cropper;
        const inputImg = document.getElementById('inputImageRapido');
        const modalEl = document.getElementById('modalCropRapido');
        const imgCrop = document.getElementById('imageToCropRapido');
        const modalObj = new bootstrap.Modal(modalEl);

        window.abrirCamara = function(id) {
            currentId = id;
            inputImg.click();
        }

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

        modalEl.addEventListener('hidden.bs.modal', function() {
            if(cropper) { cropper.destroy(); cropper = null; }
        });

        $('#btnGuardarFotoRapida').click(function() {
            if(!cropper) return;
            let canvas = cropper.getCroppedCanvas({ width: 800, height: 800 });
            
            $.post('acciones/subir_foto_rapida.php', {
                id_producto: currentId,
                imagen_base64: canvas.toDataURL('image/png')
            }, function(res) {
                if(res.status === 'success') {
                    // Actualizar imagen con timestamp para evitar caché
                    $('#img-' + currentId).attr('src', res.url + '?t=' + Date.now());
                    modalObj.hide();
                    const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
                    Toast.fire({icon: 'success', title: 'Foto Actualizada'});
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // 1. Leer el parámetro ?filtro=... de la URL
            const params = new URLSearchParams(window.location.search);
            let filtro = params.get('filtro');

            if(filtro) {
                // 2. Corregir discrepancia: Dashboard manda 'stock_bajo', pero el Select usa 'bajo_stock'
                if(filtro === 'stock_bajo') filtro = 'bajo_stock';

                // 3. Seleccionar la opción en el menú y aplicar filtro
                const select = document.getElementById('filtroEstado');
                if(select && select.querySelector(`option[value="${filtro}"]`)) {
                    select.value = filtro;
                    aplicarFiltros(); // Ejecuta tu función existente
                }
            }
        });
    </script>
</body>
</html>