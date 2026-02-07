<?php
// reportes.php - VERSIÓN DEFINITIVA CON GANANCIA REAL
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

// Filtros
$tipo_filtro = $_GET['rango'] ?? 'mes'; 
$inicio = $_GET['f_inicio'] ?? date('Y-m-01');
$fin = $_GET['f_fin'] ?? date('Y-m-d');

if(isset($_GET['set_rango'])) {
    if($_GET['set_rango'] == 'hoy') { $inicio = date('Y-m-d'); $fin = date('Y-m-d'); }
    if($_GET['set_rango'] == 'ayer') { $inicio = date('Y-m-d', strtotime("-1 days")); $fin = date('Y-m-d', strtotime("-1 days")); }
    if($_GET['set_rango'] == 'mes') { $inicio = date('Y-m-01'); $fin = date('Y-m-t'); }
}

$id_usuario = $_GET['id_usuario'] ?? '';
$metodo = $_GET['metodo'] ?? '';

// CONSULTA MAESTRA (Incluye cálculo de Costo)
// COALESCE(NULLIF(d.costo_historico,0), p.precio_costo) -> Prioriza el costo guardado al vender. Si es 0 (venta vieja), usa el costo actual.
$sql = "SELECT v.*, u.usuario, c.nombre as cliente,
        (
            SELECT SUM(d.cantidad * COALESCE(NULLIF(d.costo_historico,0), p.precio_costo))
            FROM detalle_ventas d
            JOIN productos p ON d.id_producto = p.id
            WHERE d.id_venta = v.id
        ) as costo_total_venta
        FROM ventas v 
        JOIN usuarios u ON v.id_usuario = u.id 
        JOIN clientes c ON v.id_cliente = c.id
        WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada'";

if ($id_usuario) $sql .= " AND v.id_usuario = $id_usuario";
if ($metodo) $sql .= " AND v.metodo_pago = '$metodo'";
$sql .= " ORDER BY v.fecha DESC";

$ventas = $conexion->query($sql)->fetchAll();

// KPIs
$total_ingresos = 0;
$total_costos = 0;
$cant_ventas = count($ventas);

foreach($ventas as $v) {
    $total_ingresos += $v->total;
    $total_costos += $v->costo_total_venta;
}

$total_ganancia = $total_ingresos - $total_costos;
$margen_promedio = ($total_ingresos > 0) ? ($total_ganancia / $total_ingresos) * 100 : 0;

$usuarios_db = $conexion->query("SELECT * FROM usuarios")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte Financiero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .kpi-card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); }
        .bg-gradient-success { background: linear-gradient(45deg, #198754, #20c997); color: white; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }
        .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #3dd5f3); color: white; }
        .bg-gradient-dark { background: linear-gradient(45deg, #212529, #343a40); color: white; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5 pt-3">
        
        <div class="card border-0 shadow-sm mb-4 p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Rango de Fechas</label>
                    <div class="input-group">
                        <input type="date" name="f_inicio" class="form-control" value="<?php echo $inicio; ?>">
                        <span class="input-group-text bg-white">-</span>
                        <input type="date" name="f_fin" class="form-control" value="<?php echo $fin; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Vendedor</label>
                    <select name="id_usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($usuarios_db as $u): ?>
                            <option value="<?php echo $u->id; ?>" <?php echo ($id_usuario == $u->id)?'selected':''; ?>><?php echo $u->usuario; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                     <label class="small fw-bold text-muted">Pago</label>
                     <select name="metodo" class="form-select">
                        <option value="">Todos</option>
                        <option value="Efectivo" <?php echo ($metodo=='Efectivo')?'selected':''; ?>>Efectivo</option>
                        <option value="MP" <?php echo ($metodo=='MP')?'selected':''; ?>>MercadoPago</option>
                        <option value="Mixto" <?php echo ($metodo=='Mixto')?'selected':''; ?>>Mixto</option>
                     </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
                <div class="col-md-2">
                     <div class="dropdown">
                        <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">Presets</button>
                        <ul class="dropdown-menu">
                            <li><button type="submit" name="set_rango" value="hoy" class="dropdown-item">Hoy</button></li>
                            <li><button type="submit" name="set_rango" value="ayer" class="dropdown-item">Ayer</button></li>
                            <li><button type="submit" name="set_rango" value="mes" class="dropdown-item">Este Mes</button></li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card kpi-card bg-gradient-primary p-3 h-100">
                    <div class="small text-uppercase opacity-75 fw-bold">Facturación</div>
                    <div class="display-6 fw-bold">$<?php echo number_format($total_ingresos, 0, ',', '.'); ?></div>
                    <small><?php echo $cant_ventas; ?> ventas registradas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card bg-white border p-3 h-100">
                    <div class="small text-uppercase text-muted fw-bold">Costos (Est.)</div>
                    <div class="fs-2 fw-bold text-danger">$<?php echo number_format($total_costos, 0, ',', '.'); ?></div>
                    <small class="text-muted">Costo de mercadería</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card bg-gradient-success p-3 h-100">
                    <div class="small text-uppercase opacity-75 fw-bold">Ganancia Neta</div>
                    <div class="display-6 fw-bold">$<?php echo number_format($total_ganancia, 0, ',', '.'); ?></div>
                    <small>Bolsillo Real</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card bg-dark text-white p-3 h-100">
                    <div class="small text-uppercase opacity-75 fw-bold">Margen</div>
                    <div class="display-6 fw-bold"><?php echo number_format($margen_promedio, 1); ?>%</div>
                    <small>Rentabilidad promedio</small>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold">Detalle de Operaciones</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="ps-4">Ticket</th>
                            <th>Fecha</th>
                            <th>Pago</th>
                            <th class="text-end">Venta</th>
                            <th class="text-end text-danger">Costo</th>
                            <th class="text-end text-success pe-4">Ganancia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ventas as $v): 
                            $ganancia = $v->total - $v->costo_total_venta;
                            $class_ganancia = ($ganancia >= 0) ? 'text-success' : 'text-danger';
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?php echo $v->id; ?></td>
                            <td>
                                <?php echo date('d/m H:i', strtotime($v->fecha)); ?>
                                <div class="small text-muted"><?php echo $v->usuario; ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo $v->metodo_pago; ?></span>
                            </td>
                            <td class="text-end fw-bold">$<?php echo number_format($v->total, 2); ?></td>
                            <td class="text-end text-danger">$<?php echo number_format($v->costo_total_venta, 2); ?></td>
                            <td class="text-end fw-bold <?php echo $class_ganancia; ?> pe-4">
                                $<?php echo number_format($ganancia, 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>