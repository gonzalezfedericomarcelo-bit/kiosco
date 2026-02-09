<?php
// auditoria.php - CENTRO DE CONTROL FORENSE & ESTADÍSTICAS
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN BLINDADA
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', 'includes/db.php'];
$conexion_ok = false;
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; $conexion_ok = true; break; } }
if (!$conexion_ok) die("<div class='alert alert-danger'>Error crítico: Base de datos no encontrada.</div>");

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
$rol_actual = $_SESSION['rol'] ?? 3;
if ($rol_actual > 2) { header("Location: dashboard.php"); exit; }

// --- HELPERS VISUALES ---
function getIcono($accion) {
    $a = strtoupper($accion);
    if(strpos($a, 'VENTA') !== false) return '<i class="bi bi-cash-coin text-success"></i>';
    if(strpos($a, 'ELIMIN') !== false || strpos($a, 'BORRAR') !== false) return '<i class="bi bi-trash3-fill text-danger"></i>';
    if(strpos($a, 'MODIFIC') !== false || strpos($a, 'EDITAR') !== false || strpos($a, 'CAMBIO') !== false) return '<i class="bi bi-pencil-square text-warning"></i>';
    if(strpos($a, 'CREAR') !== false || strpos($a, 'NUEVO') !== false || strpos($a, 'ALTA') !== false) return '<i class="bi bi-plus-circle-fill text-primary"></i>';
    if(strpos($a, 'LOGIN') !== false || strpos($a, 'INGRESO') !== false) return '<i class="bi bi-person-badge text-info"></i>';
    if(strpos($a, 'PAUSA') !== false || strpos($a, 'ESPERA') !== false) return '<i class="bi bi-pause-circle-fill text-secondary"></i>';
    if(strpos($a, 'RECUPER') !== false) return '<i class="bi bi-play-circle-fill text-info"></i>';
    if(strpos($a, 'CUPON') !== false || strpos($a, 'DESCUENTO') !== false) return '<i class="bi bi-ticket-perforated-fill text-purple" style="color:#6f42c1"></i>';
    return '<i class="bi bi-activity"></i>';
}

function getEstiloFila($accion, $detalles) {
    $a = strtoupper($accion);
    $d = strtoupper($detalles);
    if(strpos($a, 'ELIMIN') !== false) return 'table-danger bg-opacity-10'; // Alerta roja suave
    if(strpos($d, 'DESCUENTO MANUAL') !== false) return 'table-warning bg-opacity-25'; // Alerta amarilla
    if(strpos($a, 'VENTA') !== false) return ''; 
    return '';
}

// 2. EXPORTAR CSV (Igual que antes, pero mejorado)
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Reporte_Total_'.date('Y-m-d_Hi').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'FECHA', 'USUARIO', 'ACCION', 'DETALLES']);
    
    $sqlExp = "SELECT a.*, u.usuario as u_nombre FROM auditoria a LEFT JOIN usuarios u ON a.id_usuario = u.id ORDER BY a.fecha DESC LIMIT 5000";
    $rows = $conexion->query($sqlExp)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        fputcsv($output, [$r['id'], $r['fecha'], $r['u_nombre'] ?? 'Sistema', $r['accion'], $r['detalles']]);
    }
    fclose($output); exit;
}

// 3. FILTROS & BÚSQUEDA
$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['f_inicio'])) { $where .= " AND DATE(a.fecha) >= ?"; $params[] = $_GET['f_inicio']; }
if (!empty($_GET['f_fin'])) { $where .= " AND DATE(a.fecha) <= ?"; $params[] = $_GET['f_fin']; }
if (!empty($_GET['f_user'])) { $where .= " AND a.id_usuario = ?"; $params[] = $_GET['f_user']; }
if (!empty($_GET['f_tipo'])) { 
    $tipo = $_GET['f_tipo'];
    if($tipo == 'ventas') $where .= " AND (a.accion LIKE '%VENTA%' OR a.accion LIKE '%COBRO%')";
    if($tipo == 'stock') $where .= " AND (a.accion LIKE '%PRODUCTO%' OR a.accion LIKE '%STOCK%' OR a.accion LIKE '%PRECIO%')";
    if($tipo == 'clientes') $where .= " AND (a.accion LIKE '%CLIENTE%')";
    if($tipo == 'seguridad') $where .= " AND (a.accion LIKE '%ELIMIN%' OR a.accion LIKE '%LOGIN%' OR a.detalles LIKE '%Descuento Manual%')";
}
if (!empty($_GET['q'])) {
    $where .= " AND (a.accion LIKE ? OR a.detalles LIKE ?)";
    $term = "%" . $_GET['q'] . "%"; $params[] = $term; $params[] = $term;
}

