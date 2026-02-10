<?php
// gestionar_premios.php - LIMPIO (USA TUS INCLUDES NATIVOS)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$mensaje = '';

// 3. AGREGAR PREMIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    try {
        $nombre = $_POST['nombre'];
        $puntos = $_POST['puntos'];
        $stock = $_POST['stock'];
        $es_cupon = isset($_POST['es_cupon']) ? 1 : 0;
        $monto = $_POST['monto_dinero'] ?? 0;
        
        $conexion->prepare("INSERT INTO premios (nombre, puntos_necesarios, stock, es_cupon, monto_dinero, activo) VALUES (?, ?, ?, ?, ?, 1)")
                 ->execute([$nombre, $puntos, $stock, $es_cupon, $monto]);
        
        header("Location: gestionar_premios.php?msg=creado"); exit;
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

// 4. BORRAR
if (isset($_GET['borrar'])) {
    $conexion->prepare("DELETE FROM premios WHERE id = ?")->execute([$_GET['borrar']]);
    header("Location: gestionar_premios.php?msg=borrado"); exit;
}

// 5. LISTAR
try {
    $lista = $conexion->query("SELECT * FROM premios ORDER BY puntos_necesarios ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total_premios = count($lista);
    $fisicos = 0; $cupones = 0;
    foreach($lista as $p) { if($p['es_cupon'] == 1) $cupones++; else $fisicos++; }
} catch (Exception $e) { $lista = []; $total_premios = 0; $fisicos = 0; $cupones = 0; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Premios y Fidelización</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ESTILOS DEL DISEÑO (BANNER, WIDGETS, BADGES) */
        
        /* Ajuste crítico para que el menú no quede atrás del banner */
        .header-blue {
            background-color: #102A57;
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
            position: relative;
            overflow: visible; /* IMPORTANTE: Para que no corte menús si se superponen */
            z-index: 1;
        }
        
        .bg-icon-large {
            position: absolute; top: 50%; right: 20px;
            transform: translateY(-50%) rotate(-10deg);
            font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
            z-index: 0;
        }
        
        .stat-card {
            border: none; border-radius: 15px; padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
            background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card:hover { transform: translateY(-3px); }
        
        /* Iconos cuadrados de colores */
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }

        /* BADGE AMARILLO CON TEXTO NEGRO (SOLUCIÓN VISUAL) */
        .badge-puntos { 
            background-color: #ffc107 !important; 
            color: #000000 !important; 
            border: 1px solid #e0a800; 
            font-weight: bold;
        }

        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        /* Ajustes menores de formulario */
        .form-control-lg { font-size: 1rem; padding: 0.75rem 1rem; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/layout_header.php'; ?>

    <div class="header-blue">
        <i class="bi bi-gift-fill bg-icon-large"></i>
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-white">Catálogo de Premios</h2>
                    <p class="opacity-75 mb-0 text-white">Gestioná los regalos para el canje de puntos.</p>
                </div>
                <div>
                    <a href="canje_puntos.php" class="btn btn-outline-light rounded-pill fw-bold px-4">
                        <i class="bi bi-arrow-left-circle me-2"></i> Volver al Canje
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1">TOTAL PREMIOS</h6><h2 class="mb-0 fw-bold text-dark"><?php echo $total_premios; ?></h2></div>
                        <div class="icon-box bg-primary-soft"><i class="bi bi-gift"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1">PRODUCTOS FÍSICOS</h6><h2 class="mb-0 fw-bold text-secondary"><?php echo $fisicos; ?></h2></div>
                        <div class="icon-box bg-secondary-soft"><i class="bi bi-box-seam"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1">CUPONES DINERO</h6><h2 class="mb-0 fw-bold text-success"><?php echo $cupones; ?></h2></div>
                        <div class="icon-box bg-success-soft"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0 text-primary">
                        <i class="bi bi-plus-circle-fill me-2"></i> Nuevo Premio
                    </div>
                    <div class="card-body bg-light rounded-bottom">
                        <?php echo $mensaje; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="agregar" value="1">
                            
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Nombre</label>
                                <input type="text" name="nombre" class="form-control form-control-lg fw-bold shadow-sm" placeholder="Ej: Coca Cola" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted text-uppercase">Puntos</label>
                                    <input type="number" name="puntos" class="form-control" placeholder="1000" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted text-uppercase">Stock</label>
                                    <input type="number" name="stock" class="form-control" value="10">
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="es_cupon" id="checkCupon" onchange="toggleMonto()">
                                        <label class="form-check-label fw-bold text-dark" style="cursor:pointer;" for="checkCupon">¿Es dinero ($)?</label>
                                    </div>
                                    <div id="divMonto" style="display:none;" class="mt-2 border-top pt-2">
                                        <label class="small text-muted mb-1 fw-bold">Monto a regalar</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-success text-white border-success fw-bold">$</span>
                                            <input type="number" step="0.01" name="monto_dinero" class="form-control border-success text-success fw-bold" placeholder="500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="bi bi-save me-2"></i> GUARDAR
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-check me-2 text-primary"></i> Premios Disponibles</span>
                        <span class="badge bg-light text-muted border"><?php echo count($lista); ?> items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Premio</th>
                                    <th>Costo</th>
                                    <th>Tipo / Stock</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($lista) > 0): ?>
                                    <?php foreach($lista as $p): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['nombre']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-puntos rounded-pill px-3 py-2">
                                                <i class="bi bi-star-fill text-dark me-1"></i> <?php echo number_format($p['puntos_necesarios'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($p['es_cupon']): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    <i class="bi bi-cash-coin"></i> $<?php echo number_format($p['monto_dinero'], 0); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-secondary small">
                                                    Stock: <strong class="text-dark"><?php echo $p['stock']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button onclick="confirmarBorrado(<?php echo $p['id']; ?>)" 
                                               class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-sm">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay premios.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/layout_footer.php'; ?>

    <script>
        // CONFIRMACIÓN DE BORRADO (SWEETALERT)
        function confirmarBorrado(id) {
            Swal.fire({
                title: '¿Eliminar premio?',
                text: "No se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "gestionar_premios.php?borrar=" + id;
                }
            })
        }

        function toggleMonto() {
            var check = document.getElementById('checkCupon');
            var div = document.getElementById('divMonto');
            div.style.display = check.checked ? 'block' : 'none';
        }
        
        // Alertas Toast
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'creado') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Premio agregado', showConfirmButton: false, timer: 3000 });
        } else if(urlParams.get('msg') === 'borrado') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Premio eliminado', showConfirmButton: false, timer: 3000 });
        }
    </script>
</body>
</html>