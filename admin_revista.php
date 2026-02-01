<?php
// admin_revista.php - VISTA PREVIA PROFESIONAL (ESTILO YAGUAR)
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// LOGICA: TOGGLE PRODUCTO
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $conexion->query("UPDATE productos SET es_destacado_web = NOT es_destacado_web WHERE id = $id");
    header("Location: admin_revista.php"); exit;
}

// OBTENER PRODUCTOS
$busqueda = $_GET['q'] ?? '';
$sql = "SELECT * FROM productos WHERE activo=1";
if($busqueda) $sql .= " AND (descripcion LIKE '%$busqueda%' OR codigo_barras LIKE '%$busqueda%')";
$sql .= " ORDER BY es_destacado_web DESC, descripcion ASC"; 
$productos = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// PRODUCTOS SELECCIONADOS
$seleccionados = array_filter($productos, function($p) { return $p['es_destacado_web'] == 1; });
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Armar Revista - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #e9ecef; height: 100vh; overflow: hidden; font-family: 'Roboto Condensed', sans-serif; }
        
        /* PANELES */
        .panel-izquierdo { height: 100vh; overflow-y: auto; background: white; border-right: 1px solid #ddd; z-index: 10; }
        .panel-derecho { height: 100vh; overflow-y: auto; background: #525659; display: flex; justify-content: center; padding: 40px; }
        
        /* HOJA A4 VIRTUAL (Exactamente igual al PDF) */
        .hoja-a4 {
            width: 210mm; min-height: 297mm; 
            background: white; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
            /* Transformación para que quepa en pantalla */
            transform: scale(0.85); transform-origin: top center;
        }

        /* DISEÑO YAGUAR */
        .header-yaguar { background: #ffc107; padding: 15px 10px; border-bottom: 4px solid #dc3545; text-align: center; }
        .titulo-pdf { font-size: 32pt; font-weight: 900; color: #dc3545; margin: 0; line-height: 1; text-transform: uppercase; letter-spacing: -1px; }
        .subtitulo-pdf { font-size: 14pt; font-weight: 700; color: #000; margin-top: 5px; }

        .grilla { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2mm; padding: 5mm; }
        
        .box-producto { 
            border: 1px solid #ccc; height: 78mm; padding: 5px; 
            display: flex; flex-direction: column; justify-content: space-between; 
            background: #fff; position: relative;
        }
        
        .badge-oferta { 
            position: absolute; top: 0; right: 0; 
            background: #dc3545; color: white; 
            font-size: 10pt; font-weight: bold; 
            padding: 2px 8px; border-bottom-left-radius: 5px;
        }

        .img-container { height: 35mm; display: flex; align-items: center; justify-content: center; margin-top: 5mm; }
        .img-prod { max-height: 100%; max-width: 95%; object-fit: contain; }

        .desc-prod { 
            text-align: center; font-size: 10pt; font-weight: 700; color: #333; 
            line-height: 1.1; height: 2.2em; overflow: hidden; margin-top: 5px; 
            text-transform: uppercase;
        }

        .precios-container { text-align: center; margin-bottom: 5px; }
        .precio-antes { font-size: 10pt; color: #888; text-decoration: line-through; }
        .precio-final { font-size: 26pt; font-weight: 900; color: #dc3545; line-height: 1; letter-spacing: -1px; }
        .unidad { font-size: 10pt; color: #dc3545; font-weight: bold; }

        .footer-legal { position: absolute; bottom: 5mm; width: 100%; text-align: center; font-size: 8pt; color: #999; }

        /* LISTA IZQUIERDA */
        .item-lista { cursor: pointer; border-left: 4px solid transparent; transition: 0.2s; }
        .item-lista:hover { background-color: #f8f9fa; }
        .item-lista.active { border-left-color: #ffc107; background-color: #fff3cd; }
    </style>
</head>
<body>
    <div class="row g-0">
        <div class="col-md-4 panel-izquierdo d-flex flex-column p-0">
            <div class="p-3 bg-white border-bottom shadow-sm z-1">
                <h5 class="fw-bold text-danger mb-3"><i class="bi bi-magic"></i> Constructor de Ofertas</h5>
                <form class="d-flex gap-2">
                    <input type="text" name="q" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            
            <div class="flex-grow-1 overflow-auto">
                <?php foreach($productos as $p): 
                    $act = $p['es_destacado_web'] ? 'active' : '';
                    $chk = $p['es_destacado_web'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted';
                ?>
                <a href="admin_revista.php?toggle=<?php echo $p['id']; ?>" class="d-block text-decoration-none text-dark border-bottom p-2 item-lista <?php echo $act; ?>">
                    <div class="d-flex align-items-center">
                        <div style="width:50px; height:50px; background:#fff; display:flex; align-items:center; justify-content:center; border:1px solid #eee; margin-right:10px;">
                            <img src="<?php echo $p['imagen_url'] ?: 'img/no-image.png'; ?>" style="max-width:40px; max-height:40px;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small text-truncate" style="max-width: 200px;"><?php echo $p['descripcion']; ?></div>
                            <div class="text-danger fw-bold">$<?php echo number_format($p['precio_venta'],0,',','.'); ?></div>
                        </div>
                        <i class="bi <?php echo $chk; ?> fs-4 mx-2"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="p-3 bg-light border-top">
                <form action="exportar_revista.php" method="POST" target="_blank">
                    <div class="mb-2">
                        <label class="small fw-bold text-muted">TÍTULO PRINCIPAL</label>
                        <input type="text" name="titulo" class="form-control fw-bold" value="OFERTAS DESTACADAS" onkeyup="updateTitle(this.value)">
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold text-muted">SUBTÍTULO / FECHA</label>
                        <input type="text" name="vigencia" class="form-control" value="Válido hasta agotar stock" onkeyup="updateSub(this.value)">
                    </div>
                    <button class="btn btn-danger w-100 py-2 fw-bold shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill"></i> DESCARGAR PDF
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-8 panel-derecho">
            <div class="hoja-a4">
                <div class="header-yaguar">
                    <h1 class="titulo-pdf" id="live-title">OFERTAS DESTACADAS</h1>
                    <div class="subtitulo-pdf" id="live-sub">Válido hasta agotar stock | <?php echo strtoupper($_SESSION['nombre_negocio'] ?? 'KIOSCO'); ?></div>
                </div>

                <div class="grilla">
                    <?php if(count($seleccionados) == 0): ?>
                        <div class="col-12 text-center py-5" style="grid-column: 1 / -1;">
                            <h2 class="text-muted opacity-25 fw-bold">NO HAY PRODUCTOS</h2>
                            <p class="text-muted">Seleccioná de la lista izquierda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($seleccionados as $p): ?>
                        <div class="box-producto">
                            <div class="badge-oferta">OFERTA</div>
                            <div class="img-container">
                                <img src="<?php echo $p['imagen_url'] ?: 'img/no-image.png'; ?>" class="img-prod">
                            </div>
                            <div class="desc-prod"><?php echo $p['descripcion']; ?></div>
                            <div class="precios-container">
                                <div class="precio-antes">Antes: $<?php echo number_format($p['precio_venta']*1.2, 0, ',', '.'); ?></div>
                                <div class="precio-final">
                                    <span class="unidad">$</span><?php echo number_format($p['precio_venta'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="text-center small text-muted">COD: <?php echo $p['codigo_barras']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="footer-legal">Las fotos son ilustrativas. Precios sujetos a cambios sin previo aviso.</div>
            </div>
        </div>
    </div>

    <script>
        function updateTitle(val) { document.getElementById('live-title').innerText = val.toUpperCase(); }
        function updateSub(val) { document.getElementById('live-sub').innerText = val + " | " + "<?php echo strtoupper($_SESSION['nombre_negocio']??''); ?>"; }
    </script>
</body>
</html>