// 4. PAGINACIÓN
$pag = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
$limit = 50; // Más registros por página para ver más data
$offset = ($pag - 1) * $limit;

// QUERY PRINCIPAL
$sql = "SELECT a.*, u.usuario, u.foto_perfil 
        FROM auditoria a 
        LEFT JOIN usuarios u ON a.id_usuario = u.id 
        $where 
        ORDER BY a.fecha DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginador
$sqlC = "SELECT COUNT(*) FROM auditoria a $where";
$stmtC = $conexion->prepare($sqlC);
$stmtC->execute($params);
$total_regs = $stmtC->fetchColumn();
$total_pags = ceil($total_regs / $limit);

// 5. ESTADÍSTICAS DASHBOARD (KPIs)
$hoy = date('Y-m-d');
// Movimientos hoy
$kpi_hoy = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE DATE(fecha) = '$hoy'")->fetchColumn();
// Ventas hoy (aprox por log)
$kpi_ventas = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE accion LIKE '%VENTA%' AND DATE(fecha) = '$hoy'")->fetchColumn();
// Alertas (Eliminaciones o Descuentos manuales)
$kpi_alertas = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE (accion LIKE '%ELIMIN%' OR detalles LIKE '%Descuento Manual%') AND DATE(fecha) = '$hoy'")->fetchColumn();
// Nuevos Clientes (Total Histórico y Hoy)
$kpi_clientes_total = $conexion->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$kpi_clientes_hoy = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE accion LIKE '%CLIENTE%' AND accion LIKE '%NUEVO%' AND DATE(fecha) = '$hoy'")->fetchColumn();

