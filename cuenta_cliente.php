<?php
// cuenta_cliente.php - CORREGIDO (Columna 'concepto' y tipos 'debe'/'haber')
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error: db.php no encontrado");

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id_cliente = $_GET['id'] ?? null;
if (!$id_cliente) { header("Location: clientes.php"); exit; }

// DATOS CLIENTE
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) die("Cliente no encontrado.");

// REGISTRAR MOVIMIENTO MANUAL
$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // EN TU BASE DE DATOS:
        // 'debe' = Aumenta la deuda del cliente (Compra o Ajuste +)
        // 'haber' = Disminuye la deuda (Pago o Ajuste -)
        
        $accion = $_POST['accion']; // 'pago' (haber) o 'deuda' (debe)
        $monto = (float)$_POST['monto'];
        $concepto = trim($_POST['concepto']); // ANTES DECÍA 'descripcion' Y FALLABA
        $id_user = $_SESSION['usuario_id'];
        
        $tipo_db = ($accion == 'deuda') ? 'debe' : 'haber';

        if ($monto > 0) {
            $sql = "INSERT INTO movimientos_cc (id_cliente, tipo, monto, concepto, id_usuario, fecha) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id_cliente, $tipo_db, $monto, $concepto, $id_user]);
            
            $msg = '<div class="alert alert-success shadow-sm">✅ Movimiento registrado correctamente.</div>';
        }
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

// OBTENER HISTORIAL
$historial = [];
$saldo_actual = 0;
try {
    $stmtHist = $conexion->prepare("SELECT * FROM movimientos_cc WHERE id_cliente = ? ORDER BY fecha DESC");
    $stmtHist->execute([$id_cliente]);
    $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    // CALCULAR SALDO (Debe - Haber)
    $sqlSaldo = "SELECT 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = ? AND tipo = 'debe') - 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = ? AND tipo = 'haber')";
    $stmtS = $conexion->prepare($sqlSaldo);
    $stmtS->execute([$id_cliente, $id_cliente]);
    $saldo_actual = $stmtS->fetchColumn();

} catch (Exception $e) { $msg = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cta. Cte. <?php echo $cliente['nombre']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            <a href="clientes.php" class="text-decoration-none text-secondary mb-2 d-inline-block"><i class="bi bi-arrow-left"></i> Volver</a>
            <h2 class="fw-bold mb-0 text-uppercase"><?php echo $cliente['nombre']; ?></h2>
            <div class="text-muted small">DNI: <?php echo $cliente['dni']; ?> | Límite: $<?php echo number_format($cliente['limite_credito']??0,0,',','.'); ?></div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="card bg-white border-0 shadow-sm d-inline-block text-start" style="min-width: 250px;">
                <div class="card-body p-3">
                    <small class="text-uppercase text-muted fw-bold">Deuda Total</small>
                    <div class="fs-1 fw-bold <?php echo ($saldo_actual > 0) ? 'text-danger' : 'text-success'; ?>">
                        $ <?php echo number_format($saldo_actual, 2, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow border-0 sticky-top" style="top: 90px;">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="bi bi-cash-coin"></i> Registrar Movimiento
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small">Acción</label>
                            <select name="accion" class="form-select fw-bold" id="tipoSelect" onchange="cambiarColor()">
                                <option value="pago" class="text-success" selected>💵 Recibir Pago (Baja Deuda)</option>
                                <option value="deuda" class="text-danger">📝 Ajuste Manual (Sube Deuda)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Monto ($)</label>
                            <input type="number" name="monto" class="form-control form-control-lg fw-bold" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted">Detalle / Concepto</label>
                            <textarea name="concepto" class="form-control" rows="2" placeholder="Ej: Pago a cuenta..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-3 shadow-sm">CONFIRMAR</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white fw-bold">Movimientos Recientes</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th class="text-end text-danger">Debe</th>
                                    <th class="text-end text-success">Haber</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($historial) > 0): ?>
                                    <?php foreach($historial as $m): ?>
                                    <tr>
                                        <td class="small"><?php echo date('d/m/y H:i', strtotime($m['fecha'])); ?></td>
                                        <td><div class="small fw-bold"><?php echo $m['concepto']; ?></div></td>
                                        <td class="text-end text-danger fw-bold">
                                            <?php echo ($m['tipo']=='debe') ? '$'.number_format($m['monto'],2,',','.') : '-'; ?>
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            <?php echo ($m['tipo']=='haber') ? '$'.number_format($m['monto'],2,',','.') : '-'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Sin movimientos.</td></tr>
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
        var header = document.querySelector('.card-header.bg-success') || document.querySelector('.card-header.bg-danger');
        var btn = document.querySelector('button[type="submit"]');
        
        if(tipo === 'deuda') {
            if(header) { header.className = 'card-header bg-danger text-white fw-bold'; }
            btn.className = 'btn btn-danger w-100 fw-bold py-3 shadow-sm';
        } else {
            if(header) { header.className = 'card-header bg-success text-white fw-bold'; }
            btn.className = 'btn btn-success w-100 fw-bold py-3 shadow-sm';
        }
    }
</script>
</body>
</html>