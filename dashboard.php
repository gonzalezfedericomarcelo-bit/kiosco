<?php
// dashboard.php - VERSIÓN FINAL (Con Alertas de Vencimiento)
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';

// 1. OBTENER DATOS DE USUARIO
$id_user = $_SESSION['usuario_id'];

$stmtUser = $conexion->prepare("SELECT nombre_completo, usuario, id_rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$id_user]);
$datosUsuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

$nombre_mostrar = !empty($datosUsuario['nombre_completo']) ? $datosUsuario['nombre_completo'] : $datosUsuario['usuario'];
$rol_usuario = $datosUsuario['id_rol'] ?? 3; 

// 2. ESTADO DE CAJA
$stmtCaja = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
$stmtCaja->execute([$id_user]);
$cajaAbierta = $stmtCaja->fetch(PDO::FETCH_ASSOC);
$estado_caja = $cajaAbierta ? 'ABIERTA' : 'CERRADA';

// 3. VENTAS Y TICKETS DE HOY
$hoy = date('Y-m-d');
$stmtVentas = $conexion->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as cantidad FROM ventas WHERE id_usuario = ? AND DATE(fecha) = ?");
$stmtVentas->execute([$id_user, $hoy]);
$resVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);

$vendido_hoy = $resVentas['total'];
$tickets_hoy = $resVentas['cantidad'];

// 4. ALERTAS (STOCK Y VENCIMIENTOS)
$alertas_stock = 0;
$alertas_vencimiento = 0;

