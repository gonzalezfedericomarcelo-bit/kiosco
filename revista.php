<?php
// revista.php - VISOR TIPO CATÁLOGO DE SUPERMERCADO
require_once 'includes/db.php';
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);

// Solo productos destacados o "en oferta"
$sql = "SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.activo = 1 AND p.es_destacado_web = 1 ORDER BY p.descripcion ASC";
$ofertas = $conexion->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revista de Ofertas - <?php echo $conf['nombre_negocio']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #212529; height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .revista-container { width: 100%; max-width: 500px; height: 90vh; background: white; border-radius: 20px; box-shadow: 0 0 50px rgba(0,0,0,0.5); position: relative; overflow: hidden; }
        .carousel, .carousel-inner, .carousel-item { height: 100%; }
        .slide-content { height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; text-align: center; }
        .img-oferta { max-height: 50%; max-width: 100%; object-fit: contain; margin-bottom: 20px; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.2)); }
        .precio-grande { font-size: 3.5rem; font-weight: 900; color: #dc3545; line-height: 1; }
        .precio-antes { text-decoration: line-through; color: #aaa; font-size: 1.2rem; }
        .badge-oferta { position: absolute; top: 20px; right: 20px; background: #ffc107; color: black; padding: 10px 20px; font-weight: bold; border-radius: 50px; transform: rotate(10deg); box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 10; font-size: 1.2rem; }
        .btn-cerrar { position: absolute; top: 20px; left: 20px; z-index: 100; color: #000; background: rgba(255,255,255,0.8); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .nav-control { width: 10%; background: none; }
        .nav-control i { font-size: 2rem; color: #000; background: rgba(255,255,255,0.5); border-radius: 50%; padding: 10px; }
    </style>
</head>
<body>

    <?php if(count($ofertas) > 0): ?>
    <div class="revista-container">
        <a href="tienda.php" class="btn-cerrar"><i class="bi bi-x-lg"></i></a>

        <div id="carruselRevista" class="carousel slide" data-bs-touch="true">
            <div class="carousel-inner">
                <?php foreach($ofertas as $index => $p): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="slide-content">
                        <span class="badge-oferta">OFERTA</span>
                        <h2 class="fw-bold mb-3"><?php echo $p->descripcion; ?></h2>
                        <img src="<?php echo $p->imagen_url ?: 'img/no-image.png'; ?>" class="img-oferta">
                        
                        <div class="mt-3">
                            <div class="precio-antes">Antes: $<?php echo number_format($p->precio_venta * 1.2, 0); ?></div>
                            <div class="precio-grande">$<?php echo number_format($p->precio_venta, 0); ?></div>
                        </div>

                        <div class="mt-4">
                            <span class="badge bg-secondary"><?php echo $p->categoria; ?></span>
                            <div class="mt-3 text-muted small">Deslizá para ver más 👉</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev nav-control" type="button" data-bs-target="#carruselRevista" data-bs-slide="prev">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="carousel-control-next nav-control" type="button" data-bs-target="#carruselRevista" data-bs-slide="next">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php else: ?>
        <div class="text-white text-center">
            <h1>😕 Ups!</h1>
            <p>No hay ofertas destacadas en la revista hoy.</p>
            <a href="tienda.php" class="btn btn-light mt-3">Ir a la Tienda</a>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>