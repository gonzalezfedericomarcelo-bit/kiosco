<?php
// reportes.php - SISTEMA GERENCIAL "EL 10" - FULL DATA + EXCEL PRO + TOP PRODUCTOS
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { 
    header("Location: dashboard.php"); 
    exit; 
}

// 1. FILTROS
$inicio = $_GET['f_inicio'] ?? date('Y-m-01');
$fin = $_GET['f_fin'] ?? date('Y-m-d');

// Captura tanto set_rango como filtro para que el Dashboard funcione
$trigger = $_GET['set_rango'] ?? $_GET['filtro'] ?? '';

if($trigger) {
    if($trigger == 'hoy') { $inicio = date('Y-m-d'); $fin = date('Y-m-d'); }
    elseif($trigger == 'ayer') { $inicio = date('Y-m-d', strtotime("-1 days")); $fin = date('Y-m-d', strtotime("-1 days")); }
    elseif($trigger == 'mes') { $inicio = date('Y-m-01'); $fin = date('Y-m-t'); }
}

// Forzamos que el ID de usuario sea un entero y limpiamos el método de pago
$id_usuario = (isset($_GET['id_usuario']) && $_GET['id_usuario'] !== '') ? intval($_GET['id_usuario']) : '';
$metodo = isset($_GET['metodo']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['metodo']) : '';

// 2. CONSULTA DE VENTAS (Vendedor + Cliente + Costos)
try {
    $sql = "SELECT v.*, u.usuario as vendedor, c.nombre as cliente_nombre,
            (
                SELECT SUM(d.cantidad * COALESCE(NULLIF(d.costo_historico,0), p.precio_costo, 0))
                FROM detalle_ventas d 
                LEFT JOIN productos p ON d.id_producto = p.id
                WHERE d.id_venta = v.id
            ) as costo_total_venta
            FROM ventas v 
            LEFT JOIN usuarios u ON v.id_usuario = u.id 
            LEFT JOIN clientes c ON v.id_cliente = c.id
            WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada'";

    if ($id_usuario) $sql .= " AND v.id_usuario = " . intval($id_usuario);
    if ($metodo) $sql .= " AND v.metodo_pago = " . $conexion->quote($metodo);
    $sql .= " ORDER BY v.fecha DESC";

    $ventas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error crítico: " . $e->getMessage());
}

// 3. GASTOS Y RETIROS
$gastos_operativos = 0; $retiros_dueno = 0;
try {
    $sqlG = "SELECT categoria, SUM(monto) as total FROM gastos WHERE fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' GROUP BY categoria";
    $resG = $conexion->query($sqlG)->fetchAll(PDO::FETCH_ASSOC);
    foreach($resG as $rg) {
        if($rg['categoria'] == 'Retiro') $retiros_dueno += $rg['total'];
        else $gastos_operativos += $rg['total'];
    }
} catch (Exception $e) {}

// 4. CÁLCULOS PARA WIDGETS
$ingresos_ventas = 0; $costo_mercaderia = 0;
foreach($ventas as $v) {
    $ingresos_ventas += $v['total'];
    $costo_mercaderia += $v['costo_total_venta'];
}
$utilidad_neta = ($ingresos_ventas - $costo_mercaderia) - $gastos_operativos;
$caja_final = $utilidad_neta - $retiros_dueno;
$margen_p = ($ingresos_ventas > 0) ? ($utilidad_neta / $ingresos_ventas) * 100 : 0;

// 5. TOP PRODUCTOS
$sqlTop = "SELECT p.descripcion, SUM(d.cantidad) as cant FROM detalle_ventas d JOIN ventas v ON d.id_venta = v.id JOIN productos p ON d.id_producto = p.id
           WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada' GROUP BY p.id ORDER BY cant DESC LIMIT 10";
$top_productos = $conexion->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