if($rol_usuario <= 2) {
    // A. Alerta de Stock Bajo
    $stmtStock = $conexion->query("SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo AND activo = 1");
    $alertas_stock = $stmtStock->fetchColumn();
    // 5. ALERTAS VENCIMIENTOS (AGREGADO)
$alertas_vencimiento = 0;
if($rol_usuario <= 2) {
    // Busca configuración o usa 30 días por defecto
    $stmtConf = $conexion->query("SELECT dias_alerta_vencimiento FROM configuracion WHERE id=1");
    $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);
    $dias_venc = $conf['dias_alerta_vencimiento'] ?? 30;
    
    // Cuenta productos que vencen pronto
    $sqlVenc = "SELECT COUNT(*) FROM productos WHERE activo = 1 AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $dias_venc DAY)";
    $alertas_vencimiento = $conexion->query($sqlVenc)->fetchColumn();
}

    // B. Alerta de Vencimientos (NUEVO)
    // 1. Buscamos la config general de dias (ej. 30 dias)
    $stmtConf = $conexion->query("SELECT dias_alerta_vencimiento FROM configuracion WHERE id=1");
    $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);
    $dias_global = $conf['dias_alerta_vencimiento'] ?? 30;

    // 2. Contamos productos que vencen entre HOY y (HOY + DIAS)
    // La logica COALESCE(dias_alerta, ?) usa la alerta personalizada del producto si existe, sino la global
    $sqlVenc = "SELECT COUNT(*) FROM productos 
                WHERE activo = 1 
                AND fecha_vencimiento IS NOT NULL 
                AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL COALESCE(dias_alerta, ?) DAY)";
    
    $stmtVenc = $conexion->prepare($sqlVenc);
    $stmtVenc->execute([$dias_global]);
    $alertas_vencimiento = $stmtVenc->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Panel de Control - Kiosco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f2f4f7; font-family: 'Inter', sans-serif; padding-bottom: 80px; }
        .dash-header {
            background: linear-gradient(135deg, #111 0%, #333 100%);
            color: white; padding: 25px 20px 30px 20px;
            border-radius: 0 0 30px 30px; 
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
            transition: transform 0.2s, background 0.2s;
            cursor: pointer;
        }
        .stat-card:hover { background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
        .stat-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.75; letter-spacing: 0.5px; margin-bottom: 4px; color: white; }
        .stat-value { font-weight: 800; font-size: 1.1rem; line-height: 1.2; color: white; }
        
        .card-link { text-decoration: none; color: inherit; display: block; height: 100%; }

        .card-menu {
            background: white; border: none; border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: all 0.2s;
            text-decoration: none; color: #333; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 20px 10px;
            height: 100%; position: relative;
        }
        .card-menu:active { transform: scale(0.96); }
        .card-menu:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .icon-lg { font-size: 2.2rem; margin-bottom: 8px; }
        .menu-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 2px; }
        .menu-sub { font-size: 0.75rem; color: #888; }
        .card-caja {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white !important; align-items: flex-start; padding: 25px;
            grid-column: span 2;
        }
        .card-caja .menu-sub { color: rgba(255,255,255,0.7); }
        .grid-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (min-width: 768px) { .grid-menu { grid-template-columns: repeat(4, 1fr); gap: 20px; } }
        .badge-notify { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; border: 2px solid white; }
        .section-header { font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: #6c757d; margin: 10px 0 10px; letter-spacing: 1px; }
        #reloj { font-variant-numeric: tabular-nums; letter-spacing: 1px; }
        
        /* Estilo especial para vencimientos */
        .text-pulse { animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { color: #fff; } 50% { color: #ff8b94; } 100% { color: #fff; } }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="dash-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="fw-bold mb-0 fs-2">Hola, <?php echo htmlspecialchars(explode(' ', $nombre_mostrar)[0]); ?>!</h1>
                    <p class="opacity-75 mb-0 small">
                        <?php echo ($rol_usuario <= 2) ? 'Panel de Control Total' : 'Panel de Ventas'; ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-bold" id="reloj">--:--:--</div>
                    <span class="badge bg-white text-dark shadow-sm">ARGENTINA</span>
                </div>
            </div>
            
            <div class="row g-2 justify-content-center">
                <div class="col-4 col-md-2">
                    <a href="reportes.php?set_rango=hoy&f_inicio=<?php echo date('Y-m-d'); ?>&f_fin=<?php echo date('Y-m-d'); ?>&id_usuario=" class="card-link">
                        <div class="stat-card">
                            <small class="stat-label">Ventas de Hoy</small>
                            <div class="stat-value">$<?php echo number_format($vendido_hoy,0,',','.'); ?></div>
                        </div>
                    </a>
                </div>
                
                <div class="col-4 col-md-2">
                     <a href="reportes.php?filtro=hoy" class="card-link">
                        <div class="stat-card">
                            <small class="stat-label">Tickets</small>
                            <div class="stat-value"><?php echo $tickets_hoy; ?></div>
                        </div>
                    </a>
                </div>

                <div class="col-4 col-md-2">
                     <a href="historial_cajas.php" class="card-link">
                        <div class="stat-card">
                            <small class="stat-label">Caja</small>
                            <div class="stat-value">
                                <?php if($estado_caja=='ABIERTA'): ?>
                                    <span class="text-success"><i class="bi bi-circle-fill small"></i> ON</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="bi bi-x-circle-fill small"></i> OFF</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                     <a href="productos.php?filtro=stock_bajo" class="card-link">
                        <div class="stat-card">
                            <small class="stat-label">Stock Bajo</small>
                            <div class="stat-value <?php echo $alertas_stock > 0 ? 'text-warning' : ''; ?>">
                                <?php echo $alertas_stock; ?> <span style="font-size:0.7em; font-weight:400;">items</span>
                            </div>
                        </div>
                    </a>
                </div>

                <?php if($rol_usuario <= 2): ?>
                <div class="col-6 col-md-3">
                     <a href="productos.php?filtro=vencimientos" class="card-link">
                         <div class="stat-card" style="<?php echo $alertas_vencimiento > 0 ? 'background: rgba(220, 53, 69, 0.25); border-color: rgba(220, 53, 69, 0.5);' : ''; ?>">
                            <small class="stat-label <?php echo $alertas_vencimiento > 0 ? 'text-danger fw-bold' : ''; ?>">Vencimientos</small>
                            <div class="stat-value <?php echo $alertas_vencimiento > 0 ? 'text-pulse' : ''; ?>">
                                <?php echo $alertas_vencimiento; ?> <span style="font-size:0.7em; font-weight:400;">próx.</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        
        <div class="section-header">Operaciones</div>
        <div class="grid-menu">
            <a href="ventas.php" class="card-menu card-caja">
                <div class="d-flex justify-content-between w-100">
                    <i class="bi bi-cart4 fs-1"></i>
                    <i class="bi bi-arrow-right-circle fs-3 opacity-50"></i>
                </div>
                <div class="mt-2">
                    <div class="fs-3 fw-bold">CAJA</div>
                    <div class="menu-sub text-white opacity-75">Vender / Cobrar</div>
                </div>
            </a>
            <a href="productos.php" class="card-menu">
                <?php if($alertas_stock > 0 && $rol_usuario <= 2): ?>
                    <div class="badge-notify"><?php echo $alertas_stock; ?></div>
                <?php endif; ?>
                <i class="bi bi-box-seam icon-lg text-primary"></i>
                <div class="menu-title">Productos</div>
                <div class="menu-sub">Stock</div>
            </a>
            <a href="clientes.php" class="card-menu">
                <i class="bi bi-people-fill icon-lg text-info"></i>
                <div class="menu-title">Clientes</div>
                <div class="menu-sub">Ctas. Ctes.</div>
            </a>
        </div>

        <?php if($rol_usuario <= 2): ?>
            
            <div class="section-header mt-4">Marketing</div>
            <div class="grid-menu">
                <a href="admin_revista.php" class="card-menu border-bottom border-danger border-3">
                    <i class="bi bi-newspaper icon-lg text-danger"></i>
                    <div class="menu-title">Revista</div>
                    <div class="menu-sub">Ofertas</div>
                </a>
                <a href="tienda.php" target="_blank" class="card-menu border-bottom border-success border-3">
                    <i class="bi bi-shop icon-lg text-success"></i>
                    <div class="menu-title">Tienda</div>
                    <div class="menu-sub">Online</div>
                </a>
                <a href="gestionar_cupones.php" class="card-menu">
                    <i class="bi bi-ticket-perforated icon-lg text-warning"></i>
                    <div class="menu-title">Cupones</div>
                    <div class="menu-sub">Descuentos</div>
                </a>
                 <a href="ver_encuestas.php" class="card-menu">
                    <i class="bi bi-chat-heart icon-lg text-danger"></i>
                    <div class="menu-title">Encuestas</div>
                    <div class="menu-sub">Opiniones</div>
                </a>
            </div>

            <div class="section-header mt-4">Administración</div>
            <div class="grid-menu">
                <a href="reportes.php" class="card-menu">
                    <i class="bi bi-bar-chart-line-fill icon-lg text-primary"></i>
                    <div class="menu-title">Reportes</div>
                    <div class="menu-sub">Ganancias</div>
                </a>
                <a href="proveedores.php" class="card-menu">
                    <i class="bi bi-truck icon-lg text-dark"></i>
                    <div class="menu-title">Proveedores</div>
                    <div class="menu-sub">Pedidos</div>
                </a>
                <a href="usuarios.php" class="card-menu">
                    <i class="bi bi-people-fill icon-lg text-dark"></i>
                    <div class="menu-title">Usuarios</div>
                    <div class="menu-sub">y Roles</div>
                </a>
                <a href="configuracion.php" class="card-menu">
                    <i class="bi bi-gear-fill icon-lg text-secondary"></i>
                    <div class="menu-title">Configurar</div>
                    <div class="menu-sub">Sistema</div>
                </a>
            </div>

        <?php endif; ?>

        <div class="section-header mt-4">Cuenta</div>
        <div class="grid-menu pb-5">
             <a href="perfil.php" class="card-menu">
                <i class="bi bi-person-badge icon-lg text-primary"></i>
                <div class="menu-title">Mi Perfil</div>
                <div class="menu-sub">Editar</div>
            </a>
            <a href="logout.php" class="card-menu bg-light border">
                <i class="bi bi-power icon-lg text-danger"></i>
                <div class="menu-title text-danger">Salir</div>
                <div class="menu-sub">Cerrar Sesión</div>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'America/Argentina/Buenos_Aires' });
            document.getElementById('reloj').textContent = timeString;
        }
        setInterval(updateClock, 1000); updateClock();
    </script>
</body>
</html>