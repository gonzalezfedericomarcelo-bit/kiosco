<?php
// auditoria.php - CENTRO DE CONTROL FORENSE PROFESIONAL
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN ROBUSTA
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', 'includes/db.php'];
$conexion_ok = false;
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; $conexion_ok = true; break; } }
if (!$conexion_ok) die("Error crítico: No se encuentra la base de datos.");

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
$rol_actual = $_SESSION['rol'] ?? 3;
if ($rol_actual > 2) { 
    // Si es empleado, lo sacamos (o podrías dejarlo ver solo sus acciones, pero por seguridad mejor fuera)
    header("Location: dashboard.php"); exit; 
}

// 2. LÓGICA DE EXPORTACIÓN (CSV)
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=auditoria_'.date('Y-m-d_H-i').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'FECHA', 'USUARIO', 'ACCION', 'DETALLES']);
    
    // Reconstruimos la query sin límite
    $sqlExport = "SELECT a.*, u.usuario as nombre_user 
                  FROM auditoria a 
                  LEFT JOIN usuarios u ON a.id_usuario = u.id 
                  ORDER BY a.fecha DESC LIMIT 1000"; // Límite de seguridad
    $rows = $conexion->query($sqlExport)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        fputcsv($output, [$row['id'], $row['fecha'], $row['nombre_user'] ?? 'Sistema/Borrado', $row['accion'], $row['detalles']]);
    }
    fclose($output);
    exit;
}

// 3. FILTROS
$where = "WHERE 1=1";
$params = [];

// Filtro Fechas
if (!empty($_GET['f_inicio'])) {
    $where .= " AND DATE(a.fecha) >= ?";
    $params[] = $_GET['f_inicio'];
}
if (!empty($_GET['f_fin'])) {
    $where .= " AND DATE(a.fecha) <= ?";
    $params[] = $_GET['f_fin'];
}

// Filtro Usuario
if (!empty($_GET['f_usuario'])) {
    $where .= " AND a.id_usuario = ?";
    $params[] = $_GET['f_usuario'];
}

// Filtro Texto (Busca en acción o detalles)
if (!empty($_GET['f_buscar'])) {
    $where .= " AND (a.accion LIKE ? OR a.detalles LIKE ?)";
    $term = "%" . $_GET['f_buscar'] . "%";
    $params[] = $term;
    $params[] = $term;
}

// 4. PAGINACIÓN
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Conteo total para paginación
$sqlCount = "SELECT COUNT(*) FROM auditoria a $where";
$stmtCount = $conexion->prepare($sqlCount);
$stmtCount->execute($params);
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta Principal
$sql = "SELECT a.*, u.usuario as nombre_user, u.foto_perfil, u.rol 
        FROM auditoria a 
        LEFT JOIN usuarios u ON a.id_usuario = u.id 
        $where 
        ORDER BY a.fecha DESC 
        LIMIT $registros_por_pagina OFFSET $offset";
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de Usuarios para el filtro
$usuarios = $conexion->query("SELECT id, usuario FROM usuarios ORDER BY usuario ASC")->fetchAll(PDO::FETCH_ASSOC);

