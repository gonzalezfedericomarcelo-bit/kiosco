<?php
// reportes.php - VERSIÓN BLINDADA (CON REPORTE DE ERRORES VISIBLE)
session_start();

// 1. ACTIVAR ERRORES (Para que no quede pantalla blanca si algo falla)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { 
    header("Location: dashboard.php"); 
    exit; 
}

// Filtros
$inicio = $_GET['f_inicio'] ?? date('Y-m-01');
$fin = $_GET['f_fin'] ?? date('Y-m-d');

if(isset($_GET['set_rango'])) {
    if($_GET['set_rango'] == 'hoy') { $inicio = date('Y-m-d'); $fin = date('Y-m-d'); }
    if($_GET['set_rango'] == 'ayer') { $inicio = date('Y-m-d', strtotime("-1 days")); $fin = date('Y-m-d', strtotime("-1 days")); }
    if($_GET['set_rango'] == 'mes') { $inicio = date('Y-m-01'); $fin = date('Y-m-t'); }
}

$id_usuario = $_GET['id_usuario'] ?? '';
$metodo = $_GET['metodo'] ?? '';

// --- 1. CONSULTA DE VENTAS (BLINDADA) ---
try {
    $sql = "SELECT v.*, u.usuario, c.nombre as cliente,
            (
                SELECT SUM(d.cantidad * COALESCE(NULLIF(d.costo_historico,0), p.precio_costo))
                FROM detalle_ventas d 
                LEFT JOIN productos p ON d.id_producto = p.id
                WHERE d.id_venta = v.id
            ) as costo_total_venta
            FROM ventas v 
            LEFT JOIN usuarios u ON v.id_usuario = u.id 
            LEFT JOIN clientes c ON v.id_cliente = c.id
            WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada'";

    if ($id_usuario) $sql .= " AND v.id_usuario = $id_usuario";
    if ($metodo) $sql .= " AND v.metodo_pago = '$metodo'";
    $sql .= " ORDER BY v.fecha DESC";

    $stmt = $conexion->query($sql);
    if (!$stmt) {
        throw new Exception("Error SQL Ventas: " . print_r($conexion->errorInfo(), true));
    }
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("<div class='alert alert-danger'>Error crítico cargando ventas: " . $e->getMessage() . "</div>");
}

$ingresos_ventas = 0;
$costo_mercaderia = 0;
foreach($ventas as $v) {
    $ingresos_ventas += $v['total'];
    $costo_mercaderia += $v['costo_total_venta'];
}

