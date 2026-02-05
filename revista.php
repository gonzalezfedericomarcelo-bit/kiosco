<?php
// revista.php - VERSIÓN DEFINITIVA (SOLUCIÓN PORTADA LIMPIA)
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
        foreach($rows as $r) { $paginas_especiales[$r['posicion']][] = $r; }
    }
} catch(Exception $e) {}

// Productos
$sql_prod = "SELECT p.*, c.nombre as categoria 
        FROM productos p 
        JOIN categorias c ON p.id_categoria = c.id 
        WHERE p.activo = 1 AND p.stock_actual > 0 
        ORDER BY c.nombre ASC, p.descripcion ASC";
$productos = $conexion->query($sql_prod)->fetchAll(PDO::FETCH_ASSOC);

// VARIABLES
$nombre_negocio = $conf_sis['nombre_negocio'] ?? 'Kiosco';
$logo_url = $conf_sis['logo_url'] ?? ''; 
$direccion = $conf_sis['direccion'] ?? '';
$telefono = $conf_sis['telefono'] ?? '';

// Estilos
$img_tapa = !empty($conf_rev['img_tapa']) ? $conf_rev['img_tapa'] : 'https://via.placeholder.com/600x900/e60023/fff?text=TAPA';
$img_bienv = !empty($conf_rev['img_bienvenida']) ? $conf_rev['img_bienvenida'] : 'https://via.placeholder.com/600x450/333/fff?text=HOLA';

$tapa_ov = $conf_rev['tapa_overlay'] ?? '0.4';
$bienv_ov = $conf_rev['bienv_overlay'] ?? '0.0';
$fuente = $conf_rev['fuente_global'] ?? 'Poppins';
$bienv_bg = $conf_rev['bienv_bg_color'] ?? '#ffffff';
$tapa_banner_bg = $conf_rev['tapa_banner_color'] ?? '#ffffff';
$tapa_banner_op = $conf_rev['tapa_banner_opacity'] ?? '0.9';

// Textos
$tit_tapa = $conf_rev['titulo_tapa'] ?? 'CATÁLOGO';
$sub_tapa = $conf_rev['subtitulo_tapa'] ?? 'INTERACTIVO';
$tit_bienv = $conf_rev['texto_bienvenida_titulo'] ?? '¡Hola Vecino!';
$txt_bienv = $conf_rev['texto_bienvenida_cuerpo'] ?? 'Mirá las ofertas.';
$bienv_tit_col = $conf_rev['bienv_tit_color'] ?? '#333';
$bienv_txt_col = $conf_rev['bienv_txt_color'] ?? '#555';

