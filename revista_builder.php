<?php
// revista_builder.php - VERSIÓN FINAL: MOVER + REDIMENSIONAR + HOVER VISIBLE
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 1. PRODUCTOS
$sql = "SELECT * FROM productos WHERE activo = 1 ORDER BY descripcion ASC";
$productos = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 2. CONFIGURACIÓN
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);

// 3. DATOS SMART
$url_tienda = "federicogonzalez.net/kiosco/tienda";
$url_registro = "https://federicogonzalez.net/kiosco/registro_cliente.php";

// Logo fallback
$logo_url = !empty($conf['logo_url']) ? $conf['logo_url'] : 'https://cdn-icons-png.flaticon.com/512/3594/3594450.png';

$smart_data = [
    'nombre' => htmlspecialchars($conf['nombre_negocio'], ENT_QUOTES),
    'direccion' => htmlspecialchars($conf['direccion_local'] ?? $conf['direccion'] ?? 'Dirección', ENT_QUOTES),
    'whatsapp' => htmlspecialchars($conf['telefono_whatsapp'] ?? $conf['telefono'] ?? 'WhatsApp', ENT_QUOTES),
    'logo' => $logo_url,
    'url_tienda' => $url_tienda,
    'qr_img' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($url_registro)
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor - <?php echo $smart_data['nombre']; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700;900&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root { --yaguar-yellow: #ffc400; --yaguar-red: #d50000; --a4-w: 210mm; --a4-h: 296mm; }
        body { background-color: #e9ecef; font-family: 'Roboto Condensed', sans-serif; height: 100vh; overflow: hidden; user-select: none; }
        #app-container { display: flex; height: 100vh; }
        
        /* SIDEBAR */
        #sidebar { width: 340px; background: white; border-right: 1px solid #ccc; display: flex; flex-direction: column; z-index: 2000; }
        .nav-tabs .nav-link { border-radius: 0; color: #555; font-weight: bold; cursor: pointer; }
        .nav-tabs .nav-link.active { border-top: 4px solid var(--yaguar-red); color: var(--yaguar-red); background: #f8f9fa; }
        .tab-content { flex-grow: 1; overflow-y: auto; background: #f8f9fa; padding: 15px; }

        .tool-item { padding: 8px; background: #fff; border: 1px solid #ddd; margin-bottom: 5px; cursor: grab; display: flex; align-items: center; border-radius: 4px; font-size: 14px; color: #333; }
        .tool-item:hover { background: #f0f0f0; border-color: #bbb; }
        .tool-item img { width: 40px; height: 40px; object-fit: contain; margin-right: 10px; background: white; border: 1px solid #eee; display: block; }

        /* WORKSPACE */
        #workspace-container { flex-grow: 1; display: flex; flex-direction: column; background-color: #525659; position: relative; }
        #toolbar-top { height: 50px; background: #343a40; color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; }
        #canvas-scroll { flex-grow: 1; overflow: auto; display: flex; justify-content: center; padding: 50px; background-image: radial-gradient(#666 1px, transparent 1px); background-size: 20px 20px; }

        /* PAGINA A4 */
        .page-wrapper { margin-bottom: 50px; position: relative; }
        .page-header-ui { background: #fff; color: #333; padding: 5px 10px; font-size: 12px; display: flex; justify-content: space-between; width: var(--a4-w); margin: 0 auto; border: 1px solid #ccc; border-bottom: none; font-weight: bold; border-top-left-radius: 4px; border-top-right-radius: 4px; }
        .page { width: var(--a4-w); min-height: var(--a4-h); background: white; position: relative; overflow: visible; box-shadow: 0 5px 15px rgba(0,0,0,0.5); transform-origin: top center; margin: 0 auto; }
        .page::after { content: "--- LÍMITE DE IMPRESIÓN ---"; position: absolute; top: var(--a4-h); left: 0; width: 100%; border-bottom: 2px dashed red; color: red; font-size: 12px; text-align: right; pointer-events: none; z-index: 100; }

        /* ELEMENTOS FLOTANTES */
        .float-item { position: absolute; cursor: grab; border: 1px dashed transparent; z-index: 200; min-width: 20px; min-height: 20px; }
        
        /* HOVER VISIBLE (Punteado azul para saber qué vas a tocar) */
        .float-item:hover {
            outline: 2px dashed #0d6efd;
            cursor: move;
        }

        /* ESTADO "REDIMENSIONANDO" (Activado por menú) */
        .float-item.redimensionando { 
            border: 2px solid #0d6efd !important; 
            z-index: 5000; 
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
        }

        /* MANIJA DE REDIMENSIÓN */
        .resize-handle { 
            width: 16px; height: 16px; background: #0d6efd; 
            position: absolute; bottom: -8px; right: -8px; 
            cursor: se-resize; display: none; border-radius: 50%; 
            border: 2px solid white; z-index: 6000; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .float-item.redimensionando .resize-handle { display: block; }

        /* MENU CLICK DERECHO */
        #context-menu {
            position: fixed; display: none; background: #fff; color: #333;
            border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 9999; width: 220px; padding: 5px 0;
        }
        .ctx-item { padding: 8px 15px; cursor: pointer; display: flex; align-items: center; font-size: 14px; }
        .ctx-item:hover { background: #f0f0f0; }
        .ctx-divider { height: 1px; background: #eee; margin: 5px 0; }
        .ctx-header { padding: 5px 15px; font-size: 11px; color: #888; font-weight: bold; background: #f9f9f9; border-bottom: 1px solid #eee; }

        /* SECCIONES */
        .section-row { position: relative; width: 100%; border: 1px dashed transparent; min-height: 50px; transition: 0.1s; }
        .section-row:hover { border: 1px dashed #0d6efd; z-index: 10; }
        
        .row-resizer { position: absolute; bottom: -5px; left: 0; width: 100%; height: 15px; cursor: ns-resize; display: none; align-items: center; justify-content: center; z-index: 50; }
        .section-row:hover .row-resizer { display: flex; }
        .resizer-bar { width: 60px; height: 6px; background: #0d6efd; border-radius: 3px; border: 1px solid white; }

        .sec-tools { position: absolute; right: -30px; top: 0; width: 30px; display: none; flex-direction: column; gap: 2px; z-index: 100; }
        .section-row:hover .sec-tools { display: flex; }
        .btn-sec { width: 30px; height: 30px; background: white; border: 1px solid #ccc; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .btn-sec:hover { background: #eee; }
        .btn-sec.del { background: #dc3545; color: white; border-color: #dc3545; }

        .header-bg-tools { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.8); padding: 3px; border-radius: 4px; display: none; gap: 5px; z-index: 50; border: 1px solid #ccc; }
        .section-row:hover .header-bg-tools { display: flex; }

        /* ESTILOS INTERNOS */
        .sec-bg { width: 100%; height: 100%; position: absolute; top: 0; left: 0; z-index: 0; }
        .sec-grid { display: flex; flex-wrap: wrap; width: 100%; position: relative; z-index: 5; }
        .sec-col { border: 1px dashed #ddd; flex: 1; position: relative; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100px; }
        .sec-col:hover { background: #fcfcfc; }
        .sec-col.filled { border: none; background: white; }
        
        /* TARJETA PRODUCTO */
        .card-prod { width: 100%; height: 100%; padding: 5px; display: flex; flex-direction: column; pointer-events: none; align-items: center; position: relative; }
        .badge-off { position: absolute; top: 0; right: 0; background: var(--yaguar-red); color: white; font-size: 11px; font-weight: 900; padding: 2px 6px; z-index: 5; }
        .prod-img { flex: 1; width: auto; max-width: 95%; max-height: 120px; object-fit: contain; margin-bottom: 5px; }
        .prod-title { text-align: center; font-size: 13px; font-weight: 700; line-height: 1.1; margin-bottom: 5px; text-transform: uppercase; color: #333; }
        .prod-price-box { background: var(--yaguar-yellow); color: var(--yaguar-red); font-weight: 900; font-size: 1.6rem; transform: rotate(-2deg); padding: 0 8px; border-radius: 4px; box-shadow: 2px 2px 4px rgba(0,0,0,0.2); margin-bottom: 5px; }
        .btn-del-prod { position: absolute; top: 0; left: 0; background: red; color: white; border: none; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: none; z-index: 10; pointer-events: auto; }
        .sec-col:hover .btn-del-prod { display: block; }

        /* --- ESTO REEMPLAZA AL @media print ANTERIOR --- */

        /* 1. ESTO ELIMINA FECHA, HORA Y TÍTULO DEL NAVEGADOR */
        @page {
            size: A4;
            margin: 0mm; /* MÁRGENES CERO para que ocupe toda la hoja */
        }

        /* 2. AJUSTE PERFECTO A4 */
        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
                background-color: white;
            }

            /* OCULTAR MENÚS Y HERRAMIENTAS */
            .no-print, #sidebar, #toolbar-top, #context-menu, 
            .page-header-ui, .sec-tools, .header-bg-tools, 
            .row-resizer, .resize-handle, .btn-del-prod, 
            .action-bar, .float-item:hover { 
                display: none !important; 
            }

            /* EL CONTENEDOR SE VUELVE LA HOJA PRINCIPAL */
            #workspace-container { 
                position: fixed;
                top: 0;
                left: 0;
                margin: 0;
                padding: 0;
                width: 210mm;
                height: 297mm;
                background: white;
                z-index: 9999;
                overflow: hidden; /* Corta cualquier milímetro que sobre */
            }

            #canvas-scroll { 
                margin: 0; 
                padding: 0; 
                width: 100%; 
                height: 100%; 
                display: block;
                overflow: hidden;
            }

            .page-wrapper { 
                margin: 0; 
                width: 100%;
                height: 100%;
            }

            .page { 
                width: 210mm !important;
                height: 297mm !important; 
                margin: 0;
                box-shadow: none; 
                border: none;
                transform: none !important; /* Quita el zoom de pantalla */
            }

            /* BORRAR LÍNEA ROJA Y BORDES DE EDICIÓN */
            .page::after { display: none !important; } 
            .section-row, .sec-col, .float-item { border: none !important; outline: none !important; }
            
            /* FORZAR COLORES EXACTOS (IMPORTANTE PARA EL FONDO AMARILLO) */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div id="context-menu">
    <div class="ctx-header">ACCIONES</div>
    <div class="ctx-item" onclick="menuAction('resize_mode')">
        <i class="bi bi-arrows-angle-expand text-primary me-2"></i> <strong>Redimensionar / Mover</strong>
    </div>
    <div class="ctx-divider"></div>
    <div class="ctx-item ctx-text-only" onclick="menuAction('edit_text')"><i class="bi bi-pencil me-2"></i> Editar Texto</div>
    <div class="ctx-item ctx-text-only" onclick="menuAction('color')"><i class="bi bi-palette me-2"></i> Color <input type="color" id="ctx-color" style="opacity:0; width:1px; position:absolute;"></div>
    <div class="ctx-divider"></div>
    <div class="ctx-item" onclick="menuAction('delete')"><i class="bi bi-trash text-danger me-2"></i> Eliminar</div>
</div>

<div id="app-container">
    <div id="sidebar" class="no-print">
        <div class="p-3 bg-light border-bottom"><h5 class="m-0 fw-bold text-dark">CONSTRUCTOR</h5></div>
        <ul class="nav nav-tabs nav-fill">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-struct">Bloques</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-decor">Decorar</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-prods">Productos</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-struct">
                <p class="text-muted small mt-2 fw-bold">PRESETS</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-dark text-start" onclick="addPreset('header')"><i class="bi bi-window-dock"></i> Header</button>
                    <button class="btn btn-outline-dark text-start" onclick="addPreset('footer')"><i class="bi bi-window-sidebar"></i> Footer</button>
                </div>
                <p class="text-muted small mt-3 fw-bold">GRILLAS</p>
                <div class="row g-2">
                    <div class="col-6"><button class="btn btn-light border w-100" onclick="addSection('grid', 2)">2 Cols</button></div>
                    <div class="col-6"><button class="btn btn-light border w-100" onclick="addSection('grid', 3)">3 Cols</button></div>
                    <div class="col-6"><button class="btn btn-light border w-100" onclick="addSection('grid', 4)">4 Cols</button></div>
                    <div class="col-6"><button class="btn btn-light border w-100" onclick="addSection('grid', 1)">1 Banner</button></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-decor">
                <div class="alert alert-info small p-2 mb-2">
                    <small>Clic derecho para opciones.</small>
                </div>
                
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'text', 'TÍTULO', '3rem')"><i class="bi bi-type-h1 me-2"></i> Título</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'text', 'Texto', '1rem')"><i class="bi bi-fonts me-2"></i> Texto</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'sticker', '¡OFERTA!')"><i class="bi bi-stars text-danger me-2"></i> Sticker</div>

                <hr>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'smart_data', 'direccion')"><i class="bi bi-geo-alt text-danger me-2"></i> Dirección</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'smart_data', 'whatsapp')"><i class="bi bi-whatsapp text-success me-2"></i> WhatsApp</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'smart_data', 'web')"><i class="bi bi-globe text-primary me-2"></i> Link Tienda</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'smart_qr')"><i class="bi bi-qr-code me-2"></i> QR Registro</div>
                <div class="tool-item" draggable="true" ondragstart="dragStart(event, 'smart_logo')"><i class="bi bi-image me-2"></i> Logo</div>
                <hr>
                <input type="file" class="form-control form-control-sm" accept="image/*" onchange="uploadImg(this)">
            </div>

            <div class="tab-pane fade" id="tab-prods">
                <input type="text" class="form-control mb-2" placeholder="Buscar..." onkeyup="filterProds(this)">
                <div id="prods-list">
                    <?php foreach($productos as $p): 
                        // FIX: Precios
                        $precioFloat = (float)$p['precio_venta'];
                        $precioDisplay = number_format($precioFloat, 0, ',', '.');
                    ?>
                    <div class="tool-item prod-source" draggable="true" 
                         data-nombre="<?php echo htmlspecialchars($p['descripcion'], ENT_QUOTES); ?>"
                         data-precio="<?php echo $precioFloat; ?>"
                         data-img="<?php echo $p['imagen_url']; ?>"
                         ondragstart="dragProd(event)">
                        <img src="<?php echo $p['imagen_url'] ?: 'img/no-image.png'; ?>">
                        <div style="line-height:1.2; width:100%;">
                            <div class="small fw-bold text-truncate"><?php echo $p['descripcion']; ?></div>
                            <div class="text-danger small fw-bold">$<?php echo $precioDisplay; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="p-3 border-top">
            <button onclick="window.print()" class="btn btn-danger w-100 fw-bold shadow">DESCARGAR PDF</button>
        </div>
    </div>

    <div id="workspace-container" onclick="deselectAll(event)">
        <div id="toolbar-top" class="no-print">
            <div class="fw-bold">EDITOR VISUAL</div>
            <div>
                <button class="btn btn-sm btn-light border" onclick="zoom(-0.1)">-</button>
                <button class="btn btn-sm btn-light border" onclick="zoom(0.1)">+</button>
            </div>
            <button class="btn btn-sm btn-warning fw-bold text-dark border" onclick="addPage()">+ HOJA</button>
        </div>
        <div id="canvas-scroll">
            <div id="pages-container"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const SMART = <?php echo json_encode($smart_data); ?>;
    let currentZoom = 0.8;
    let activeElement = null;

    document.addEventListener("DOMContentLoaded", () => { addPage(); applyZoom(); });

    // --- CLICK DERECHO ---
    document.addEventListener('contextmenu', function(e) {
        const target = e.target.closest('.float-item, .card-prod');
        if (target) {
            e.preventDefault(); 
            showContext(e, target);
        }
    });

    const ctxMenu = document.getElementById('context-menu');
    const colorPicker = document.getElementById('ctx-color');

    function showContext(e, el) {
        activeElement = el;
        const isText = el.dataset.type === 'text' || el.classList.contains('card-prod');
        document.querySelectorAll('.ctx-text-only').forEach(i => i.style.display = (el.dataset.type === 'text') ? 'flex' : 'none');
        
        ctxMenu.style.display = 'block';
        ctxMenu.style.left = e.pageX + 'px';
        ctxMenu.style.top = e.pageY + 'px';
    }

    // --- ACCIONES MENÚ ---
    function menuAction(action) {
        if (!activeElement) return;
        ctxMenu.style.display = 'none';

        if (action === 'resize_mode') {
            document.querySelectorAll('.redimensionando').forEach(x => x.classList.remove('redimensionando'));
            activeElement.classList.add('redimensionando');
        }
        else if (action === 'delete') {
            if(confirm('¿Eliminar?')) activeElement.remove();
        } 
        else if (action === 'edit_text') {
            activeElement.contentEditable = true; activeElement.focus();
            activeElement.onblur = () => { activeElement.contentEditable = false; };
        }
        else if (action === 'color') {
            colorPicker.click();
            colorPicker.oninput = () => { activeElement.style.color = colorPicker.value; };
        }
    }

    function deselectAll(e) {
        // No deseleccionar si toco handle, menú o herramientas
        if (e && (e.target.closest('.resize-handle') || e.target.closest('#context-menu') || e.target.closest('.sec-tools') || e.target.closest('.header-bg-tools') || e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON')) return;
        
        // Si toco fondo, sacar modo redimensión
        if (e && !e.target.closest('.float-item')) {
            document.querySelectorAll('.redimensionando').forEach(x => x.classList.remove('redimensionando'));
        }
    }

    // --- ARRASTRAR (MOUSE) ---
    function initDrag(el) {
        let isDown = false, offX, offY;
        el.addEventListener('mousedown', (e) => {
            // PERMITIR ARRASTRE SIEMPRE, SALVO QUE TOQUE EL HANDLE
            if (e.target.classList.contains('resize-handle')) return;
            if (e.button !== 0 || el.isContentEditable) return; 
            
            isDown = true;
            offX = e.clientX - el.offsetLeft * currentZoom;
            offY = e.clientY - el.offsetTop * currentZoom;
        });
        
        window.addEventListener('mouseup', () => isDown = false);
        window.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            el.style.left = (e.clientX - offX) / currentZoom + 'px';
            el.style.top = (e.clientY - offY) / currentZoom + 'px';
        });

        // --- REDIMENSIÓN (HANDLE) ---
        const h = document.createElement('div'); h.className = 'resize-handle'; el.appendChild(h);
        
        h.addEventListener('mousedown', (e) => {
            e.stopPropagation(); e.preventDefault();
            let startX = e.clientX, startW = el.offsetWidth, startH = el.offsetHeight;
            
            // FIX: Heredar tamaño fuente para redimensionar
            if (el.dataset.type === 'text' || el.dataset.type === 'sticker') {
                const inner = el.querySelector('.inner-content > div') || el.querySelector('.inner-content');
                if (inner && inner.style.fontSize) {
                    el.style.fontSize = inner.style.fontSize;
                    inner.style.fontSize = 'inherit';
                }
            }
            
            let startFontSize = parseFloat(window.getComputedStyle(el).fontSize) || 16;

            const onMove = (ev) => {
                let diff = (ev.clientX - startX) / currentZoom;
                
                if(el.dataset.type === 'text' || el.dataset.type === 'sticker') {
                    let newSize = startFontSize + (diff / 3); 
                    if(newSize < 10) newSize = 10; 
                    el.style.fontSize = newSize + 'px';
                    el.style.width = 'auto'; 
                } else {
                    let newW = startW + diff;
                    if(newW < 20) newW = 20;
                    el.style.width = newW + 'px';
                    el.style.height = 'auto';
                }
            };
            
            const onUp = () => { 
                document.removeEventListener('mousemove', onMove); 
                document.removeEventListener('mouseup', onUp); 
            };
            document.addEventListener('mousemove', onMove); 
            document.addEventListener('mouseup', onUp);
        });
    }

    // --- PRESETS ---
    // --- PRESETS (HEADER/FOOTER) ---
    // --- PRESETS (HEADER/FOOTER) ---
    function addPreset(type) {
        const p = getLastPage();
        const div = document.createElement('div'); 
        div.className = 'section-row';
        
        // 1. SOLUCIÓN MARGEN: Si es header, empuja lo de abajo 20px
        if (type === 'header') {
            div.style.minHeight = '140px';
            div.style.marginBottom = '20px'; 
        } else {
            div.style.minHeight = '170px'; // Altura Footer
        }
        
        // Colores de fondo
        const bgColor = (type==='header'?'#ffc400':'#f1f1f1');
        const borderB = (type==='header'?'5px solid #d50000':'none');
        
        // 2. SOLUCIÓN DOBLE LÍNEA: Creamos la barra roja del footer como elemento aparte (limpio)
        let footerBar = '';
        if(type === 'footer') {
            footerBar = '<div style="position:absolute; top:0; left:0; width:100%; height:5px; background:#d50000; z-index:1;"></div>';
        }
        
        div.innerHTML = `
            ${footerBar}
            <div class="sec-bg" style="background:${bgColor}; border-bottom:${borderB}; width:100%; height:100%; position:absolute; top:0; left:0; z-index:0;"></div>
            <div class="sec-tools no-print">
                <button class="btn-sec" onclick="moveRow(this, -1)">▲</button><button class="btn-sec" onclick="moveRow(this, 1)">▼</button><button class="btn-sec del" onclick="this.closest('.section-row').remove()">X</button>
            </div>
            <div class="header-bg-tools no-print">
                <input type="color" onchange="this.closest('.section-row').querySelector('.sec-bg').style.background=this.value;">
                <button class="btn-sec" onclick="this.nextElementSibling.click()">IMG</button><input type="file" style="display:none" accept="image/*" onchange="setBg(this)">
            </div>`;
            
        div.style.position = 'relative'; div.onclick = (e) => selectEl(e, div);

        // CONTENIDO DEL HEADER
        if(type === 'header') {
            createFloat(div, `<img src="${SMART.logo}" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.display='none'">`, 20, 20, 'img', '100px');
            createFloat(div, `<div style="font-size:40px; font-weight:900; color:#d50000; text-shadow:2px 2px 0 white;">${SMART.nombre}</div>`, 140, 30, 'text');
            createFloat(div, `<div style="font-size:20px; font-weight:bold; background:white; padding:2px 10px;">OFERTAS IMPERDIBLES</div>`, 140, 80, 'text');
        } 
        // CONTENIDO DEL FOOTER (Diseño Lindo)
        else if(type === 'footer') {
            createFloat(div, `<img src="${SMART.logo}" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.display='none'">`, 30, 35, 'img', '90px');
            createFloat(div, `<div style="font-size:18px; font-weight:900; color:#333; text-transform:uppercase;">CONTACTANOS</div>`, 140, 35, 'text');
            createFloat(div, `<div style="font-weight:bold; color:#555; font-size:13px;"><i class="bi bi-geo-alt-fill text-danger"></i> ${SMART.direccion}</div>`, 140, 65, 'text');
            createFloat(div, `<div style="font-weight:bold; color:green; font-size:13px;"><i class="bi bi-whatsapp"></i> ${SMART.whatsapp}</div>`, 140, 85, 'text');
            createFloat(div, `<div style="font-weight:bold; color:#0d6efd; font-size:13px; margin-top:5px;"><i class="bi bi-cart4"></i> Visita nuestra tienda online:<br><span style="color:#333; font-weight:normal;">${SMART.url_tienda}</span></div>`, 140, 110, 'text');
            createFloat(div, `<div style="background:#d50000; color:white; font-weight:900; font-size:14px; padding:8px 12px; text-align:center; transform:rotate(-3deg); box-shadow: 3px 3px 0px #333; border-radius:4px; line-height:1.2;">¡ESCANEÁ Y<br>SUMÁ PUNTOS! <i class="bi bi-arrow-right"></i></div>`, 500, 60, 'sticker');
            createFloat(div, `<img src="${SMART.qr_img}" style="width:100%; height:100%;">`, 660, 35, 'qr', '100px');
        }

        initRowResize(div); p.appendChild(div); div.scrollIntoView({behavior:'smooth'});
    }

    function addSection(type, cols) {
        if(type === 'grid') {
            const p = getLastPage();
            const div = document.createElement('div'); div.className = 'section-row';
            div.innerHTML = `<div class="sec-tools no-print"><button class="btn-sec del" onclick="this.closest('.section-row').remove()">X</button></div>`;
            const grid = document.createElement('div'); grid.className = 'sec-grid';
            for(let i=0; i<cols; i++) {
                const col = document.createElement('div'); col.className = 'sec-col'; col.style.width = (100/cols)+'%';
                col.ondrop = (ev) => dropProd(ev, col); col.ondragover = (ev) => ev.preventDefault();
                grid.appendChild(col);
            }
            div.appendChild(grid); initRowResize(div); p.appendChild(div); div.scrollIntoView({behavior:'smooth'});
        }
    }

    function createFloat(parent, html, x, y, type='text', width='auto') {
        const el = document.createElement('div'); el.className = 'float-item';
        el.style.left = x + 'px'; el.style.top = y + 'px'; el.style.width = width; el.dataset.type = type;
        const inner = document.createElement('div'); inner.className = 'inner-content'; inner.style.width = '100%'; inner.style.height = '100%';
        if (type === 'qr' || type === 'img') {
            if(html.startsWith('<img')) inner.innerHTML = html;
            else inner.innerHTML = `<img src="${html}" style="width:100%;height:100%;object-fit:contain;pointer-events:none;" onerror="this.onerror=null;this.src='https://cdn-icons-png.flaticon.com/512/3594/3594450.png'">`;
        }
        else inner.innerHTML = html;
        el.appendChild(inner); initDrag(el); parent.appendChild(el);
    }

    function dragStart(ev, type, val1, val2) {
        ev.dataTransfer.setData("type", type); ev.dataTransfer.setData("val1", val1); ev.dataTransfer.setData("val2", val2);
    }
    function dragProd(ev) {
        const t = ev.target.closest('.prod-source');
        ev.dataTransfer.setData("type", "prod");
        ev.dataTransfer.setData("data", JSON.stringify({
            name: t.dataset.nombre,
            price: t.dataset.precio,
            img: t.dataset.img
        }));
    }

    function dropOnPage(ev) {
        ev.preventDefault();
        const type = ev.dataTransfer.getData("type");
        if(type==='prod') return; 

        let target = ev.target;
        if(target.classList.contains('page')) {
             const s = document.createElement('div'); s.className='section-row'; s.style.height='100px'; 
             target.appendChild(s); target = s;
        }
        if(target.classList.contains('float-item') || target.classList.contains('inner-content') || target.tagName==='IMG') target = target.closest('.section-row') || target.parentElement;
        if(target.classList.contains('sec-bg')) target = target.parentElement;

        const rect = target.getBoundingClientRect();
        const x = (ev.clientX - rect.left) / currentZoom;
        const y = (ev.clientY - rect.top) / currentZoom;

        if(type === 'text') createFloat(target, `<div style="font-size:${ev.dataTransfer.getData('val2')||'16px'}; font-weight:bold;">${ev.dataTransfer.getData('val1')}</div>`, x, y, 'text');
        if(type === 'sticker') createFloat(target, `<div style="background:red; color:white; padding:5px; font-weight:900; transform:rotate(-5deg); text-align:center;">${ev.dataTransfer.getData('val1')}</div>`, x, y, 'sticker');

        if(type === 'smart_data') {
            const key = ev.dataTransfer.getData('val1');
            let html = '';
            if(key === 'direccion') html = `<div><i class="bi bi-geo-alt-fill text-danger"></i> ${SMART.direccion}</div>`;
            if(key === 'whatsapp') html = `<div><i class="bi bi-whatsapp text-success"></i> ${SMART.whatsapp}</div>`;
            if(key === 'web') html = `<div style="color:blue;">${SMART.url_tienda}</div>`;
            if(html) createFloat(target, `<div style="font-weight:bold; font-size:14px;">${html}</div>`, x, y, 'text');
        }
        
        if(type === 'smart_qr') createFloat(target, SMART.qr_img, x, y, 'qr', '100px');
        if(type === 'smart_logo') createFloat(target, SMART.logo, x, y, 'img', '100px');
    }

    function dropProd(ev, col) {
        ev.preventDefault(); ev.stopPropagation();
        if(ev.dataTransfer.getData("type") !== 'prod') return;
        
        const data = JSON.parse(ev.dataTransfer.getData("data"));
        let precio = parseFloat(data.price);
        if(isNaN(precio)) precio = 0;
        const precioF = precio.toLocaleString('es-AR');

        col.innerHTML = `
            <div class="card-prod">
                <div class="badge-off">OFERTA</div>
                <img src="${data.img}" class="prod-img">
                <div class="prod-title">${data.name}</div>
                <div class="prod-price-box">$${precioF}</div>
                <button class="btn-del-prod" onclick="this.closest('.sec-col').innerHTML=''; this.closest('.sec-col').classList.remove('filled')">X</button>
            </div>`;
        col.classList.add('filled');
    }

    function addPage() {
        const div = document.createElement('div'); div.className='page-wrapper';
        div.innerHTML = `<div class="page-header-ui"><span>HOJA A4</span><button class="btn btn-sm btn-danger py-0" onclick="this.closest('.page-wrapper').remove()">Eliminar</button></div><div class="page" ondrop="dropOnPage(event)" ondragover="event.preventDefault()"></div>`;
        document.getElementById('pages-container').appendChild(div);
        applyZoom();
    }
    function zoom(d) { currentZoom+=d; if(currentZoom<0.4) currentZoom=0.4; applyZoom(); }
    function applyZoom() { document.querySelectorAll('.page').forEach(p => p.style.transform = `scale(${currentZoom})`); document.querySelectorAll('.page-wrapper').forEach(w => w.style.height = (297 * currentZoom + 40) + 'mm'); }
    function filterProds(i) { document.querySelectorAll('.tool-item.prod-source').forEach(e => e.style.display = e.innerText.toLowerCase().includes(i.value.toLowerCase()) ? 'flex' : 'none'); }
    function uploadImg(i) {
        if(i.files[0]) {
            const r = new FileReader();
            const pages = document.querySelectorAll('.page');
            r.onload = (e) => createFloat(pages[pages.length-1].lastElementChild || pages[pages.length-1], `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:contain;">`, 50, 50, 'img', '150px');
            r.readAsDataURL(i.files[0]);
            i.value = '';
        }
    }
    function setBg(inp) {
        if (inp.files[0]) {
            const r = new FileReader();
            r.onload = (e) => { const t = inp.closest('.section-row').querySelector('.sec-bg'); t.style.backgroundImage = `url(${e.target.result})`; t.style.backgroundSize='cover'; };
            r.readAsDataURL(inp.files[0]);
        }
    }
    function getLastPage() { return document.querySelectorAll('.page')[document.querySelectorAll('.page').length-1]; }
    function initRowResize(row) {
        const resizer = document.createElement('div'); resizer.className = 'row-resizer'; resizer.innerHTML='<div class="resizer-bar"></div>'; row.appendChild(resizer);
        resizer.addEventListener('mousedown', (e) => {
            e.preventDefault(); e.stopPropagation();
            let startY = e.clientY, startH = row.offsetHeight;
            const onMove = (ev) => row.style.height = (startH + (ev.clientY - startY)/currentZoom) + 'px';
            const onUp = () => { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
            document.addEventListener('mousemove', onMove); document.addEventListener('mouseup', onUp);
        });
    }
    function moveRow(btn, dir) { 
        const r = btn.closest('.section-row'); 
        if(dir===-1 && r.previousElementSibling) r.parentNode.insertBefore(r, r.previousElementSibling);
        if(dir===1 && r.nextElementSibling) r.parentNode.insertBefore(r.nextElementSibling, r);
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#context-menu')) ctxMenu.style.display = 'none';
    });
</script>
</body>
</html>