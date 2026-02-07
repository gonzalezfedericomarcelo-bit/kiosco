<?php
// carteleria.php - CORREGIDO (Menú Funciona)
session_start();
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';

$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
if (!in_array('imprimir_carteles', $permisos) && $rol > 2) { header("Location: dashboard.php"); exit; }

$busqueda = $_GET['q'] ?? '';
$where = "activo = 1";
if($busqueda) $where .= " AND (descripcion LIKE '%$busqueda%' OR codigo_barras LIKE '%$busqueda%')";
$productos = $conexion->query("SELECT * FROM productos WHERE $where ORDER BY descripcion ASC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cartelería - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .etiqueta { border: 1px dashed #ccc; break-inside: avoid; }
        }
        .etiqueta {
            border: 2px solid #000; padding: 15px; margin-bottom: 20px;
            text-align: center; background: #fff; border-radius: 8px;
        }
        .precio-grande { font-size: 3rem; font-weight: 900; line-height: 1; color: #000; }
        .nombre-prod { font-size: 1.2rem; font-weight: bold; height: 3em; overflow: hidden; }
        .codigo { font-family: monospace; font-size: 0.9rem; letter-spacing: 2px; }
    </style>
</head>
<body class="bg-light">

    <div class="no-print">
        <?php 
        if(file_exists('menu.php')) include 'menu.php'; 
        elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
        ?>
    </div>

    <div class="container mt-4">
        <div class="card shadow-sm mb-4 no-print border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="m-0 fw-bold text-primary"><i class="bi bi-printer"></i> Generador de Cartelería</h4>
                    <button onclick="window.print()" class="btn btn-success fw-bold btn-lg"><i class="bi bi-printer-fill"></i> IMPRIMIR AHORA</button>
                </div>
                
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="q" class="form-control" placeholder="Buscar productos para imprimir..." value="<?php echo $busqueda; ?>">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>
        </div>

        <div class="row" id="areaImpresion">
            <?php foreach($productos as $p): ?>
            <div class="col-md-4 col-6"> 
                <div class="etiqueta position-relative">
                    <div class="nombre-prod mb-2"><?php echo htmlspecialchars($p->descripcion); ?></div>
                    <div class="text-muted small mb-1">PRECIO DE CONTADO</div>
                    <div class="precio-grande">$<?php echo number_format($p->precio_venta, 0); ?></div>
                    <div class="codigo mt-2"><?php echo $p->codigo_barras; ?></div>
                    <div class="mt-2 text-center opacity-50"><i class="bi bi-qr-code-scan fs-3"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(empty($productos)): ?>
            <div class="alert alert-info text-center no-print">Usa el buscador para encontrar productos.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>