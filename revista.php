<?php
// revista.php - VERSIÓN FINAL: IMÁGENES GRANDES + DISEÑO COMPACTO
ini_set('display_errors', 0);
error_reporting(0);
require_once 'includes/db.php';

// 1. CARGA DE DATOS
$conf_sis = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);
try {
    $stmt_rev = $conexion->query("SELECT * FROM revista_config WHERE id=1");
    $conf_rev = $stmt_rev ? $stmt_rev->fetch(PDO::FETCH_ASSOC) : [];
} catch(Exception $e) { $conf_rev = []; }

// Páginas especiales
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

// Mapa de Botones
$mapa_botones = [];
$contador_paginas = 3; 
foreach($productos_por_cat as $cat => $items) {
    $mapa_botones[$cat] = $contador_paginas;
    $chunks = array_chunk($items, 6);
    $contador_paginas += count($chunks);
    if(count($paginas_especiales) > 0) $contador_paginas++; 
}
$total_paginas = $contador_paginas;

// VISUALES
$nombre_negocio = $conf_sis['nombre_negocio'] ?? 'Kiosco';
$logo_url = $conf_sis['logo_url'] ?? ''; 
$direccion = $conf_sis['direccion'] ?? '';
$telefono = $conf_sis['telefono'] ?? '';

$img_tapa = !empty($conf_rev['img_tapa']) ? $conf_rev['img_tapa'] : 'https://via.placeholder.com/600x900/e60023/fff?text=TAPA';
$img_bienv = !empty($conf_rev['img_bienvenida']) ? $conf_rev['img_bienvenida'] : 'https://via.placeholder.com/600x450/333/fff?text=HOLA';

$tapa_ov = $conf_rev['tapa_overlay'] ?? '0.4';
$bienv_ov = $conf_rev['bienv_overlay'] ?? '0.0';
$fuente = $conf_rev['fuente_global'] ?? 'Poppins';
$bienv_bg = $conf_rev['bienv_bg_color'] ?? '#ffffff';
$tapa_banner_bg = $conf_rev['tapa_banner_color'] ?? '#ffffff';
$tapa_banner_op = $conf_rev['tapa_banner_opacity'] ?? '0.9';

$tit_tapa = $conf_rev['titulo_tapa'] ?? 'CATÁLOGO';
$sub_tapa = $conf_rev['subtitulo_tapa'] ?? 'INTERACTIVO';
$tapa_tit_col = $conf_rev['tapa_tit_color'] ?? '#ffde00';
$tapa_sub_col = $conf_rev['tapa_sub_color'] ?? '#ffffff';

$tit_bienv = $conf_rev['texto_bienvenida_titulo'] ?? '¡Hola Vecino!';
$txt_bienv = $conf_rev['texto_bienvenida_cuerpo'] ?? 'Mirá las ofertas.';
$bienv_tit_col = $conf_rev['bienv_tit_color'] ?? '#333';
$bienv_txt_col = $conf_rev['bienv_txt_color'] ?? '#555';

