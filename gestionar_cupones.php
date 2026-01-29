<?php
// gestionar_cupones.php - VERSIÓN CORREGIDA Y COMPATIBLE CON TU DB EXISTENTE
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN (Mismo parche de seguridad que proveedores.php)
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("<h1>ERROR CRÍTICO: No se encuentra db.php.</h1>");
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

// 2. ELIMINAR
if (isset($_GET['borrar'])) {
    try {
        $conexion->prepare("DELETE FROM cupones WHERE id = ?")->execute([$_GET['borrar']]);
        header("Location: gestionar_cupones.php?msg=eliminado"); exit;
    } catch (Exception $e) { /* Silencio o log */ }
}

// 3. CREAR (Usando nombres de columnas EXISTENTES en tu DB)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $codigo = strtoupper(trim($_POST['codigo']));
        $porcentaje = (int)$_POST['porcentaje'];
        $vencimiento = $_POST['vencimiento']; // Se guardará en 'fecha_limite'
        $limite = (int)$_POST['limite'];

        // Validar duplicado
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM cupones WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetchColumn() > 0) {
            $mensaje = '<div class="alert alert-danger">❌ El código <b>'.$codigo.'</b> ya existe.</div>';
        } else {
            // AQUÍ ESTABA EL ERROR: Usamos tus columnas reales
            $sql = "INSERT INTO cupones (codigo, descuento_porcentaje, fecha_limite, cantidad_limite, usos_actuales, activo) 
                    VALUES (?, ?, ?, ?, 0, 1)";
            $stmtInsert = $conexion->prepare($sql);
            
            if ($stmtInsert->execute([$codigo, $porcentaje, $vencimiento, $limite])) {
                $mensaje = '<div class="alert alert-success">✅ Cupón creado.</div>';
            }
        }
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error SQL: '.$e->getMessage().'</div>';
    }
}

// 4. LISTAR (Usando 'fecha_limite' en el ORDER BY)
try {
    $cupones = $conexion->query("SELECT * FROM cupones ORDER BY fecha_limite DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cupones = [];
    $mensaje = '<div class="alert alert-danger">Error de lectura: '.$e->getMessage().'</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Cupones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container py-4">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-plus-circle-dotted me-2"></i>Nuevo Cupón
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Código</label>
                                <input type="text" name="codigo" class="form-control text-uppercase" required placeholder="Ej: PROMO10">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Descuento (%)</label>
                                <input type="number" name="porcentaje" class="form-control" min="1" max="100" required placeholder="10">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Vencimiento</label>
                                <input type="date" name="vencimiento" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Límite de Usos</label>
                                <input type="number" name="limite" class="form-control" value="0">
                                <div class="form-text small">0 = Ilimitado</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">GUARDAR</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold">Cupones Activos</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Código</th>
                                        <th class="text-center">Desc.</th>
                                        <th>Vence</th>
                                        <th class="text-center">Usos</th>
                                        <th class="text-end pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($cupones) > 0): ?>
                                        <?php foreach($cupones as $c): 
                                            // Mapeo de columnas viejas a variables lógicas
                                            $venc = $c['fecha_limite'] ?? $c['fecha_vencimiento'] ?? null;
                                            $desc = $c['descuento_porcentaje'] ?? $c['porcentaje'] ?? 0;
                                            $lim = $c['cantidad_limite'] ?? 0;
                                            $usos = $c['usos_actuales'] ?? 0;
                                            
                                            $vencido = ($venc && $venc < date('Y-m-d'));
                                            $agotado = ($lim > 0 && $usos >= $lim);
                                            
                                            $style = ($vencido || $agotado) ? 'opacity-50' : '';
                                        ?>
                                        <tr class="<?php echo $style; ?>">
                                            <td class="ps-3 fw-bold text-uppercase"><?php echo $c['codigo']; ?></td>
                                            <td class="text-center"><span class="badge bg-info text-dark"><?php echo $desc; ?>%</span></td>
                                            <td class="small"><?php echo $venc ? date('d/m/Y', strtotime($venc)) : '∞'; ?></td>
                                            <td class="text-center small"><?php echo $usos; ?> / <?php echo ($lim == 0) ? '∞' : $lim; ?></td>
                                            <td class="text-end pe-3">
                                                <a href="gestionar_cupones.php?borrar=<?php echo $c['id']; ?>" class="text-danger" onclick="return confirm('¿Borrar?');"><i class="bi bi-trash3-fill"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-3 text-muted">Sin cupones.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>