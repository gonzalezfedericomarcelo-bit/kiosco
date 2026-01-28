<?php
// gestionar_premios.php - CON OPCIÓN DE CUPONES
session_start();
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// AGREGAR PREMIO
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $puntos = $_POST['puntos'];
    $stock = $_POST['stock'];
    
    // Nuevos campos
    $es_cupon = isset($_POST['es_cupon']) ? 1 : 0;
    $monto = $_POST['monto_dinero'] ?? 0;
    
    $conexion->prepare("INSERT INTO premios (nombre, puntos_necesarios, stock, es_cupon, monto_dinero, activo) VALUES (?, ?, ?, ?, ?, 1)")
             ->execute([$nombre, $puntos, $stock, $es_cupon, $monto]);
    header("Location: gestionar_premios.php"); exit;
}

// BORRAR
if (isset($_GET['borrar'])) {
    $conexion->prepare("DELETE FROM premios WHERE id = ?")->execute([$_GET['borrar']]);
    header("Location: gestionar_premios.php"); exit;
}

$lista = $conexion->query("SELECT * FROM premios ORDER BY puntos_necesarios ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Premios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if (file_exists('includes/menu.php')) include 'includes/menu.php';
    elseif (file_exists('menu.php')) include 'menu.php';
    ?>

    <div class="container mt-4">
        <h3 class="mb-4 fw-bold text-success"><i class="bi bi-gift"></i> Configuración de Premios</h3>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">Nuevo Premio / Cupón</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nombre</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: Vale $500" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small">Costo Puntos</label>
                                    <input type="number" name="puntos" class="form-control" placeholder="Ej: 1000" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small">Stock Físico</label>
                                    <input type="number" name="stock" class="form-control" value="100">
                                </div>
                            </div>

                            <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="es_cupon" id="checkCupon" onchange="toggleMonto()">
                                    <label class="form-check-label fw-bold" for="checkCupon">¿Es dinero a favor?</label>
                                </div>
                                <div id="divMonto" style="display:none;" class="mt-2">
                                    <label class="form-label small text-muted">Monto a regalar ($)</label>
                                    <input type="number" step="0.01" name="monto_dinero" class="form-control" placeholder="Ej: 500.00">
                                    <small class="text-danger" style="font-size:0.7em">* Se acreditará en la cuenta del cliente</small>
                                </div>
                            </div>

                            <button type="submit" name="agregar" class="btn btn-success w-100 fw-bold">CREAR PREMIO</button>
                        </form>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="canje_puntos.php" class="btn btn-link text-decoration-none">← Ir al Canje</a>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Premio</th>
                                    <th>Costo</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lista as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $p['nombre']; ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $p['puntos_necesarios']; ?> pts</span></td>
                                    <td>
                                        <?php if($p['es_cupon']): ?>
                                            <span class="badge bg-success text-white"><i class="bi bi-cash"></i> $<?php echo number_format($p['monto_dinero'],0); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Stock: <?php echo $p['stock']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="gestionar_premios.php?borrar=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleMonto() {
            var check = document.getElementById('checkCupon');
            var div = document.getElementById('divMonto');
            div.style.display = check.checked ? 'block' : 'none';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>