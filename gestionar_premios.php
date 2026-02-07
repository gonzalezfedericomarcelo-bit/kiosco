<?php
// gestionar_premios.php - DISEÑO PREMIUM
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error Crítico: No se encuentra db.php");

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$mensaje = '';

// 3. AGREGAR PREMIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    try {
        $nombre = $_POST['nombre'];
        $puntos = $_POST['puntos'];
        $stock = $_POST['stock'];
        
        // Lógica de Cupón vs Físico
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
$lista = $conexion->query("SELECT * FROM premios ORDER BY puntos_necesarios ASC")->fetchAll(PDO::FETCH_ASSOC);
$total_premios = count($lista);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Premios y Fidelización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        .header-gradient {
            background: linear-gradient(135deg, #F2994A 0%, #F2C94C 100%); /* Gold Gradient */
            color: white; padding: 30px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; 
            box-shadow: 0 4px 15px rgba(242, 153, 74, 0.3);
        }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .badge-puntos { background-color: #FFEFD5; color: #D35400; border: 1px solid #F5CBA7; }
    </style>
</head>
<body>

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="header-gradient">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0 text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Catálogo de Premios</h2>
                <p class="mb-0 opacity-75 text-white">Configurá los regalos para el canje de puntos.</p>
            </div>
            <div class="bg-white bg-opacity-25 rounded-pill px-4 py-2 shadow-sm border border-white border-opacity-25">
                <i class="bi bi-gift-fill text-white me-1"></i> 
                <span class="fw-bold fs-5 text-white"><?php echo $total_premios; ?></span> <small class="text-white">Opciones</small>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0">
                        <i class="bi bi-plus-circle text-warning"></i> Nuevo Premio
                    </div>
                    <div class="card-body bg-light">
                        <?php echo $mensaje; ?>
                        <form method="POST">
                            <input type="hidden" name="agregar" value="1">
                            
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Nombre del Premio</label>
                                <input type="text" name="nombre" class="form-control form-control-lg fw-bold" placeholder="Ej: Coca Cola 500ml" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Puntos Req.</label>
                                    <input type="number" name="puntos" class="form-control" placeholder="1000" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Stock Inicial</label>
                                    <input type="number" name="stock" class="form-control" value="10">
                                </div>
                            </div>

                            <div class="card border-warning mb-3" style="background-color: #fffcf5;">
                                <div class="card-body p-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="es_cupon" id="checkCupon" onchange="toggleMonto()">
                                        <label class="form-check-label fw-bold text-dark" for="checkCupon">¿Es dinero a favor?</label>
                                    </div>
                                    <div id="divMonto" style="display:none;">
                                        <label class="small text-muted mb-1">Monto a regalar ($)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white text-success border-success">$</span>
                                            <input type="number" step="0.01" name="monto_dinero" class="form-control border-success" placeholder="500.00">
                                        </div>
                                        <small class="text-success fst-italic" style="font-size: 0.75rem;">* Se carga como saldo a favor.</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning text-white w-100 fw-bold py-2 shadow-sm" style="background-color: #F2994A; border:none;">
                                GUARDAR PREMIO
                            </button>
                            
                            <div class="mt-3 text-center">
                                <a href="canje_puntos.php" class="small text-decoration-none text-muted"><i class="bi bi-arrow-right"></i> Ir al Canje de Puntos</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Premios Disponibles</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Premio</th>
                                    <th>Costo (Puntos)</th>
                                    <th>Tipo / Stock</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($lista) > 0): ?>
                                    <?php foreach($lista as $p): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo $p['nombre']; ?></div>
                                            <small class="text-muted">ID: #<?php echo $p['id']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-puntos rounded-pill px-3">
                                                <i class="bi bi-star-fill text-warning me-1"></i> <?php echo number_format($p['puntos_necesarios'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($p['es_cupon']): ?>
                                                <div class="d-flex align-items-center text-success fw-bold small">
                                                    <i class="bi bi-cash-coin fs-5 me-2"></i>
                                                    Vale por $<?php echo number_format($p['monto_dinero'], 0); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center text-secondary small">
                                                    <i class="bi bi-box-seam fs-5 me-2"></i>
                                                    Stock: <strong><?php echo $p['stock']; ?></strong> un.
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="gestionar_premios.php?borrar=<?php echo $p['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                               onclick="return confirm('¿Estás seguro de eliminar este premio?');"
                                               title="Eliminar">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay premios creados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMonto() {
            var check = document.getElementById('checkCupon');
            var div = document.getElementById('divMonto');
            if(check.checked) {
                div.style.display = 'block';
                // Animación simple
                div.style.opacity = 0;
                setTimeout(() => div.style.opacity = 1, 50);
            } else {
                div.style.display = 'none';
            }
        }
        
        // Notificaciones SweetAlert
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'creado') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Premio agregado', showConfirmButton: false, timer: 3000 });
        } else if(urlParams.get('msg') === 'borrado') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Premio eliminado', showConfirmButton: false, timer: 3000 });
        }
    </script>
</body>
</html>