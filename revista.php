<?php
// revista.php - VERSIÓN FINAL (FIX BOTÓN CELULAR + FOTOS CHICAS)
ini_set('display_errors', 0);
error_reporting(0);
require_once 'includes/db.php';

// 1. DATA
$conf_sis = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
try {
    $stmt_rev = $conexion->query("SELECT * FROM revista_config WHERE id=1");
    $conf_rev = $stmt_rev ? $stmt_rev->fetch(PDO::FETCH_ASSOC) : [];
} catch(Exception $e) { $conf_rev = []; }

// Ads
$paginas_especiales = [];
try {
    $stmt_esp = $conexion->query("SELECT * FROM revista_paginas WHERE activa=1 ORDER BY posicion ASC");
    if($stmt_esp) {
        $rows = $stmt_esp->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r) { $paginas_especiales[] = $r; }
    }
} catch(Exception $e) {}

// 2. PRODUCTOS
$sql_prod = "SELECT p.*, c.nombre as categoria 
        FROM productos p 
        JOIN categorias c ON p.id_categoria = c.id 
        WHERE p.activo = 1 AND p.stock_actual > 0 
        ORDER BY c.nombre ASC, p.descripcion ASC";
$raw_productos = $conexion->query($sql_prod)->fetchAll(PDO::FETCH_ASSOC);

$productos_por_cat = [];
foreach ($raw_productos as $p) {
    $productos_por_cat[$p['categoria']][] = $p;
}

// Mapa
$mapa_botones = [];
$contador_paginas = 2; 
foreach($productos_por_cat as $cat => $items) {
    $mapa_botones[$cat] = $contador_paginas;
    $chunks = array_chunk($items, 4); 
    $contador_paginas += count($chunks);
    if(count($paginas_especiales) > 0) $contador_paginas++; 
}

// Visuales
$nombre_negocio = $conf_sis['nombre_negocio'] ?? 'Kiosco';
$logo_url = $conf_sis['logo_url'] ?? ''; 
$direccion = $conf_sis['direccion'] ?? '';
$telefono = $conf_sis['telefono'] ?? ''; 

$img_tapa = $conf_rev['img_tapa'];
$tapa_ov = str_replace(',', '.', $conf_rev['tapa_overlay'] ?? '0.4');
$fuente = $conf_rev['fuente_global'] ?? 'Poppins';
$tapa_banner_bg = $conf_rev['tapa_banner_color'] ?? '#ffffff';
$tapa_banner_op = $conf_rev['tapa_banner_opacity'] ?? '0.9';

$tit_tapa = $conf_rev['titulo_tapa'] ?? 'CATÁLOGO';
$sub_tapa = $conf_rev['subtitulo_tapa'] ?? 'INTERACTIVO';
$tapa_tit_col = $conf_rev['tapa_tit_color'] ?? '#ffde00';
$tapa_sub_col = $conf_rev['tapa_sub_color'] ?? '#ffffff';