// ESTADÍSTICAS RÁPIDAS (HOY)
$hoy = date('Y-m-d');
$stats_hoy = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE DATE(fecha) = '$hoy'")->fetchColumn();
$stats_del = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE DATE(fecha) = '$hoy' AND (accion LIKE '%Elimin%' OR accion LIKE '%Borrar%')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Auditoría Forense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        
        /* Badges de Acción Inteligentes */
        .badge-accion { font-size: 0.8rem; padding: 6px 10px; border-radius: 6px; font-weight: 600; width: 100%; display: block; text-align: center; }
        .acc-danger { background-color: #ffe5e5; color: #d63384; border: 1px solid #fcc2d7; }
        .acc-success { background-color: #e6fcf5; color: #0ca678; border: 1px solid #96f2d7; }
        .acc-warning { background-color: #fff9db; color: #f59f00; border: 1px solid #ffec99; }
        .acc-info { background-color: #e7f5ff; color: #1c7ed6; border: 1px solid #a5d8ff; }
        .acc-default { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }

        /* Tarjetas de Stats */
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        /* Tabla y Diseño */
        .table-custom { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .table-custom thead { background: #f8f9fa; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #888; }
        .avatar-small { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .details-cell { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; color: #555; }
        .details-cell:hover { color: #0d6efd; text-decoration: underline; }

        /* Ajustes Móvil */
        @media (max-width: 768px) {
            .mobile-hide { display: none; }
            .card-log-mobile { background: white; margin-bottom: 10px; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #ccc; }
            .card-log-mobile.danger { border-left-color: #dc3545; }
            .card-log-mobile.success { border-left-color: #198754; }
            .card-log-mobile.warning { border-left-color: #ffc107; }
        }
    </style>
</head>
<body>

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container-fluid px-lg-5 pb-5">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark"><i class="bi bi-shield-check text-primary"></i> Auditoría del Sistema</h2>
                <p class="text-muted small mb-0">Registro forense de todas las operaciones realizadas.</p>
            </div>
            <div class="col-md-6 text-end d-none d-md-block">
                <button class="btn btn-outline-dark btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 bg-white d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-activity"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $stats_hoy; ?></h4>
                        <small class="text-muted">Movimientos Hoy</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 bg-white d-flex align-items-center">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3"><i class="bi bi-trash3"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $stats_del; ?></h4>
                        <small class="text-muted">Eliminaciones Hoy</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="stat-card p-3 bg-white h-100 d-flex align-items-center justify-content-between cursor-pointer" onclick="toggleFiltros()">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-dark me-3"><i class="bi bi-funnel"></i></div>
                        <div>
                            <h5 class="fw-bold mb-0">Filtros de Búsqueda</h5>
                            <small class="text-muted">Haz clic para expandir</small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down text-muted" id="icon-filter"></i>
                </div>
            </div>
        </div>

        <div class="collapse mb-4 <?php echo (!empty($_GET))?'show':''; ?>" id="panelFiltros">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-light rounded-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Desde</label>
                            <input type="date" name="f_inicio" class="form-control" value="<?php echo $_GET['f_inicio']??''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Hasta</label>
                            <input type="date" name="f_fin" class="form-control" value="<?php echo $_GET['f_fin']??''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Usuario</label>
                            <select name="f_usuario" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach($usuarios as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['f_usuario']) && $_GET['f_usuario']==$user['id'])?'selected':''; ?>>
                                        <?php echo $user['usuario']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Buscar Texto</label>
                            <input type="text" name="f_buscar" class="form-control" placeholder="Ej: stock, borrado..." value="<?php echo $_GET['f_buscar']??''; ?>">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                            <a href="auditoria.php" class="btn btn-outline-secondary">Limpiar</a>
                            <button type="submit" name="exportar" value="csv" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar CSV</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-search"></i> Filtrar Resultados</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-custom d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID / Fecha</th>
                        <th>Usuario</th>
                        <th class="text-center">Tipo Acción</th>
                        <th>Detalles (Click para ver)</th>
                        <th class="text-end pe-4">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($logs) > 0): ?>
                        <?php foreach($logs as $log): 
                            // Lógica de colores según acción
                            $classBadge = 'acc-default';
                            $txt = strtolower($log['accion']);
                            if(strpos($txt, 'elimin')!==false || strpos($txt, 'borrar')!==false || strpos($txt, 'merma')!==false) $classBadge = 'acc-danger';
                            elseif(strpos($txt, 'crear')!==false || strpos($txt, 'alta')!==false || strpos($txt, 'nuevo')!==false) $classBadge = 'acc-success';
                            elseif(strpos($txt, 'modificar')!==false || strpos($txt, 'editar')!==false || strpos($txt, 'actualizar')!==false) $classBadge = 'acc-warning';
                            elseif(strpos($txt, 'login')!==false || strpos($txt, 'ingreso')!==false) $classBadge = 'acc-info';

                            // Foto Usuario
                            $avatar = !empty($log['foto_perfil']) && file_exists('uploads/'.$log['foto_perfil']) ? 'uploads/'.$log['foto_perfil'] : 
                                      (!empty($log['foto_perfil']) && file_exists('img/usuarios/'.$log['foto_perfil']) ? 'img/usuarios/'.$log['foto_perfil'] : 'img/no-image.png');
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">#<?php echo $log['id']; ?></div>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $avatar; ?>" class="avatar-small">
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $log['nombre_user'] ?? 'Sistema/Desconocido'; ?></div>
                                        <small class="text-muted"><?php echo ($log['rol'] ?? 0) <= 2 ? 'Admin/Dueño' : 'Empleado'; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-accion <?php echo $classBadge; ?>">
                                    <?php echo $log['accion']; ?>
                                </span>
                            </td>
                            <td class="details-cell" onclick="verDetalle('<?php echo htmlspecialchars($log['detalles']); ?>', '<?php echo $log['accion']; ?>')">
                                <?php echo substr($log['detalles'], 0, 80) . (strlen($log['detalles'])>80 ? '...' : ''); ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border" onclick="verDetalle('<?php echo htmlspecialchars($log['detalles']); ?>', '<?php echo $log['accion']; ?>')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron registros con esos filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-md-none mt-3">
            <?php foreach($logs as $log): 
                 $classBorder = 'border-secondary';
                 $txt = strtolower($log['accion']);
                 if(strpos($txt, 'elimin')!==false) $classBorder = 'danger';
                 elseif(strpos($txt, 'alta')!==false) $classBorder = 'success';
                 elseif(strpos($txt, 'modificar')!==false) $classBorder = 'warning';
            ?>
            <div class="card-log-mobile <?php echo $classBorder; ?>" onclick="verDetalle('<?php echo htmlspecialchars($log['detalles']); ?>', '<?php echo $log['accion']; ?>')">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-dark"><?php echo date('d/m H:i', strtotime($log['fecha'])); ?></span>
                    <span class="fw-bold text-uppercase small"><?php echo $log['accion']; ?></span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-person-circle me-2 text-muted"></i>
                    <span class="fw-bold text-dark"><?php echo $log['nombre_user'] ?? 'Sistema'; ?></span>
                </div>
                <div class="text-muted small text-truncate">
                    <?php echo $log['detalles']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination">
                <li class="page-item <?php echo ($pagina_actual<=1)?'disabled':''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pagina_actual-1; ?>&f_inicio=<?php echo $_GET['f_inicio']??''; ?>&f_usuario=<?php echo $_GET['f_usuario']??''; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                
                <li class="page-item disabled"><span class="page-link"><?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span></li>
                
                <li class="page-item <?php echo ($pagina_actual>=$total_paginas)?'disabled':''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pagina_actual+1; ?>&f_inicio=<?php echo $_GET['f_inicio']??''; ?>&f_usuario=<?php echo $_GET['f_usuario']??''; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Detalle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light font-monospace p-4" id="modalBody" style="white-space: pre-wrap; word-break: break-all;">
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFiltros() {
            var el = document.getElementById('panelFiltros');
            var icon = document.getElementById('icon-filter');
            if (el.classList.contains('show')) {
                new bootstrap.Collapse(el, {toggle: true}).hide();
                icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
            } else {
                new bootstrap.Collapse(el, {toggle: true}).show();
                icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
            }
        }

        function verDetalle(texto, titulo) {
            document.getElementById('modalTitle').innerText = titulo;
            document.getElementById('modalBody').innerText = texto;
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        }
    </script>
</body>
</html>