$colores_cat = ['#e60023', '#007bff', '#28a745', '#fd7e14', '#6610f2'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Revista - <?php echo $nombre_negocio; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Poppins:wght@300;400;700;900&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            margin: 0; padding: 0; background-color: #222; 
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); 
            height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; 
            font-family: '<?php echo $fuente; ?>', sans-serif; 
        }
        
        .book-wrapper { 
            box-shadow: 0 30px 60px rgba(0,0,0,0.8); 
            transition: transform 0.4s ease-in-out;
        }

        /* CENTRADO */
        .center-right { transform: translateX(-25%); } 
        .center-left { transform: translateX(25%); }   
        .center-normal { transform: translateX(0); }

        @media (max-width: 768px) {
            .center-right, .center-left, .center-normal { transform: translateX(0) !important; }
        }

        .page { 
            background-color: #fff !important; 
            border: 1px solid #ddd; 
            overflow: hidden; position: relative; 
            width: 100%; height: 100%;
            backface-visibility: hidden;
        }

        /* --- FILTROS IZQUIERDOS --- */
        .external-nav {
            position: fixed; left: 20px; top: 50%; transform: translateY(-50%);
            display: flex; flex-direction: column; gap: 8px;
            z-index: 9000; max-height: 80vh; overflow-y: auto;
            padding-right: 5px; scrollbar-width: none;
        }
        .external-nav::-webkit-scrollbar { display: none; }

        .nav-pill-btn {
            background: rgba(255,255,255,0.95); border: 1px solid #ccc;
            padding: 8px 15px; border-radius: 5px;
            cursor: pointer; font-size: 0.8rem; font-weight: bold;
            text-transform: uppercase; box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
            text-decoration: none; color: #333; display: block;
            transition: 0.2s; min-width: 120px;
        }
        .nav-pill-btn:hover { background: #ffde00; transform: translateX(5px); color: black; }
        .nav-pill-btn.btn-salir { background: #dc3545; color: white; border: none; margin-bottom: 10px; }
        .nav-pill-btn.btn-final { background: #333; color: white; margin-top: 10px; }

        /* --- FLECHAS (ABAJO) --- */
        .nav-arrow {
            position: fixed; bottom: 20px;
            width: 50px; height: 50px;
            background-color: #e60023; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; cursor: pointer; z-index: 9999;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); border: 2px solid white;
        }
        .nav-prev { left: calc(50% - 70px); }
        .nav-next { left: calc(50% + 20px); }
        
        @media (max-width: 768px) {
            .external-nav {
                left: 0; top: 0; bottom: auto; transform: none;
                width: 100%; flex-direction: row;
                background: #111; padding: 10px; gap: 10px;
                overflow-x: auto; justify-content: flex-start; 
            }
            .book-wrapper { margin-top: 60px; margin-bottom: 60px; }
            .nav-prev { left: 20px; bottom: 20px; }
            .nav-next { right: 20px; left: auto; bottom: 20px; }
        }

        /* --- TAPA & BIENVENIDA (Estilos previos ok) --- */
        .page-cover { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px; background-image: linear-gradient(rgba(0,0,0,<?php echo $tapa_ov; ?>), rgba(0,0,0,<?php echo $tapa_ov; ?>)), url('<?php echo $img_tapa; ?>'); background-size: cover; background-position: center; height: 100%; text-align: center; z-index: 100; background-color: #222; }
        .logo-box-container { display: inline-block; padding: 15px; border-radius: 15px; margin-bottom: 20px; background-color: <?php echo $tapa_banner_bg; ?>; <?php if($tapa_banner_op == '0') echo 'background-color: transparent;'; else echo "opacity: $tapa_banner_op;"; ?> box-shadow: 0 4px 10px rgba(0,0,0,0.3); max-width: 90%; }
        .cover-logo { max-height: 130px !important; width: auto !important; max-width: 100% !important; object-fit: contain; }
        .main-title { font-family: 'Bebas Neue'; font-size: 3.5rem; text-shadow: 2px 2px 0 #000; color: <?php echo $tapa_tit_col; ?>; line-height: 0.9; }
        .sub-title { font-size: 1.2rem; text-shadow: 2px 2px 0 #000; color: <?php echo $tapa_sub_col; ?>; font-weight: bold; text-transform: uppercase; }
        
        .d-none-custom { display: none !important; }
        .page-welcome { display: flex !important; flex-direction: column !important; height: 100%; width: 100%; margin: 0; padding: 0; }
        .welcome-top { flex: 0 0 50%; height: 50%; width: 100%; position: relative; overflow: hidden; }
        .welcome-img { height: 100%; width: 100%; object-fit: cover; }
        .welcome-bot { flex: 1; background-color: <?php echo $bienv_bg; ?>; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; text-align: center; }
        .welcome-tit { font-size: 2.2rem; font-weight: bold; margin-bottom: 5px; color: <?php echo $bienv_tit_col; ?>; line-height:1.1; }
        .welcome-txt { font-size: 1rem; color: <?php echo $bienv_txt_col; ?>; }

        /* --- NUEVO DISEÑO DE PRODUCTOS (MAXIMIZADO) --- */
        .cat-header { background: #333; color: white; padding: 5px; text-align: center; font-family: 'Anton'; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 2px; }
        
        .products-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; /* 2 columnas */
            grid-template-rows: 1fr 1fr 1fr; /* 3 filas */
            gap: 4px; /* Espacio mínimo */
            height: calc(100% - 40px); 
            padding: 4px; 
        }
        
        .prod-card { 
            border: 1px solid #eee; 
            border-radius: 4px; 
            padding: 0; /* Sin relleno para que la foto llegue al borde */
            display: flex; flex-direction: column; 
            background: white; 
            position: relative; 
            overflow: hidden;
        }

        /* 1. Imagen (70% de la tarjeta) */
        .prod-img-container {
            height: 70%; 
            width: 100%; 
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .prod-img-mini { 
            width: 100%; height: 100%; 
            object-fit: contain; /* Muestra toda la foto */
            padding: 2px;
        }

        /* 2. Info (30% de la tarjeta) */
        .prod-info {
            height: 30%;
            width: 100%;
            background: #fdfdfd;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            border-top: 1px solid #f0f0f0;
        }

        .prod-title-mini { 
            font-size: 0.7rem; font-weight: bold; text-align: center; 
            line-height: 1; margin-bottom: 2px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; /* Corta si es largo */
            width: 95%;
            color: #444;
        }
        
        .prod-price-mini { 
            font-family: 'Bebas Neue'; 
            font-size: 1.5rem; /* PRECIO GRANDE */
            color: #d00000; 
            line-height: 0.9;
        }
        
        .btn-add-mini { 
            font-size: 0.65rem; 
            background: #28a745; /* Verde compra */
            color: white; 
            width: 80%; 
            text-align: center; border-radius: 10px; 
            text-decoration: none; padding: 2px 0; 
            font-weight: bold;
        }

        /* Publicidad */
        .page-special img { width: 100%; height: 100%; object-fit: cover; }
        .page-special .btn-overlay { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); background: white; color: black; padding: 10px 20px; border-radius: 30px; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.5); text-decoration: none; }

        /* Contratapa */
        .page-back { background: #222; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px; }
        .qr-card { background: white; padding: 15px; border-radius: 20px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    
    <div class="external-nav">
        <a href="tienda.php" class="nav-pill-btn btn-salir">
            <i class="bi bi-x-circle"></i> SALIR
        </a>
        <div class="nav-pill-btn" onclick="externalFlip(0)">
            <i class="bi bi-book"></i> PORTADA
        </div>
        <?php foreach($mapa_botones as $cat => $pag): ?>
            <div class="nav-pill-btn" onclick="externalFlip(<?php echo $pag; ?>)">
                <?php echo $cat; ?>
            </div>
        <?php endforeach; ?>
        <div class="nav-pill-btn btn-final" onclick="goFinal()">
            FINAL <i class="bi bi-arrow-right"></i>
        </div>
    </div>

    <div class="nav-arrow nav-prev" id="btnP"><i class="bi bi-caret-left-fill"></i></div>
    <div class="nav-arrow nav-next" id="btnN"><i class="bi bi-caret-right-fill"></i></div>

    <div id="flipbook" class="book-wrapper">
        
        <div class="page page-cover" data-density="hard">
            <div class="logo-box-container">
                <?php if(!empty($logo_url)): ?>
                    <img src="<?php echo $logo_url; ?>" class="cover-logo" alt="Logo">
                <?php else: ?>
                    <i class="bi bi-shop" style="font-size: 4rem; color: #333;"></i>
                <?php endif; ?>
            </div>
            <div class="main-title"><?php echo $tit_tapa; ?></div>
            <div class="sub-title"><?php echo $sub_tapa; ?></div>
            <div style="margin-top: auto; color: white; font-size: 0.9rem;">
                <i class="bi bi-arrow-right-circle"></i> Abrir Catálogo
            </div>
        </div>

        <div class="page page-welcome">
            <div id="welcome-container" style="width:100%; height:100%; display:flex; flex-direction:column;" class="d-none-custom">
                <div class="welcome-top">
                    <img src="<?php echo $img_bienv; ?>" class="welcome-img">
                </div>
                <div class="welcome-bot">
                    <div class="welcome-tit"><?php echo $tit_bienv; ?></div>
                    <div class="welcome-txt"><?php echo $txt_bienv; ?></div>
                    <div class="mt-3 bg-white p-2 rounded text-dark d-inline-block shadow-sm">
                        <?php if($direccion): ?><div><i class="bi bi-geo-alt text-danger"></i> <?php echo $direccion; ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="page page-index" style="background:#f8f9fa; padding:20px; overflow-y:auto;">
            <div style="font-family:'Anton'; font-size:2rem; border-bottom:3px solid #333; margin-bottom:15px;">ÍNDICE</div>
            <?php foreach($mapa_botones as $cat => $pag): ?>
                <div onclick="externalFlip(<?php echo $pag; ?>)" style="padding:10px; border-bottom:1px solid #ddd; cursor:pointer; font-weight:bold; display:flex; justify-content:space-between;">
                    <span><?php echo $cat; ?></span><i class="bi bi-chevron-right"></i>
                </div>
            <?php endforeach; ?>
        </div>

        <?php 
        $cat_idx = 0;
        foreach($productos_por_cat as $cat_nombre => $items): 
            $color_actual = $colores_cat[$cat_idx % count($colores_cat)];
            $cat_idx++;
            $chunks = array_chunk($items, 6);
            foreach($chunks as $chunk):
        ?>
            <div class="page">
                <div class="cat-header" style="background-color: <?php echo $color_actual; ?>;">
                    <?php echo $cat_nombre; ?>
                </div>
                <div class="products-grid">
                    <?php foreach($chunk as $p): ?>
                        <div class="prod-card">
                            <div class="prod-img-container">
                                <img src="<?php echo $p['imagen_url'] ?: 'img/no-image.png'; ?>" class="prod-img-mini">
                            </div>
                            <div class="prod-info">
                                <div class="prod-price-mini">$<?php echo number_format($p['precio_venta'], 0); ?></div>
                                <div class="prod-title-mini"><?php echo $p['descripcion']; ?></div>
                                <a href="tienda.php?q=<?php echo urlencode($p['descripcion']); ?>" class="btn-add-mini">AGREGAR +</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php for($k=count($chunk); $k<6; $k++): ?><div style="visibility: hidden;"></div><?php endfor; ?>
                </div>
            </div>
        <?php 
            endforeach; 
            if(isset($paginas_especiales[$cat_idx-1])): 
                $ad = $paginas_especiales[$cat_idx-1];
        ?>
            <div class="page page-special">
                <img src="<?php echo $ad['imagen_url']; ?>">
                <?php if(!empty($ad['boton_texto'])): ?>
                    <a href="<?php echo $ad['boton_link']; ?>" class="btn-overlay"><?php echo $ad['boton_texto']; ?></a>
                <?php endif; ?>
            </div>
        <?php endif; endforeach; ?>

        <div class="page page-back" data-density="hard">
            <h1 style="font-family:'Anton'; font-size:3rem; color:#ffde00;">¡TE ESPERAMOS!</h1>
            <div class="qr-card">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>" style="width:120px;">
            </div>
            <div class="mt-3 text-white-50 small">
                <div style="font-size:1.2rem; font-weight:bold;"><?php echo $nombre_negocio; ?></div>
                <?php if($direccion): ?><div><?php echo $direccion; ?></div><?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/page-flip/dist/js/page-flip.browser.js"></script>
    <script>
        let pFlip; 
        function externalFlip(pageNum) { if(pFlip) pFlip.flip(parseInt(pageNum)); }
        function goFinal() { if(pFlip) pFlip.flip(pFlip.getPageCount() - 1); }

        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('flipbook');
            const wrapper = document.querySelector('.book-wrapper');
            const welcomeContent = document.getElementById('welcome-container');

            pFlip = new St.PageFlip(el, { 
                width: 420, height: 600, 
                size: 'stretch', minWidth: 300, maxWidth: 600, minHeight: 500, maxHeight: 900, 
                showCover: true, mobileScrollSupport: false 
            });
            
            pFlip.loadFromHTML(document.querySelectorAll('.page'));
            
            const handleFlip = (idx) => {
                const total = pFlip.getPageCount();

                // 1. VISIBILIDAD "HOLA VECINO"
                if (idx === 0 || idx >= total - 1) {
                    welcomeContent.classList.add('d-none-custom'); 
                } else {
                    setTimeout(() => welcomeContent.classList.remove('d-none-custom'), 100);
                }

                // 2. CENTRADO
                if(window.innerWidth >= 768) { 
                    if (idx === 0) {
                        wrapper.classList.add('center-right'); wrapper.classList.remove('center-left', 'center-normal');
                    } else if (idx >= total - 2) { 
                        wrapper.classList.add('center-left'); wrapper.classList.remove('center-right', 'center-normal');
                    } else {
                        wrapper.classList.add('center-normal'); wrapper.classList.remove('center-right', 'center-left');
                    }
                }
            };

            pFlip.on('flip', (e) => handleFlip(e.data));
            
            setTimeout(() => {
                handleFlip(0);
                welcomeContent.classList.add('d-none-custom'); 
            }, 50);

            document.getElementById('btnP').addEventListener('click', () => pFlip.flipPrev());
            document.getElementById('btnN').addEventListener('click', () => pFlip.flipNext());
            
            const rsz = () => { 
                if (window.innerWidth < 768) {
                    pFlip.update({ mode: 'portrait' }); 
                    wrapper.classList.remove('center-right', 'center-left');
                } else { 
                    pFlip.update({ mode: 'landscape' }); 
                    handleFlip(pFlip.getCurrentPageIndex());
                } 
            };
            window.addEventListener('resize', rsz); rsz();
        });
    </script>
</body>
</html>