$colores_cat = ['#e60023', '#007bff', '#28a745', '#fd7e14', '#6610f2', '#6f42c1'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Revista - <?php echo $nombre_negocio; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --dark-bg: #1a1a1a; --header-h: 60px; --footer-h: 70px; }
        
        body { 
            margin: 0; padding: 0; background-color: var(--dark-bg); 
            height: 100vh; width: 100vw; overflow: hidden;
            display: flex; flex-direction: column; 
            font-family: '<?php echo $fuente; ?>', sans-serif; 
        }

        /* HEADER */
        .top-bar-container {
            height: var(--header-h); flex-shrink: 0;
            background: rgba(0,0,0,0.95); border-bottom: 1px solid #333;
            display: flex; align-items: center; padding: 0 15px; gap: 10px;
            overflow-x: auto; white-space: nowrap; scrollbar-width: none;
            padding-top: env(safe-area-inset-top); 
            z-index: 1000;
        }
        .nav-pill {
            color: white; border: 1px solid #555; padding: 5px 15px; border-radius: 20px;
            font-size: 0.8rem; cursor: pointer; text-transform: uppercase;
        }
        .nav-pill.active { background: white; color: black; border-color: white; }
        .nav-pill.salir { border-color: #dc3545; color: #dc3545; }

        /* AREA CENTRAL */
        .stage-center {
            flex-grow: 1; 
            display: flex; align-items: center; justify-content: center;
            position: relative; width: 100%; overflow: hidden;
            padding: 10px 0;
        }

        @media (max-width: 768px) {
            .stage-center {
                align-items: flex-start !important; 
                padding-top: 15px !important; 
            }
        }
        
        .book-wrapper { box-shadow: 0 15px 40px rgba(0,0,0,0.6); }

        /* DOCK (BOTONES) - BAJADO A 20px */
        .dock-container {
            position: fixed; 
            bottom: calc(20px + env(safe-area-inset-bottom)); /* ANTES ERA 70px */
            left: 50%; transform: translateX(-50%);
            z-index: 9100;
            display: flex; align-items: center; gap: 30px;
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(10px);
            padding: 12px 35px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .dock-btn {
            background: none; border: none; color: white; font-size: 1.6rem;
            position: relative; transition: transform 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .dock-btn:active { transform: scale(0.8); }
        .dock-divider { width: 1px; height: 25px; background: rgba(255,255,255,0.3); }
        
        .cart-badge {
            position: absolute; top: -5px; right: -8px;
            background: #ffde00; color: black; font-size: 0.7rem; font-weight: 800;
            width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        /* PÁGINAS */
        .page { background: #fff !important; width: 100%; height: 100%; overflow: hidden; border: 1px solid #ccc; display: flex; flex-direction: column; }
        
        /* PORTADA */
        .page-cover { 
            background-color: #222 !important;
            /* Aquí imprimimos la ruta exacta de la base de datos + el time() para que se actualice si la cambias */
            background-image: linear-gradient(rgba(0,0,0,<?php echo $tapa_ov; ?>), rgba(0,0,0,<?php echo $tapa_ov; ?>)), url('<?php echo $img_tapa; ?>?v=<?php echo time(); ?>');
            background-position: center center !important;
            background-repeat: no-repeat !important;
            display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
        }
        .logo-box { 
            background: <?php echo $tapa_banner_bg; ?>; 
            opacity: <?php echo $tapa_banner_op; ?>; 
            padding: 20px; border-radius: 15px; margin-bottom: 20px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }
        .cover-title { 
            font-family: 'Bebas Neue'; font-size: 4rem; line-height: 0.9; 
            color: <?php echo $tapa_tit_col; ?>; 
            text-shadow: 3px 3px 0 rgba(0,0,0,0.8);
        }
        .cover-sub {
            font-size: 1.2rem; margin-top: 10px; font-weight: bold; 
            color: <?php echo $tapa_sub_col; ?>; 
            text-transform: uppercase; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        /* PRODUCTOS */
        .cat-header { height: 40px; line-height: 40px; text-align: center; background: #222; color: white; font-family: 'Anton'; font-size: 1.2rem; flex-shrink: 0; }

        /* GRILLA CORREGIDA PARA QUE NO SE ACHIQUEN FOTOS */
        .products-grid {
            flex-grow: 1; display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr; /* USAMOS 1fr PARA QUE SE REPARTA IGUAL */
            gap: 2px; padding: 2px;
        }

        .prod-card { 
            border: 1px solid #eee; display: flex; flex-direction: column; 
            position: relative; height: 100% !important; /* FORZAR ALTURA */
            overflow: hidden; background: white; 
        }
        
        .prod-img-box { 
            flex: 1; /* Ocupa todo el espacio vertical disponible */
            display: flex; align-items: center; justify-content: center; 
            padding: 10px; overflow: hidden; position: relative; 
        }
        .prod-img { 
            width: 100%; height: 100%; 
            object-fit: contain; /* Asegura que la foto se vea entera */
        }

        .prod-info-box { 
            height: 65px; background: #f9f9f9; border-top: 1px solid #eee; 
            padding: 4px; text-align: center; display: flex; flex-direction: column; justify-content: center; flex-shrink: 0; 
        }
        .prod-title { font-size: 0.75rem; font-weight: bold; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }
        .prod-price { font-family: 'Bebas Neue'; font-size: 1.5rem; color: #d63031; line-height: 1; }

        /* BOTÓN AGREGAR (FIX CLIC MÓVIL) */
        .btn-plus {
            position: absolute; top: 10px; right: 10px;
            width: 35px; height: 35px; border-radius: 50%;
            background: #28a745; color: white; border: none;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 500;
            cursor: pointer; font-size: 1.2rem;
        }
        /* Efecto al tocar */
        .btn-plus:active { transform: scale(0.9); background: #218838; }

        /* FLECHAS PC */
        .nav-arrow { position: absolute; top: 50%; transform: translateY(-50%); font-size: 2rem; color: white; cursor: pointer; z-index: 100; background: rgba(0,0,0,0.3); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .nav-prev { left: 30px; } .nav-next { right: 30px; }
        @media (max-width: 768px) { .nav-arrow { display: none; } }
        
        .modal-content { border-radius: 15px; }
        .cart-item { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .swal2-container { z-index: 100000 !important; }
    </style>
</head>
<body>

    <nav class="top-bar-container">
        <div class="nav-pill salir" onclick="window.location.href='tienda.php'">Salir</div>
        <div class="nav-pill" onclick="pFlip.flip(0)">Portada</div>
        <?php foreach($mapa_botones as $cat => $pag): ?>
            <div class="nav-pill" onclick="pFlip.flip(<?php echo $pag; ?>)"><?php echo $cat; ?></div>
        <?php endforeach; ?>
    </nav>

    <div class="stage-center">
        <div class="nav-arrow nav-prev" id="btnP"><i class="bi bi-chevron-left"></i></div>
        <div class="nav-arrow nav-next" id="btnN"><i class="bi bi-chevron-right"></i></div>

        <div id="flipbook" class="book-wrapper">
            
            <div class="page page-cover" data-density="hard" style="position: relative; background: #222; overflow: hidden;">
                
                <img src="<?php echo $img_tapa; ?>" 
                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; border: none;">
                
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,<?php echo str_replace(',','.',$tapa_ov); ?>); z-index: 1;"></div>

                <div style="position: relative; z-index: 2; width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <div class="logo-box">
                        <?php if(!empty($logo_url)): ?>
                            <img src="<?php echo $logo_url; ?>" style="max-height:80px;">
                        <?php else: ?>
                            <i class="bi bi-shop" style="font-size:3rem;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cover-title"><?php echo $tit_tapa; ?></div>
                    <div class="cover-sub"><?php echo $sub_tapa; ?></div>
                </div>
            </div>

            <div class="page" style="background:#eee; padding:20px; overflow-y:auto; display:block;">
                <h2 style="font-family:'Anton'; border-bottom:2px solid #333;">ÍNDICE</h2>
                <?php foreach($mapa_botones as $cat => $pag): ?>
                    <div onclick="pFlip.flip(<?php echo $pag; ?>)" style="padding:15px; background:white; margin-bottom:5px; border-radius:5px; cursor:pointer; font-weight:bold;">
                        <?php echo $cat; ?> <i class="bi bi-chevron-right float-end"></i>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php 
            $cat_idx = 0;
            foreach($productos_por_cat as $cat_nombre => $items): 
                $color_actual = $colores_cat[$cat_idx % count($colores_cat)];
                $cat_idx++;
                $chunks = array_chunk($items, 4); 
                foreach($chunks as $chunk):
            ?>
                <div class="page">
                    <div class="cat-header" style="background: <?php echo $color_actual; ?>;"><?php echo $cat_nombre; ?></div>
                    <div class="products-grid">
                        <?php foreach($chunk as $p): ?>
                            <div class="prod-card">
                                <button class="btn-plus" 
                                    ontouchstart="event.stopPropagation(); addCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['descripcion']); ?>', <?php echo $p['precio_venta']; ?>); return false;"
                                    onclick="event.stopPropagation(); addCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['descripcion']); ?>', <?php echo $p['precio_venta']; ?>);">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <div class="prod-img-box">
                                    <img src="<?php echo $p['imagen_url'] ?: 'img/no-image.png'; ?>" class="prod-img">
                                </div>
                                <div class="prod-info-box">
                                    <div class="prod-title"><?php echo $p['descripcion']; ?></div>
                                    <div class="prod-price">$<?php echo number_format($p['precio_venta'], 0); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php for($k=count($chunk); $k<4; $k++): ?>
                            <div class="prod-card" style="visibility:hidden; border:none; height:100%;"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; if(isset($paginas_especiales[$cat_idx-1])): $ad = $paginas_especiales[$cat_idx-1]; ?>
                <div class="page" style="background:black; justify-content:center; align-items:center;">
                    <img src="<?php echo $ad['imagen_url']; ?>" style="width:100%; height:100%; object-fit:cover;">
                </div>
            <?php endif; endforeach; ?>

            <div class="page page-cover" data-density="hard" style="background:#222 !important;">
                <h2 style="color:white; font-family:'Anton'; z-index:3;">¡GRACIAS!</h2>
                <div style="background:white; padding:10px; margin:20px; border-radius:10px; z-index:3;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>" width="120">
                </div>
                <div style="color:#aaa; z-index:3;"><?php echo $nombre_negocio; ?></div>
            </div>

        </div>
    </div>

    <div class="dock-container">
        <button class="dock-btn" onclick="share()"><i class="bi bi-share"></i></button>
        <div class="dock-divider"></div>
        <button class="dock-btn" onclick="openCart()">
            <i class="bi bi-cart4"></i>
            <span class="cart-badge" id="badgeCount">0</span>
        </button>
    </div>

    <div class="modal fade" id="modalC" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tu Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartList"></div>
                    <div class="d-flex justify-content-between mt-3 fw-bold fs-5 pt-3 border-top">
                        <span>Total:</span>
                        <span class="text-danger" id="cartTotal">$0</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-danger w-100" onclick="clearCart()">Vaciar</button>
                    <button class="btn btn-success w-100 fw-bold" onclick="sendWA()"><i class="bi bi-whatsapp"></i> ENVIAR</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/page-flip/dist/js/page-flip.browser.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let pFlip;
        let cart = [];
        let modalCart;

        function initBook() {
            const el = document.getElementById('flipbook');
            const wrapper = document.querySelector('.book-wrapper');
            const centerStage = document.querySelector('.stage-center');
            
            const availableH = centerStage.clientHeight - 20; 
            const availableW = centerStage.clientWidth - 20;

            let pageH = availableH;
            let pageW = (pageH * 0.7); 

            if (pageW > availableW) {
                pageW = availableW;
                pageH = pageW / 0.7;
            }

            // ESCRITORIO
            if(window.innerWidth > 768) {
                pageW = 450; 
                pageH = 650;
            }

            pFlip = new St.PageFlip(el, { 
                width: pageW, 
                height: pageH,
                size: 'fixed',
                minWidth: 300, maxWidth: 1000, 
                minHeight: 400, maxHeight: 1200,
                showCover: true, mobileScrollSupport: false 
            });

            pFlip.loadFromHTML(document.querySelectorAll('.page'));
            
            if(window.innerWidth > 768) wrapper.style.transform = 'translateX(0)';
        }

        // CARRO
        function initCart() {
            modalCart = new bootstrap.Modal(document.getElementById('modalC'));
            try {
                let r = localStorage.getItem('carrito_kiosco');
                if(r) {
                    cart = JSON.parse(r);
                    cart = cart.filter(i => i && !isNaN(parseFloat(i.precio)) && !isNaN(parseInt(i.cant)));
                }
            } catch(e) { cart=[]; localStorage.removeItem('carrito_kiosco'); }
            updBadge();
        }

        function addCart(id, n, p) {
            let ex = cart.find(i => i.id == id);
            if(ex) ex.cant++; else cart.push({id, nombre:n, precio:p, cant:1});
            save(); updBadge();
            // Toast muy visible
            Swal.fire({toast:true, position:'top', icon:'success', title:'¡Agregado!', timer:800, showConfirmButton:false, background:'#28a745', color:'#fff'});
        }

        function openCart() {
            let html = '', tot=0;
            if(cart.length==0) html='<p class="text-center text-muted">Vacío</p>';
            else {
                cart.forEach((i,x) => {
                    let s = i.precio*i.cant; tot+=s;
                    html += `<div class="cart-item">
                        <div style="flex:1"><div class="fw-bold small">${i.nombre}</div><div class="small text-muted">$${i.precio}</div></div>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-sm btn-light border" onclick="mod(${x},-1)">-</button>
                            <span class="fw-bold small">${i.cant}</span>
                            <button class="btn btn-sm btn-light border" onclick="mod(${x},1)">+</button>
                        </div>
                        <div class="fw-bold ms-3">$${s}</div>
                    </div>`;
                });
            }
            document.getElementById('cartList').innerHTML = html;
            document.getElementById('cartTotal').innerText = '$'+tot;
            modalCart.show();
        }

        function mod(x,d) {
            cart[x].cant+=d; if(cart[x].cant<=0) cart.splice(x,1);
            save(); updBadge(); openCart();
        }
        function clearCart() { cart=[]; save(); updBadge(); openCart(); }
        function save() { localStorage.setItem('carrito_kiosco', JSON.stringify(cart)); }
        function updBadge() { document.getElementById('badgeCount').innerText = cart.reduce((s,i)=>s+i.cant,0); }
        
        function sendWA() {
            if(cart.length==0) return;
            let msg="Pedido:%0A", tot=0;
            cart.forEach(i=>{ msg+=`- ${i.cant}x ${i.nombre}%0A`; tot+=i.precio*i.cant; });
            msg+=`*Total: $${tot}*`;
            window.open(`https://wa.me/<?php echo $telefono; ?>?text=${msg}`,'_blank');
        }
        function share() {
            if(navigator.share) navigator.share({url:window.location.href});
            else { navigator.clipboard.writeText(window.location.href); Swal.fire('Link copiado'); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initBook(); initCart();
            document.getElementById('btnP').addEventListener('click',()=>pFlip.flipPrev());
            document.getElementById('btnN').addEventListener('click',()=>pFlip.flipNext());
            window.addEventListener('resize', () => setTimeout(()=>{location.reload()}, 500));
        });
    </script>
</body>
</html>