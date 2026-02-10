<?php
// gastos.php - VERSIÓN FINAL CON TICKET VISUAL Y REPORTE PDF
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

// 2. SEGURIDAD
$permisos = $_SESSION['permisos'] ?? [];
$rol = $_SESSION['rol'] ?? 3;
if (!in_array('gestionar_gastos', $permisos) && $rol > 2) { header("Location: dashboard.php"); exit; }

// 3. VERIFICAR CAJA
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$usuario_id]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$caja) { header("Location: apertura_caja.php"); exit; }
$id_caja_sesion = $caja['id'];

// 4. PROCESAR GASTO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $desc = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $cat = $_POST['categoria'];
    
    $stmt = $conexion->prepare("INSERT INTO gastos (descripcion, monto, categoria, fecha, id_usuario, id_caja_sesion) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([$desc, $monto, $cat, $_SESSION['usuario_id'], $id_caja_sesion]);
    header("Location: gastos.php?msg=ok"); exit;
}

// 5. DATOS
$gastos = $conexion->query("SELECT g.*, u.usuario FROM gastos g JOIN usuarios u ON g.id_usuario = u.id ORDER BY g.fecha DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// KPIS
$hoy = date('Y-m-d');
$totalHoy = $conexion->query("SELECT SUM(monto) FROM gastos WHERE DATE(fecha) = '$hoy'")->fetchColumn() ?: 0;
$cantHoy = $conexion->query("SELECT COUNT(*) FROM gastos WHERE DATE(fecha) = '$hoy'")->fetchColumn() ?: 0;
$mesActual = date('Y-m');
$totalMes = $conexion->query("SELECT SUM(monto) FROM gastos WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mesActual'")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Gastos</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ESTILOS DE BANNER Y WIDGETS */
        .header-blue {
            background-color: #102A57;
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
            position: relative;
            z-index: 1;
        }
        .bg-icon-large { position: absolute; top: 50%; right: 20px; transform: translateY(-50%) rotate(-10deg); font-size: 10rem; opacity: 0.1; color: white; pointer-events: none; z-index: 0; }
        
        .stat-card {
            border: none; border-radius: 15px; padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
            background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }

        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-control-lg { font-size: 1rem; padding: 0.75rem 1rem; }
        .badge-cat { font-weight: 500; letter-spacing: 0.5px; }
        
        /* CURSOR POINTER PARA LA TABLA */
        .fila-gasto { cursor: pointer; transition: background 0.2s; }
        .fila-gasto:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    
    <?php include 'includes/layout_header.php'; ?>

    <div class="header-blue">
        <i class="bi bi-wallet2 bg-icon-large"></i>
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-white">Gastos y Retiros</h2>
                    <p class="opacity-75 mb-0 text-white">Hacé clic en la lista para ver el ticket.</p>
                </div>
                <div>
                    <a href="reporte_gastos.php" target="_blank" class="btn btn-outline-light rounded-pill fw-bold px-4">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i> Reporte PDF
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Gasto Diario</h6><h2 class="mb-0 fw-bold text-danger">$<?php echo number_format($totalHoy, 2, ',', '.'); ?></h2></div>
                        <div class="icon-box bg-danger-soft"><i class="bi bi-calendar-event"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Movimientos Hoy</h6><h2 class="mb-0 fw-bold text-primary"><?php echo $cantHoy; ?></h2></div>
                        <div class="icon-box bg-primary-soft"><i class="bi bi-list-check"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div><h6 class="text-muted small fw-bold mb-1 text-uppercase">Acumulado Mes</h6><h2 class="mb-0 fw-bold text-dark">$<?php echo number_format($totalMes, 2, ',', '.'); ?></h2></div>
                        <div class="icon-box bg-warning-soft"><i class="bi bi-calendar3"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0 text-danger">
                        <i class="bi bi-dash-circle-fill me-2"></i> Nuevo Retiro
                    </div>
                    <div class="card-body bg-light rounded-bottom">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Monto ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-danger fw-bold">$</span>
                                    <input type="number" step="0.01" name="monto" class="form-control form-control-lg fw-bold border-start-0 text-danger" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" required placeholder="Ej: Pago Proveedor">
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted text-uppercase">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Proveedores">🚚 Proveedores</option>
                                    <option value="Servicios">💡 Servicios</option>
                                    <option value="Alquiler">🏠 Alquiler</option>
                                    <option value="Sueldos">👥 Sueldos</option>
                                    <option value="Retiro">💸 Retiro Ganancias</option>
                                    <option value="Insumos">🧻 Insumos</option>
                                    <option value="Otros">📦 Otros</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                                <i class="bi bi-check-lg me-2"></i> REGISTRAR SALIDA
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history me-2 text-secondary"></i> Últimos Movimientos</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Fecha</th>
                                    <th>Detalle</th>
                                    <th>Categoría</th>
                                    <th class="text-end pe-4">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($gastos) > 0): ?>
                                    <?php foreach($gastos as $g): 
                                        $icono = 'bi-box-seam';
                                        if($g['categoria'] == 'Proveedores') $icono = 'bi-truck';
                                        if($g['categoria'] == 'Servicios') $icono = 'bi-lightning-charge';
                                        if($g['categoria'] == 'Sueldos') $icono = 'bi-people';
                                        if($g['categoria'] == 'Retiro') $icono = 'bi-cash-stack';
                                        
                                        // Datos JSON para el ticket
                                        $jsonData = htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr class="fila-gasto" onclick="verTicket(<?php echo $jsonData; ?>)">
                                        <td class="ps-4 text-muted small">
                                            <?php echo date('d/m H:i', strtotime($g['fecha'])); ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($g['descripcion']); ?></div>
                                            <small class="text-muted"><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($g['usuario']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border badge-cat">
                                                <i class="bi <?php echo $icono; ?> me-1"></i> <?php echo $g['categoria']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-danger fw-bold pe-4">
                                            -$<?php echo number_format($g['monto'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No hay gastos recientes.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/layout_footer.php'; ?>

    <script>
        if(new URLSearchParams(window.location.search).get('msg') === 'ok') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Gasto registrado', showConfirmButton: false, timer: 3000 });
        }

        // FUNCIÓN DEL TICKET (MODAL)
        function verTicket(gasto) {
            // Formatear monto
            let montoF = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(gasto.monto);
            let fechaF = new Date(gasto.fecha).toLocaleString();

            Swal.fire({
                background: '#fff',
                width: 350,
                html: `
                    <div style="font-family: 'Courier New', monospace; text-align: center; color: #000;">
                        <h3 style="font-weight: bold; margin-bottom: 5px;">COMPROBANTE DE SALIDA</h3>
                        <p style="font-size: 12px; margin-bottom: 15px;">#ID: ${gasto.id} - ${fechaF}</p>
                        
                        <div style="border-bottom: 2px dashed #ccc; margin: 10px 0;"></div>
                        
                        <div style="text-align: left; margin: 10px 0;">
                            <p style="margin: 5px 0;"><strong>RESPONSABLE:</strong><br>${gasto.usuario}</p>
                            <p style="margin: 5px 0;"><strong>CONCEPTO:</strong><br>${gasto.descripcion}</p>
                            <p style="margin: 5px 0;"><strong>CATEGORÍA:</strong><br>${gasto.categoria}</p>
                        </div>
                        
                        <div style="border-bottom: 2px dashed #ccc; margin: 15px 0;"></div>
                        
                        <h1 style="color: #dc3545; font-weight: bold; margin: 10px 0;">-${montoF}</h1>
                        
                        <div style="border-bottom: 2px dashed #ccc; margin: 15px 0;"></div>
                        
                        <p style="font-size: 11px; font-style: italic;">Comprobante interno no válido como factura fiscal.</p>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                backdrop: `rgba(0,0,0,0.6)`
            });
        }
    </script>
</body>
</html>