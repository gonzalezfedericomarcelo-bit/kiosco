<?php
// ver_detalle_caja.php - VERSIÓN BLINDADA CONTRA ERRORES
session_start();
require_once 'includes/db.php';
require_once 'includes/interfaz_helper.php';

if (!isset($_GET['id'])) { header("Location: historial_cajas.php"); exit; }
$id_sesion = $_GET['id'];

// 1. OBTENER CABECERA DE CAJA
$stmt = $conexion->prepare("SELECT c.*, u.usuario as cajero FROM cajas_sesion c JOIN usuarios u ON c.id_usuario = u.id WHERE c.id = ?");
$stmt->execute([$id_sesion]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja) { die("Caja no encontrada."); }

// 2. CALCULAR TOTALES DESGLOSADOS
// A. Ventas de Rifas
$sqlRifas = "SELECT SUM(total) FROM ventas WHERE id_caja_sesion = ? AND estado='completada' AND codigo_ticket LIKE 'RIFA-%'";
$stmtR = $conexion->prepare($sqlRifas); $stmtR->execute([$id_sesion]);
$total_rifas = $stmtR->fetchColumn() ?: 0;

// B. Ventas de Mostrador (incluye las que tienen codigo_ticket NULL)
$sqlVentas = "SELECT SUM(total) FROM ventas WHERE id_caja_sesion = ? AND estado='completada' AND (codigo_ticket NOT LIKE 'RIFA-%' OR codigo_ticket IS NULL)";
$stmtV = $conexion->prepare($sqlVentas); $stmtV->execute([$id_sesion]);
$total_ventas = $stmtV->fetchColumn() ?: 0;

// C. Gastos
$sqlGastos = "SELECT SUM(monto) FROM gastos WHERE id_caja_sesion = ?";
$stmtG = $conexion->prepare($sqlGastos); $stmtG->execute([$id_sesion]);
$total_gastos = $stmtG->fetchColumn() ?: 0;

// 3. OBTENER LISTADO COMPLETO (SOLUCIÓN ERROR: Manejo de nulos en SQL)
$movimientos = $conexion->prepare("
    SELECT 'Venta' as tipo, id, fecha, total as monto, metodo_pago as detalle, IFNULL(codigo_ticket, '') as codigo_ticket 
    FROM ventas WHERE id_caja_sesion = ? AND estado='completada'
    UNION ALL
    SELECT 'Gasto' as tipo, id, fecha, monto, categoria as detalle, descripcion as codigo_ticket 
    FROM gastos WHERE id_caja_sesion = ?
    ORDER BY fecha DESC
");
$movimientos->execute([$id_sesion, $id_sesion]);
$lista = $movimientos->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/layout_header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="historial_cajas.php" class="text-decoration-none text-muted mb-1"><i class="bi bi-arrow-left"></i> Volver al Historial</a>
            <h2 class="fw-bold mb-0">Detalle de Caja #<?php echo $caja['id']; ?></h2>
            <p class="text-muted small">Cajero: <?php echo strtoupper($caja['cajero']); ?> | Fecha: <?php echo date('d/m/Y H:i', strtotime($caja['fecha_apertura'])); ?></p>
        </div>
        <div class="text-end">
             <h3 class="fw-bold text-success mb-0"><?php echo formato_moneda($caja['total_ventas']); ?></h3>
             <small>Total Ingresos Brutos</small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-white-50 fw-bold">VENTAS MOSTRADOR</small>
                    <h3 class="fw-bold mb-0"><?php echo formato_moneda($total_ventas); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-dark-50 fw-bold">INGRESOS POR RIFAS</small>
                    <h3 class="fw-bold mb-0"><?php echo formato_moneda($total_rifas); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-white-50 fw-bold">GASTOS Y RETIROS</small>
                    <h3 class="fw-bold mb-0">-<?php echo formato_moneda($total_gastos); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold py-3">Movimientos Registrados</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Detalle / Ref</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($lista)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No hay movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach($lista as $m): 
                            // PROTECCIÓN CONTRA NULOS
                            $cod = $m['codigo_ticket'] ?? ''; 
                            $esRifa = (strpos($cod, 'RIFA-') === 0);
                        ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($m['fecha'])); ?></td>
                            <td>
                                <?php if($m['tipo'] == 'Gasto'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">GASTO</span>
                                <?php elseif($esRifa): ?>
                                    <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-ticket-fill"></i> RIFA</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">VENTA</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if($m['tipo'] == 'Gasto') {
                                        echo "<strong>" . htmlspecialchars($m['detalle']) . "</strong>: " . htmlspecialchars($cod);
                                    } elseif($esRifa) {
                                        echo "<strong>Sorteo</strong>: Ticket " . htmlspecialchars($cod);
                                    } else {
                                        echo "<strong>Producto</strong> (" . htmlspecialchars($m['detalle']) . ")";
                                    }
                                ?>
                            </td>
                            <td class="text-end fw-bold <?php echo $m['tipo']=='Gasto'?'text-danger':'text-dark'; ?>">
                                <?php echo $m['tipo']=='Gasto' ? '-' : ''; ?><?php echo formato_moneda($m['monto']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>