$usuarios_db = $conexion->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes de Gestión | El 10</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* Liberamos el scroll para que el banner no "ocupe" toda la pantalla */
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; overflow-y: auto !important; }
        
        /* Aseguramos que el menú esté siempre al frente y reciba clics */
        .navbar-container { 
            position: relative; 
            z-index: 5000 !important; 
            background: white; 
            border-bottom: 4px solid #102A57; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }

        /* Banner Azul idéntico a Gastos */
        .header-blue { 
            background-color: #102A57; 
            color: white; 
            padding: 30px 0; 
            margin-bottom: 25px; 
            border-radius: 0 0 30px 30px; 
            box-shadow: 0 4px 15px rgba(16,42,87,0.25); 
            position: relative; 
            z-index: 1; 
        }
        .bg-icon-large { position: absolute; top: 50%; right: 20px; transform: translateY(-50%) rotate(-10deg); font-size: 8rem; opacity: 0.1; color: white; pointer-events: none; z-index: 0; }
        
        .stat-card { border: none; border-radius: 15px; padding: 15px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background: white; height: 100%; display: flex; align-items: center; justify-content: space-between; }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .bg-primary-soft { background-color: rgba(13,110,253,0.1); color: #0d6efd; }
        .bg-danger-soft { background-color: rgba(220,53,69,0.1); color: #dc3545; }
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .badge-margen { color: #000 !important; font-weight: 800; font-size: 0.8rem; }
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="navbar-container no-print">
        <?php include 'includes/layout_header.php'; ?>
    </div>

    <div class="header-blue">
        <i class="bi bi-graph-up-arrow bg-icon-large"></i>
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0 text-white">Reporte de Gestión</h2>
                <div class="d-flex gap-2">
                    <button onclick="exportarExcelPro()" class="btn btn-success rounded-pill fw-bold px-4 shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> EXCEL
                    </button>
                    <a href="reporte_financiero_pdf.php?f_inicio=<?php echo $inicio; ?>&f_fin=<?php echo $fin; ?>" target="_blank" class="btn btn-danger rounded-pill fw-bold px-4 shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> PDF
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1">INGRESOS</h6><h3 class="mb-0 fw-bold text-primary">$<?php echo number_format($ingresos_ventas, 0, ',', '.'); ?></h3></div>
                        <div class="icon-box bg-primary-soft"><i class="bi bi-cash-stack"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Egresos</h6><h3 class="mb-0 fw-bold text-danger">$<?php echo number_format($costo_mercaderia + $gastos_operativos, 0, ',', '.'); ?></h3></div>
                        <div class="icon-box bg-danger-soft"><i class="bi bi-cart-dash"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Utilidad</h6><h3 class="mb-0 fw-bold text-success">$<?php echo number_format($utilidad_neta, 0, ',', '.'); ?></h3><span class="badge bg-success-soft badge-margen"><?php echo number_format($margen_p, 1); ?>% Margen</span></div>
                        <div class="icon-box bg-success-soft"><i class="bi bi-graph-up"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Caja Real</h6><h3 class="mb-0 fw-bold <?php echo ($caja_final < 0) ? 'text-danger' : 'text-dark'; ?>">$<?php echo number_format($caja_final, 0, ',', '.'); ?></h3></div>
                        <div class="icon-box bg-dark-soft"><i class="bi bi-piggy-bank"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="card border-0 shadow-sm p-4 mb-4 table-container">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold">RANGO DE FECHAS</label>
                    <div class="input-group">
                        <input type="date" name="f_inicio" class="form-control" value="<?php echo $inicio; ?>">
                        <input type="date" name="f_fin" class="form-control" value="<?php echo $fin; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">CAJERO</label>
                    <select name="id_usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($usuarios_db as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($id_usuario == $u['id']) ? 'selected' : ''; ?>><?php echo $u['usuario']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><a href="?set_rango=hoy" class="btn btn-outline-secondary w-100 btn-sm">Hoy</a></div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel-fill"></i> ACTUALIZAR</button></div>
            </form>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="table-container">
                    <h5 class="fw-bold mb-4 text-secondary">Detalle de Operaciones</h5>
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0" id="tabla-export">
                            <thead class="bg-light">
                                <tr class="text-uppercase small fw-bold text-muted">
                                    <th>Ticket</th><th>Fecha</th><th>Vendedor</th><th>Cliente</th><th>Pago</th><th class="text-end">Venta</th><th class="text-end">Costo</th><th class="text-end">Margen</th><th class="text-center">Ver</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach($ventas as $v): 
    $cliente = !empty($v['cliente_nombre']) ? $v['cliente_nombre'] : 'Consumidor Final';
    // FIX PHP 8: Forzamos a que sean números (0 si es nulo) para que no tire el Deprecated
    $total_v = (float)($v['total'] ?? 0);
    $costo_v = (float)($v['costo_total_venta'] ?? 0);
    $margen_v = $total_v - $costo_v;
?>
<tr>
    <td class="fw-bold">#<?php echo $v['id']; ?></td>
    <td><?php echo date('d/m/y H:i', strtotime($v['fecha'])); ?></td>
    <td><?php echo $v['vendedor']; ?></td>
    <td><?php echo $cliente; ?></td>
    <td><span class="badge bg-light text-dark border"><?php echo $v['metodo_pago']; ?></span></td>
    <td class="text-end fw-bold">$<?php echo number_format($total_v, 0, ',', '.'); ?></td>
    <td class="text-end text-muted">$<?php echo number_format($costo_v, 0, ',', '.'); ?></td>
    <td class="text-end fw-bold text-success">$<?php echo number_format($margen_v, 0, ',', '.'); ?></td>
    <td class="text-center">
        <a href="ticket.php?id=<?php echo $v['id']; ?>" onclick="window.open(this.href, 'TicketView', 'width=350,height=600'); return false;" class="text-primary" title="Ver Ticket">
            <i class="bi bi-eye-fill"></i>
        </a>
    </td>
</tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="table-container h-100">
                    <h5 class="fw-bold mb-4 text-warning"><i class="bi bi-trophy-fill me-2"></i> Más Vendidos</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach($top_productos as $tp): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="small text-uppercase fw-bold text-dark"><?php echo $tp['descripcion']; ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo intval($tp['cant']); ?> un.</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/layout_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// 1. ACTIVACIÓN DEL MENÚ (Dropdowns y Botón Móvil)
document.addEventListener('DOMContentLoaded', function () {
    // Despierta los desplegables
    var dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdowns.map(function (el) { return new bootstrap.Dropdown(el); });

    // Despierta el botón de las tres rayitas en celulares
    var collapses = [].slice.call(document.querySelectorAll('.navbar-collapse'));
    collapses.map(function (el) { return new bootstrap.Collapse(el, { toggle: false }); });
});

// 2. FUNCIÓN DE EXCEL (Tu código original intacto)
function exportarExcelPro() {
    let table = document.getElementById("tabla-export");
    let rows = Array.from(table.querySelectorAll("tr"));
    let csvContent = "\uFEFF"; 
    rows.forEach(row => {
        let cols = Array.from(row.querySelectorAll("th, td")).map(cell => {
            let text = cell.innerText.replace(/\./g, "").replace("$", "").trim();
            return `"${text}"`;
        });
        csvContent += cols.join(";") + "\r\n";
    });
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Reporte_Completo_El10.csv";
    link.click();
}
</script>
</body>
</html>