$estilos_pagina = ['style-oferta', 'style-fresh', 'style-impact', 'style-clean'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revista - <?php echo $nombre_negocio; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Poppins:wght@300;400;700;900&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* BASE */
        @keyframes float-icons { 0% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-15px) rotate(5deg); } 100% { transform: translateY(0) rotate(0deg); } }
        
        body { 
            margin: 0; padding: 0; background-color: #222; 
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); 
            height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; 
            font-family: '<?php echo $fuente; ?>', sans-serif; 
        }
        
        .book-wrapper { box-shadow: 0 20px 60px rgba(0,0,0,0.8); }
        
        /* REGLA DE ORO: Las páginas no pueden salirse de su contenedor */
        .page { 
            background-color: #fff; 
            border: 1px solid #ddd; 
            overflow: hidden; 
            position: relative; 
            width: 100%;
            height: 100%;
        }

        /* --- BOTÓN CERRAR ARREGLADO --- */
        .boton-salir-custom {
            position: fixed; top: 20px; left: 20px; 
            background-color: white; color: #333; 
            padding: 10px 25px; border-radius: 30px; 
            text-decoration: none; font-weight: bold; 
            z-index: 9999; box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border: 2px solid #eee; display: flex; align-items: center; gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .boton-salir-custom:hover { background-color: #f8f9fa; color: #000; }

        /* --- HOJA 1: PORTADA --- */
        .page-cover {
            display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;
            background-image: linear-gradient(rgba(0,0,0,<?php echo $tapa_ov; ?>), rgba(0,0,0,<?php echo $tapa_ov; ?>)), url('<?php echo $img_tapa; ?>');
            background-size: cover; background-position: center; 
            height: 100%; text-align: center;
        }
        
        .logo-box { 
            background-color: <?php echo $tapa_banner_bg; ?>; 
            <?php if($tapa_banner_op == '0') echo 'background-color: transparent;'; else echo "opacity: $tapa_banner_op;"; ?>
            padding: 20px; border-radius: 15px; margin-bottom: 20px; display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .cover-logo { max-height: 120px; max-width: 250px; object-fit: contain; }
        
        .main-title {
            font-family: 'Bebas Neue'; font-size: 4rem; line-height: 0.9;
            text-shadow: 3px 3px 0 #000; margin-top: 10px; text-transform: uppercase;
            color: <?php echo $conf_rev['tapa_tit_color'] ?? '#ffde00'; ?>;
        }
        .sub-title {
            font-size: 1.5rem; letter-spacing: 2px; text-shadow: 2px 2px 0 #000; margin-top: 5px; text-transform: uppercase;
            color: <?php echo $conf_rev['tapa_sub_color'] ?? '#ffffff'; ?>;
        }

        /* --- HOJA 2: BIENVENIDA (ARREGLADO: SIN !IMPORTANT QUE ROMPE LA PORTADA) --- */
        .page-welcome { 
            /* Quitamos el !important para que la librería pueda ocultarla si quiere */
            display: flex; 
            flex-direction: column; 
            height: 100%; 
            width: 100%; 
            margin: 0; padding: 0;
        }
        
        /* 50% IMAGEN */
        .welcome-top { 
            flex: 0 0 50%; height: 50%; width: 100%; 
            position: relative; overflow: hidden; 
        }
        .welcome-img { height: 100%; width: 100%; object-fit: cover; }
        .welcome-ov { position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,<?php echo $bienv_ov; ?>); }

        /* 50% TEXTO (FONDO CORRECTO) */
        .welcome-bot { 
            flex: 0 0 50%; height: 50%; width: 100%;
            background-color: <?php echo $bienv_bg; ?>; /* Tu color */
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 20px; text-align: center;
            box-sizing: border-box; 
        }
        
        .welcome-tit { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; color: <?php echo $bienv_tit_col; ?>; line-height:1.1; }
        .welcome-txt { font-size: 1rem; color: <?php echo $bienv_txt_col; ?>; }

        /* --- PRODUCTOS --- */
        .prod-layout { height: 100%; padding: 15px; display: flex; flex-direction: column; box-sizing: border-box; }
        .prod-img-box { flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .prod-img { max-height: 220px; max-width: 95%; object-fit: contain; }
        .btn-add { background: #222; color: white; width: 100%; padding: 10px; text-decoration: none; text-align: center; display: block; border-radius: 50px; font-weight: bold; }
        
        .page-special { background: #000; padding: 0; display: flex; flex-direction: column; position: relative; }
        .special-img-full { width: 100%; height: 100%; object-fit: cover; }
        .btn-special-overlay { position: absolute; bottom: 50px; left: 50%; transform: translateX(-50%); background: rgba(255,255,255,0.95); color: #e60023; padding: 10px 30px; border-radius: 50px; font-weight: 900; text-transform: uppercase; text-decoration: none; white-space: nowrap; }

        /* EXTRAS */
        .nav-btn { position: fixed; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 100; font-size: 1.5rem; color: #e60023; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .prev { left: 20px; } .next { right: 20px; }
        
        .style-oferta { background: radial-gradient(circle, #fff 40%, #fff0f0 100%); }
        .style-fresh { background: #f4fff4; }
        .style-impact { background: linear-gradient(180deg, #fff 70%, #e3f2fd 100%); }
        .style-clean { background: #fff; }
        
        .page-back { background: #222; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px; }
        .qr-card { background: white; padding: 15px; border-radius: 20px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    
    <a href="tienda.php" class="boton-salir-custom">
        <i class="bi bi-x-circle-fill"></i> CERRAR REVISTA
    </a>

    <div class="nav-btn prev" id="btnP"><i class="bi bi-chevron-left"></i></div>
    <div class="nav-btn next" id="btnN"><i class="bi bi-chevron-right"></i></div>

    <div id="flipbook">
        
        <div class="page page-cover" data-density="hard">
            <div class="logo-box">
                <?php if(!empty($logo_url)): ?>
                    <img src="<?php echo $logo_url; ?>" class="cover-logo" alt="Logo">
                <?php else: ?>
                    <i class="bi bi-shop" style="font-size: 4rem; color: #333;"></i>
                <?php endif; ?>
            </div>
            <div class="main-title"><?php echo $tit_tapa; ?></div>
            <div class="sub-title"><?php echo $sub_tapa; ?></div>
            
            <div style="margin-top: auto; background: rgba(0,0,0,0.5); padding: 8px 25px; border-radius: 50px; font-size: 0.9rem; color: white;">
                <i class="bi bi-hand-index-thumb-fill"></i> Arrastrá para abrir
            </div>
        </div>

        <div class="page page-welcome">
            <div class="welcome-top">
                <img src="<?php echo $img_bienv; ?>" class="welcome-img">
                <div class="welcome-ov"></div>
            </div>
            <div class="welcome-bot">
                <div class="welcome-tit"><?php echo $tit_bienv; ?></div>
                <div class="welcome-txt"><?php echo $txt_bienv; ?></div>
                <div class="mt-4 bg-white p-2 rounded shadow text-dark d-inline-block">
                    <?php if($direccion): ?><div><i class="bi bi-geo-alt text-danger"></i> <?php echo $direccion; ?></div><?php endif; ?>
                    <?php if($telefono): ?><div><i class="bi bi-whatsapp text-success"></i> <?php echo $telefono; ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <?php foreach($productos as $i => $p): ?>
            
            <?php if(isset($paginas_especiales[$i])): foreach($paginas_especiales[$i] as $ad): ?>
                <div class="page page-special">
                    <img src="<?php echo $ad['imagen_url']; ?>" class="special-img-full">
                    <?php if(!empty($ad['boton_texto'])): ?>
                        <a href="<?php echo $ad['boton_link']; ?>" class="btn-special-overlay"><?php echo $ad['boton_texto']; ?> <i class="bi bi-arrow-right-circle-fill"></i></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
            
            <div class="page <?php echo $estilos_pagina[$i % 4]; ?>">
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; overflow:hidden; z-index:0;">
                    <i class="bi bi-cup-hot-fill" style="position:absolute; top:10%; left:10%; opacity:0.05; font-size:3rem; animation: float-icons 8s infinite;"></i>
                    <i class="bi bi-basket-fill" style="position:absolute; bottom:15%; right:15%; opacity:0.05; font-size:2rem; animation: float-icons 6s infinite;"></i>
                </div>

                <div class="prod-layout">
                    <span class="badge bg-dark align-self-start shadow-sm"><?php echo $p['categoria']; ?></span>
                    <div class="prod-img-box"><img src="<?php echo $p['imagen_url']?:'img/no-image.png'; ?>" class="prod-img"></div>
                    <div style="text-align:center;">
                        <h5 style="font-weight:900; text-transform:uppercase; margin-bottom:5px; color:#222; line-height:1.1;"><?php echo $p['descripcion']; ?></h5>
                        <div style="font-family:'Bebas Neue'; font-size:2.5rem; margin-bottom:10px; color:#222;">$<?php echo number_format($p['precio_venta'], 0); ?></div>
                        <a href="tienda.php?q=<?php echo urlencode($p['descripcion']); ?>" class="btn-add">AGREGAR <i class="bi bi-cart"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="page page-cover" data-density="hard">
            <h1 class="main-title">¡TE ESPERAMOS!</h1>
            <div class="bg-white p-3 rounded mt-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>" width="120">
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/page-flip/dist/js/page-flip.browser.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('flipbook');
            const pFlip = new St.PageFlip(el, { 
                width: 450, height: 650, 
                size: 'stretch', 
                minWidth: 320, maxWidth: 600, 
                minHeight: 500, maxHeight: 900, 
                showCover: true, 
                mobileScrollSupport: false 
            });
            
            pFlip.loadFromHTML(document.querySelectorAll('.page'));
            
            document.getElementById('btnP').addEventListener('click', () => pFlip.flipPrev());
            document.getElementById('btnN').addEventListener('click', () => pFlip.flipNext());
            
            const rsz = () => { 
                if (window.innerWidth < 768) pFlip.update({ mode: 'portrait' }); 
                else pFlip.update({ mode: 'landscape' }); 
            };
            window.addEventListener('resize', rsz); rsz();
        });
    </script>
</body>
</html>