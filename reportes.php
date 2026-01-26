<?php
// reportes.php - TU LÓGICA ORIGINAL + MENÚ GLOBAL
session_start();
require_once 'includes/db.php';

// SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

// LOGICA DE FECHAS (Presets rápidos)
$tipo_filtro = $_GET['rango'] ?? 'mes'; 
$inicio = $_GET['f_inicio'] ?? date('Y-m-01');
$fin = $_GET['f_fin'] ?? date('Y-m-d');

// Ajuste rápido de fechas si usan los botones
if(isset($_GET['set_rango'])) {
    if($_GET['set_rango'] == 'hoy') { $inicio = date('Y-m-d'); $fin = date('Y-m-d'); }
    if($_GET['set_rango'] == 'ayer') { $inicio = date('Y-m-d', strtotime("-1 days")); $fin = date('Y-m-d', strtotime("-1 days")); }
    if($_GET['set_rango'] == 'mes') { $inicio = date('Y-m-01'); $fin = date('Y-m-t'); }
}

$id_usuario = $_GET['id_usuario'] ?? '';
$metodo = $_GET['metodo'] ?? '';
$cliente = $_GET['cliente'] ?? '';

// CONSULTA
$sql = "SELECT v.*, u.usuario, c.nombre as cliente 
        FROM ventas v 
        JOIN usuarios u ON v.id_usuario = u.id 
        JOIN clientes c ON v.id_cliente = c.id
        WHERE v.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND v.estado = 'completada'";

if ($id_usuario) $sql .= " AND v.id_usuario = $id_usuario";
if ($metodo) $sql .= " AND v.metodo_pago = '$metodo'";
if ($cliente) $sql .= " AND c.nombre LIKE '%$cliente%'";

$sql .= " ORDER BY v.fecha DESC";
$ventas = $conexion->query($sql)->fetchAll();

// TOTALES WIDGETS
$total_ingresos = 0;
$total_operaciones = count($ventas);
$mp_total = 0;

foreach($ventas as $v) {
    $total_ingresos += $v->total;
    if($v->metodo_pago == 'MP') $mp_total += $v->total;
}
$usuarios_db = $conexion->query("SELECT * FROM usuarios")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <title>Reportes - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* TUS ESTILOS ORIGINALES */
        body { background-color: #f8f9fa; }
        .card-menu { transition: transform 0.2s; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card-menu:hover { transform: translateY(-3px); }
        
        .filter-bar { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .input-group-text { background-color: #e9ecef; border: 1px solid #ced4da; }
        .btn-preset { font-size: 0.85rem; font-weight: 600; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="filter-bar mb-4">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    
                    <div class="col-12 mb-2">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Filtros Rápidos:</small><br>
                        <button type="submit" name="set_rango" value="hoy" class="btn btn-outline-primary btn-sm btn-preset rounded-pill">Hoy</button>
                        <button type="submit" name="set_rango" value="ayer" class="btn btn-outline-secondary btn-sm btn-preset rounded-pill">Ayer</button>
                        <button type="submit" name="set_rango" value="mes" class="btn btn-outline-success btn-sm btn-preset rounded-pill">Este Mes</button>
                    </div>

                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="f_inicio" class="form-control" value="<?php echo $inicio; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                            <input type="date" name="f_fin" class="form-control" value="<?php echo $fin; ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <select name="id_usuario" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach($usuarios_db as $u): ?>
                                    <option value="<?php echo $u->id; ?>" <?php if($id_usuario == $u->id) echo 'selected'; ?>>
                                        <?php echo $u->usuario; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                            <i class="bi bi-funnel-fill"></i> APLICAR
                        </button>
                    </div>
                    
                    <div class="col-md-2">
                         <a href="generar_pdf.php?f_inicio=<?php echo $inicio; ?>&f_fin=<?php echo $fin; ?>&id_usuario=<?php echo $id_usuario; ?>&metodo=<?php echo $metodo; ?>&cliente=<?php echo $cliente; ?>" 
                           target="_blank" 
                           class="btn btn-danger w-100 fw-bold shadow-sm">
                            <i class="bi bi-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-menu border-start border-5 border-success p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-bold">Ingresos Totales</h6>
                            <h2 class="fw-bold text-success mb-0">$ <?php echo number_format($total_ingresos, 2); ?></h2>
                        </div>
                        <i class="bi bi-cash-stack text-success fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-menu border-start border-5 border-primary p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-bold">Cant. Ventas</h6>
                            <h2 class="fw-bold text-primary mb-0"><?php echo $total_operaciones; ?></h2>
                        </div>
                        <i class="bi bi-bag-check text-primary fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-menu border-start border-5 border-info p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted small fw-bold">MercadoPago</h6>
                            <h2 class="fw-bold text-info mb-0">$ <?php echo number_format($mp_total, 2); ?></h2>
                        </div>
                        <i class="bi bi-qr-code text-info fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-secondary">Detalle de Operaciones</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Ticket</th>
                                <th>Fecha</th>
                                <th>Cajero</th>
                                <th>Cliente</th>
                                <th>Método</th>
                                <th class="text-end pe-4">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ventas as $v): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?php echo str_pad($v->id, 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($v->fecha)); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill">
                                        <i class="bi bi-person-fill"></i> <?php echo $v->usuario; ?>
                                    </span>
                                </td>
                                <td><?php echo $v->cliente; ?></td>
                                <td><?php echo $v->metodo_pago; ?></td>
                                <td class="text-end pe-4 fw-bold text-dark">$ <?php echo number_format($v->total, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> 
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>