// --- 2. CONSULTA DE GASTOS (BLINDADA) ---
try {
    // Verificamos si existe la tabla gastos antes de consultar para evitar errores
    $checkTable = $conexion->query("SHOW TABLES LIKE 'gastos'");
    
    $gastos_operativos = 0;
    $retiros_dueno = 0;

    if ($checkTable->rowCount() > 0) {
        $sqlGastos = "SELECT categoria, SUM(monto) as total FROM gastos 
                      WHERE fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' 
                      GROUP BY categoria";
        
        $stmtG = $conexion->query($sqlGastos);
        if ($stmtG) {
            $resGastos = $stmtG->fetchAll(PDO::FETCH_ASSOC);
            foreach($resGastos as $r) {
                if($r['categoria'] == 'Retiro') {
                    $retiros_dueno += $r['total'];
                } else {
                    $gastos_operativos += $r['total'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Si falla gastos, no rompemos todo, solo mostramos 0
    $gastos_operativos = 0;
    $retiros_dueno = 0;
}

// --- 3. CÁLCULOS FINALES ---
$utilidad_bruta = $ingresos_ventas - $costo_mercaderia;
$utilidad_neta_negocio = $utilidad_bruta - $gastos_operativos;
$caja_final = $utilidad_neta_negocio - $retiros_dueno;

// --- 4. TOP PRODUCTOS ---
$top_productos = [];
try {
    $sqlTop = "SELECT p.descripcion, SUM(d.cantidad) as cantidad_total, SUM(d.subtotal) as dinero_total
               FROM detalle_ventas d 
               JOIN ventas v ON d.id_venta = v.id 
               JOIN productos p ON d.id_producto = p.id
               WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada'
               GROUP BY p.id ORDER BY cantidad_total DESC LIMIT 5";
    $stmtTop = $conexion->query($sqlTop);
    if($stmtTop) $top_productos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$usuarios_db = $conexion->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte Financiero Real</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); }
        .card-header { background: white; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5 pt-3">
        
        <div class="card border-0 shadow-sm mb-4 p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Rango</label>
                    <div class="input-group">
                        <input type="date" name="f_inicio" class="form-control" value="<?php echo $inicio; ?>">
                        <span class="input-group-text">-</span>
                        <input type="date" name="f_fin" class="form-control" value="<?php echo $fin; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-2">
                        <a href="generar_pdf.php?tipo=ventas&f_inicio=<?php echo $inicio; ?>&f_fin=<?php echo $fin; ?>" target="_blank" class="btn btn-danger btn-sm fw-bold">
                            <i class="bi bi-file-earmark-pdf"></i> Listado Ventas
                        </a>
                        <a href="reporte_financiero_pdf.php?f_inicio=<?php echo $inicio; ?>&f_fin=<?php echo $fin; ?>" target="_blank" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-pie-chart-fill"></i> Informe Gerencial
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <h5 class="fw-bold text-secondary mb-3">Balance Económico</h5>
        <div class="row g-3 mb-4">
            
            <div class="col-md-3">
                <div class="card kpi-card p-3 h-100 border-start border-primary border-4">
                    <div class="small text-uppercase text-muted fw-bold">1. Ingresos Ventas</div>
                    <div class="fs-3 fw-bold text-primary">$<?php echo number_format($ingresos_ventas, 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card kpi-card p-3 h-100 border-start border-warning border-4">
                    <div class="small text-uppercase text-muted fw-bold">2. Costo Mercadería</div>
                    <div class="fs-3 fw-bold text-warning">$<?php echo number_format($costo_mercaderia, 0, ',', '.'); ?></div>
                    <small class="text-danger">- Gastos Op.: $<?php echo number_format($gastos_operativos, 0); ?></small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card kpi-card p-3 h-100 border-start border-success border-4">
                    <div class="small text-uppercase text-muted fw-bold">3. Rentabilidad Neta</div>
                    <div class="fs-3 fw-bold text-success">$<?php echo number_format($utilidad_neta_negocio, 0, ',', '.'); ?></div>
                    <small class="text-muted">(Ventas - Costos - Gastos)</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card kpi-card p-3 h-100 border-start border-dark border-4 bg-white">
                    <div class="small text-uppercase text-muted fw-bold">4. Tus Retiros</div>
                    <div class="fs-3 fw-bold text-danger">-$<?php echo number_format($retiros_dueno, 0, ',', '.'); ?></div>
                    <small class="fw-bold text-dark">Queda en Caja: $<?php echo number_format($caja_final, 0); ?></small>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-warning"><i class="bi bi-trophy-fill"></i> Más Vendidos</div>
                    <ul class="list-group list-group-flush small">
                        <?php foreach($top_productos as $tp): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo $tp['descripcion']; ?>
                                <span class="badge bg-light text-dark border"><?php echo intval($tp['cantidad_total']); ?> un.</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if(empty($top_productos)) echo '<li class="list-group-item text-center text-muted">Sin datos</li>'; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold">Detalle de Operaciones</h6>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-4">Ticket</th>
                                    <th>Fecha</th>
                                    <th>Pago</th>
                                    <th class="text-end">Venta</th>
                                    <th class="text-end text-danger">Costo</th>
                                    <th class="text-end text-success pe-4">Margen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ventas as $v): 
                                    $ganancia = $v['total'] - $v['costo_total_venta'];
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">#<?php echo $v['id']; ?></td>
                                    <td><?php echo date('d/m H:i', strtotime($v['fecha'])); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $v['metodo_pago']; ?></span></td>
                                    <td class="text-end fw-bold">$<?php echo number_format($v['total'], 0); ?></td>
                                    <td class="text-end text-danger">$<?php echo number_format($v['costo_total_venta'], 0); ?></td>
                                    <td class="text-end fw-bold text-success pe-4">$<?php echo number_format($ganancia, 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>