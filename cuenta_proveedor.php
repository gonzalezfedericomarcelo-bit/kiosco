<?php
// cuenta_proveedor.php - GESTIÓN DE CUENTA CORRIENTE
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error: No se encuentra db.php");

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

$id_proveedor = $_GET['id'] ?? null;
if (!$id_proveedor) { header("Location: proveedores.php"); exit; }

// 3. DATOS DEL PROVEEDOR
$stmt = $conexion->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$id_proveedor]);
$prov = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prov) die("Proveedor no encontrado.");

// 4. REGISTRAR MOVIMIENTO (COMPRA O PAGO)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo']; // 'compra' o 'pago'
    $monto = (float)$_POST['monto'];
    $desc = trim($_POST['descripcion']);
    $comp = trim($_POST['comprobante']);
    $fecha = $_POST['fecha'] . ' ' . date('H:i:s');
    $id_user = $_SESSION['usuario_id'];

    if ($monto > 0) {
        $sql = "INSERT INTO movimientos_proveedores (id_proveedor, tipo, monto, descripcion, comprobante, fecha, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $conexion->prepare($sql)->execute([$id_proveedor, $tipo, $monto, $desc, $comp, $fecha, $id_user]);
        $msg = '<div class="alert alert-success">✅ Movimiento registrado correctamente.</div>';
    }
}

// 5. OBTENER HISTORIAL Y SALDO
// Ordenamos por fecha descendente (lo más nuevo arriba)
$historial = $conexion->prepare("SELECT * FROM movimientos_proveedores WHERE id_proveedor = ? ORDER BY fecha DESC");
$historial->execute([$id_proveedor]);
$movimientos = $historial->fetchAll(PDO::FETCH_ASSOC);

// Calcular Saldo Actual (Deuda Total)
// Compras SUMAN deuda, Pagos RESTAN deuda
$saldo_total = 0;
// Recalculamos recorriendo todo o con SQL. Por seguridad visual, SQL directo:
$sqlSaldo = "SELECT 
    (SELECT COALESCE(SUM(monto),0) FROM movimientos_proveedores WHERE id_proveedor = ? AND tipo = 'compra') - 
    (SELECT COALESCE(SUM(monto),0) FROM movimientos_proveedores WHERE id_proveedor = ? AND tipo = 'pago') as saldo";
$stmtSaldo = $conexion->prepare($sqlSaldo);
$stmtSaldo->execute([$id_proveedor, $id_proveedor]);
$saldo_total = $stmtSaldo->fetchColumn();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cta. Cte. <?php echo $prov['empresa']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
if(file_exists('menu.php')) include 'menu.php'; 
elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
?>

<div class="container py-4">
    
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <a href="proveedores.php" class="text-decoration-none text-secondary mb-2 d-inline-block"><i class="bi bi-arrow-left"></i> Volver a Proveedores</a>
            <h2 class="fw-bold mb-0"><i class="bi bi-truck text-primary"></i> <?php echo $prov['empresa']; ?></h2>
            <div class="text-muted small"><?php echo $prov['contacto']; ?> | <?php echo $prov['telefono']; ?></div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="card bg-white border-0 shadow-sm d-inline-block text-start" style="min-width: 250px;">
                <div class="card-body p-3">
                    <small class="text-uppercase text-muted fw-bold">Saldo Deudor (Lo que debés)</small>
                    <div class="fs-2 fw-bold <?php echo ($saldo_total > 0) ? 'text-danger' : 'text-success'; ?>">
                        $ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-pencil-square"></i> Registrar Movimiento
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small">Tipo de Movimiento</label>
                            <select name="tipo" class="form-select fw-bold" id="tipoSelect" onchange="cambiarColor()">
                                <option value="compra" class="text-danger">🔴 Nueva Compra (Aumenta Deuda)</option>
                                <option value="pago" class="text-success">🟢 Registrar Pago (Baja Deuda)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small">Monto ($)</label>
                            <input type="number" name="monto" class="form-control form-control-lg" step="0.01" required placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">N° Comprobante / Factura</label>
                            <input type="text" name="comprobante" class="form-control" placeholder="Ej: FC-A 0001">
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Detalle / Notas</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Ej: Compra de 10 cajones de Coca..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">REGISTRAR</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-clock-history"></i> Historial de Cuenta
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Detalle</th>
                                    <th class="text-end text-danger">Debe (Compras)</th>
                                    <th class="text-end text-success">Haber (Pagos)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($movimientos) > 0): ?>
                                    <?php foreach($movimientos as $m): ?>
                                    <tr>
                                        <td class="small"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
                                        <td>
                                            <div class="fw-bold small"><?php echo $m['tipo'] == 'compra' ? 'Factura / Compra' : 'Pago Realizado'; ?></div>
                                            <div class="text-muted small" style="font-size: 0.8rem;">
                                                <?php echo $m['comprobante'] ? 'Comp: '.$m['comprobante'] : ''; ?>
                                                <?php echo $m['descripcion']; ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <?php if($m['tipo'] == 'compra'): ?>
                                                <span class="text-danger fw-bold">$ <?php echo number_format($m['monto'], 2, ',', '.'); ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if($m['tipo'] == 'pago'): ?>
                                                <span class="text-success fw-bold">$ <?php echo number_format($m['monto'], 2, ',', '.'); ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay movimientos registrados.</td></tr>
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
<script>
    function cambiarColor() {
        var tipo = document.getElementById('tipoSelect').value;
        var select = document.getElementById('tipoSelect');
        if(tipo === 'compra') {
            select.classList.remove('text-success');
            select.classList.add('text-danger');
        } else {
            select.classList.remove('text-danger');
            select.classList.add('text-success');
        }
    }
    cambiarColor(); // Init
</script>
</body>
</html>