// Lista usuarios para filtro
$users = $conexion->query("SELECT id, usuario FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoría Global | KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        /* TARJETAS KPI */
        .card-kpi { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s; overflow: hidden; }
        .card-kpi:hover { transform: translateY(-3px); }
        .kpi-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1.5rem; }
        
        /* TABLA ESTILIZADA */
        .table-log { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table-log thead { background: #343a40; color: white; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
        .user-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6; }
        
        /* FILTROS */
        .filter-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        /* BADGES */
        .badge-acc { font-weight: 600; letter-spacing: 0.5px; padding: 5px 10px; }
        
        /* DETALLE */
        .detail-text { font-family: 'Courier New', monospace; font-size: 0.85rem; color: #555; cursor: pointer; }
        .detail-text:hover { color: #0d6efd; text-decoration: underline; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container-fluid px-4 py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0"><i class="bi bi-eye-fill text-primary"></i> Centro de Auditoría</h3>
                <p class="text-muted small m-0">Registro completo de movimientos, seguridad y clientes.</p>
            </div>
            <div>
                <a href="auditoria.php?exportar=csv" class="btn btn-success fw-bold shadow-sm">
                    <i class="bi bi-filetype-csv"></i> Descargar Reporte
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-kpi p-3">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold m-0"><?php echo number_format($kpi_hoy); ?></h3>
                            <small class="text-muted fw-bold">Movimientos Hoy</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-kpi p-3">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold m-0"><?php echo number_format($kpi_ventas); ?></h3>
                            <small class="text-muted fw-bold">Ventas Hoy</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-kpi p-3">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold m-0"><?php echo number_format($kpi_clientes_total); ?></h3>
                            <small class="text-muted fw-bold">Clientes Totales (+<?php echo $kpi_clientes_hoy; ?> hoy)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card card-kpi p-3 border-start border-4 <?php echo $kpi_alertas>0 ? 'border-danger' : 'border-light'; ?>">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold m-0"><?php echo number_format($kpi_alertas); ?></h3>
                            <small class="text-muted fw-bold">Alertas Hoy</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-box">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="date" name="f_inicio" class="form-control form-control-sm" value="<?php echo $_GET['f_inicio']??''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="date" name="f_fin" class="form-control form-control-sm" value="<?php echo $_GET['f_fin']??''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tipo Movimiento</label>
                    <select name="f_tipo" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <option value="ventas" <?php echo (isset($_GET['f_tipo']) && $_GET['f_tipo']=='ventas')?'selected':''; ?>>💰 Ventas y Caja</option>
                        <option value="stock" <?php echo (isset($_GET['f_tipo']) && $_GET['f_tipo']=='stock')?'selected':''; ?>>📦 Stock y Precios</option>
                        <option value="clientes" <?php echo (isset($_GET['f_tipo']) && $_GET['f_tipo']=='clientes')?'selected':''; ?>>👥 Clientes</option>
                        <option value="seguridad" <?php echo (isset($_GET['f_tipo']) && $_GET['f_tipo']=='seguridad')?'selected':''; ?>>🚨 Seguridad / Borrados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Usuario</label>
                    <select name="f_user" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['f_user']) && $_GET['f_user']==$u['id'])?'selected':''; ?>>
                                <?php echo $u['usuario']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Búsqueda Específica</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control" placeholder="Ej: cupón, nombre producto..." value="<?php echo $_GET['q']??''; ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="auditoria.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="table-responsive table-log">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalles Completos</th>
                        <th class="text-end pe-4">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($registros) > 0): ?>
                        <?php foreach($registros as $r): 
                            $bgRow = getEstiloFila($r['accion'], $r['detalles']);
                            $avatar = !empty($r['foto_perfil']) ? 'uploads/'.$r['foto_perfil'] : 'img/default_user.png';
                            if(!file_exists($avatar)) $avatar = 'img/no-image.png'; // Fallback
                        ?>
                        <tr class="<?php echo $bgRow; ?>">
                            <td class="ps-4 fw-bold text-muted">#<?php echo $r['id']; ?></td>
                            <td style="font-size:0.9rem;">
                                <?php echo date('d/m/Y', strtotime($r['fecha'])); ?><br>
                                <span class="text-muted small"><?php echo date('H:i:s', strtotime($r['fecha'])); ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $avatar; ?>" class="user-avatar me-2">
                                    <span class="fw-bold small"><?php echo $r['usuario'] ?? 'Sistema'; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php echo getIcono($r['accion']); ?>
                                    <span class="fw-bold text-dark small"><?php echo $r['accion']; ?></span>
                                </div>
                            </td>
                            <td class="detail-text" onclick="verModal('<?php echo addslashes($r['accion']); ?>', `<?php echo addslashes($r['detalles']); ?>`)">
                                <?php 
                                    $prev = strip_tags($r['detalles']);
                                    echo substr($prev, 0, 90) . (strlen($prev)>90 ? '...' : '');
                                    
                                    // Etiquetas inteligentes en el detalle
                                    if(strpos($r['detalles'], 'Descuento Manual') !== false) echo ' <span class="badge bg-warning text-dark ms-1">DESC. MANUAL</span>';
                                    if(strpos($r['accion'], 'RECUPER') !== false) echo ' <span class="badge bg-info text-dark ms-1">RECUPERADO</span>';
                                    if(strpos($r['accion'], 'PAUSA') !== false) echo ' <span class="badge bg-secondary ms-1">EN ESPERA</span>';
                                ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border" onclick="verModal('<?php echo addslashes($r['accion']); ?>', `<?php echo addslashes($r['detalles']); ?>`)">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No hay movimientos registrados con estos filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pags > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($pag<=1)?'disabled':''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pag-1; ?>&<?php echo http_build_query($_GET); ?>">Anterior</a>
                </li>
                <li class="page-item disabled"><span class="page-link">Página <?php echo $pag; ?> de <?php echo $total_pags; ?></span></li>
                <li class="page-item <?php echo ($pag>=$total_pags)?'disabled':''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pag+1; ?>&<?php echo http_build_query($_GET); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>

    <div class="modal fade" id="modalAudit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="mTitulo"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;" id="mCuerpo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verModal(titulo, detalle) {
            document.getElementById('mTitulo').innerText = titulo;
            document.getElementById('mCuerpo').innerText = detalle;
            new bootstrap.Modal(document.getElementById('modalAudit')).show();
        }
    </script>
</body>
</html>