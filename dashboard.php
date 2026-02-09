<?php
// dashboard.php - DISEÑO COMPACTO Y MODERNO (Horizontal)
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';

// --- MISMAS FUNCIONES Y LÓGICA QUE ANTES (INTACTO) ---

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

// 4. ALERTAS
$alertas_stock = 0;
$alertas_vencimiento = 0;
$alertas_cumple = 0;

if($rol_usuario <= 2) {
    // A. Stock
    $stmtStock = $conexion->query("SELECT COUNT(p.id) FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.stock_actual <= p.stock_minimo AND p.activo = 1 AND p.tipo != 'combo'");
    $alertas_stock = $stmtStock->fetchColumn();

    // B. Vencimientos
    $stmtConf = $conexion->query("SELECT dias_alerta_vencimiento FROM configuracion WHERE id=1");
    $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);
    $dias_global = $conf['dias_alerta_vencimiento'] ?? 30;
    $sqlVenc = "SELECT COUNT(*) FROM productos WHERE activo = 1 AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL COALESCE(dias_alerta, ?) DAY)";
    $stmtVenc = $conexion->prepare($sqlVenc);
    $stmtVenc->execute([$dias_global]);
    $alertas_vencimiento = $stmtVenc->fetchColumn();

    // C. Cumpleaños
    try {
        $sqlCumple = "SELECT COUNT(*) FROM clientes WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE()) AND DAY(fecha_nacimiento) = DAY(CURDATE())";
        $alertas_cumple = $conexion->query($sqlCumple)->fetchColumn();
    } catch(Exception $e) { $alertas_cumple = 0; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Panel - Kiosco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f0f2f5;
            --card-bg: #ffffff;
            --text-main: #2c3e50;
            --accent: #0d6efd;
        }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; padding-bottom: 60px; }
        
        /* HEADER COMPACTO */
        .header-section {
            background: #fff;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        /* WIDGETS DE DATOS (SUPERIOR) */
        .info-card {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .info-card .label { font-size: 0.7rem; text-transform: uppercase; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-card .value { font-size: 1.1rem; font-weight: 800; color: #212529; line-height: 1.1; }
        
        /* Colores de Borde para Identificar rápido */
        .b-blue { border-left-color: #0d6efd; }
        .b-purple { border-left-color: #6f42c1; }
        .b-green { border-left-color: #198754; }
        .b-red { border-left-color: #dc3545; }
        .b-orange { border-left-color: #fd7e14; }
        .b-yellow { border-left-color: #ffc107; }

        /* MENÚ HORIZONTAL (ESTILO LISTA PERO EN GRID) */
        .menu-btn {
            background: white;
            border: 1px solid #eef0f3;
            border-radius: 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            height: 100%;
        }
        .menu-btn:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
            transform: translateX(3px);
        }
        .menu-icon-box {
            width: 45px; height: 45px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .menu-text { flex-grow: 1; }
        .m-title { font-weight: 700; font-size: 0.95rem; display: block; line-height: 1.2; }
        .m-sub { font-size: 0.75rem; color: #888; display: block; }

        /* BANNER CAJA */
        .caja-banner {
            background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .caja-banner:hover { transform: scale(1.01); color: white; }

        /* UTILIDADES */
        .section-title { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #adb5bd; margin-bottom: 10px; letter-spacing: 1px; }
        .badge-alert { background: #dc3545; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: auto; }
        
        /* GRID RESPONSIVE AJUSTADA */
        .grid-compact { display: grid; grid-template-columns: repeat(1, 1fr); gap: 10px; }
        @media (min-width: 576px) { .grid-compact { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 992px) { .grid-compact { grid-template-columns: repeat(3, 1fr); } }

        .pulse-text { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="header-section">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold m-0 text-dark">Hola, <?php echo htmlspecialchars(explode(' ', $nombre_mostrar)[0]); ?></h4>
                <small class="text-muted"><?php echo ($rol_usuario <= 2) ? 'Administrador' : 'Vendedor'; ?></small>
            </div>
            <div class="text-end lh-1">
                <div class="fw-bold fs-5" id="reloj">--:--</div>
                <small class="text-muted" style="font-size: 0.65rem;">ARG</small>
            </div>
        </div>
    </div>

    <div class="container">
        
        <div class="row g-2 row-cols-2 row-cols-md-3 row-cols-lg-6 mb-4">
            
            <div class="col">
                <a href="reportes.php?filtro=hoy" class="info-card b-blue">
                    <div class="label">Ventas Hoy</div>
                    <div class="value text-primary">$<?php echo number_format($vendido_hoy,0,',','.'); ?></div>
                </a>
            </div>
            
            <div class="col">
                <a href="reportes.php?filtro=hoy" class="info-card b-purple">
                    <div class="label">Tickets</div>
                    <div class="value"><?php echo $tickets_hoy; ?></div>
                </a>
            </div>

            <div class="col">
                <a href="historial_cajas.php" class="info-card <?php echo $estado_caja=='ABIERTA'?'b-green':'b-red'; ?>">
                    <div class="label">Caja</div>
                    <div class="value">
                        <?php if($estado_caja=='ABIERTA'): ?>
                            <span class="text-success"><i class="bi bi-circle-fill" style="font-size:8px; vertical-align:middle;"></i> ON</span>
                        <?php else: ?>
                            <span class="text-danger">CERRADA</span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="productos.php?filtro=bajo_stock" class="info-card b-orange">
                    <div class="label">Stock Bajo</div>
                    <div class="value <?php echo $alertas_stock > 0 ? 'text-danger' : ''; ?>">
                        <?php echo $alertas_stock; ?>
                    </div>
                </a>
            </div>

            <?php if($rol_usuario <= 2): ?>
            <div class="col">
                <a href="productos.php?filtro=vencimientos" class="info-card b-red">
                    <div class="label">Vencimientos</div>
                    <div class="value <?php echo $alertas_vencimiento > 0 ? 'text-danger pulse-text' : ''; ?>">
                        <?php echo $alertas_vencimiento; ?>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="clientes.php?filtro=cumple" class="info-card b-yellow">
                    <div class="label">Cumpleaños</div>
                    <div class="value <?php echo $alertas_cumple > 0 ? 'text-warning' : ''; ?>">
                        <?php echo $alertas_cumple; ?>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <a href="ventas.php" class="caja-banner">
            <div class="d-flex align-items-center">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-cart4 fs-3"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1">Punto de Venta</div>
                    <div class="small opacity-75">Cobrar / Facturar</div>
                </div>
            </div>
            <i class="bi bi-chevron-right fs-4"></i>
        </a>

        <div class="section-title">Gestión Diaria</div>
        <div class="grid-compact mb-4">
            
            <a href="productos.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-primary"><i class="bi bi-box-seam"></i></div>
                <div class="menu-text">
                    <span class="m-title">Productos</span>
                    <span class="m-sub">Precios y Stock</span>
                </div>
                <?php if($alertas_stock > 0 && $rol_usuario <= 2): ?>
                    <span class="badge-alert"><?php echo $alertas_stock; ?></span>
                <?php endif; ?>
            </a>

            <a href="clientes.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-info"><i class="bi bi-people-fill"></i></div>
                <div class="menu-text">
                    <span class="m-title">Clientes</span>
                    <span class="m-sub">Ctas. Corrientes</span>
                </div>
            </a>

            <a href="proveedores.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-dark"><i class="bi bi-truck"></i></div>
                <div class="menu-text">
                    <span class="m-title">Proveedores</span>
                    <span class="m-sub">Pedidos y Compras</span>
                </div>
            </a>
            
            <a href="gastos.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-secondary"><i class="bi bi-cash-coin"></i></div>
                <div class="menu-text">
                    <span class="m-title">Gastos</span>
                    <span class="m-sub">Salidas de Caja</span>
                </div>
            </a>

        </div>

        <?php if($rol_usuario <= 2): ?>
        
        <div class="section-title">Marketing & Extras</div>
        <div class="grid-compact mb-4">
            <a href="admin_revista.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-danger"><i class="bi bi-newspaper"></i></div>
                <div class="menu-text">
                    <span class="m-title">Revista Digital</span>
                    <span class="m-sub">Publicar Ofertas</span>
                </div>
            </a>
            
            <a href="gestionar_cupones.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-warning"><i class="bi bi-ticket-perforated"></i></div>
                <div class="menu-text">
                    <span class="m-title">Cupones</span>
                    <span class="m-sub">Descuentos</span>
                </div>
            </a>

            <a href="tienda.php" target="_blank" class="menu-btn">
                <div class="menu-icon-box bg-light text-success"><i class="bi bi-shop"></i></div>
                <div class="menu-text">
                    <span class="m-title">Ver Tienda</span>
                    <span class="m-sub">Vista Cliente</span>
                </div>
            </a>

             <a href="ver_encuestas.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-danger"><i class="bi bi-chat-heart"></i></div>
                <div class="menu-text">
                    <span class="m-title">Encuestas</span>
                    <span class="m-sub">Opiniones</span>
                </div>
            </a>
        </div>

        <div class="section-title">Administración</div>
        <div class="grid-compact mb-4">
            <a href="reportes.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-primary"><i class="bi bi-bar-chart-line-fill"></i></div>
                <div class="menu-text">
                    <span class="m-title">Reportes</span>
                    <span class="m-sub">Estadísticas</span>
                </div>
            </a>

            <a href="usuarios.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-dark"><i class="bi bi-shield-lock-fill"></i></div>
                <div class="menu-text">
                    <span class="m-title">Usuarios</span>
                    <span class="m-sub">Accesos y Roles</span>
                </div>
            </a>

            <a href="configuracion.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-secondary"><i class="bi bi-gear-fill"></i></div>
                <div class="menu-text">
                    <span class="m-title">Configuración</span>
                    <span class="m-sub">General</span>
                </div>
            </a>
        </div>

        <?php endif; ?>

        <div class="section-title">Mi Cuenta</div>
        <div class="grid-compact pb-4">
             <a href="perfil.php" class="menu-btn">
                <div class="menu-icon-box bg-light text-primary"><i class="bi bi-person-circle"></i></div>
                <div class="menu-text">
                    <span class="m-title">Mi Perfil</span>
                    <span class="m-sub">Mis Datos</span>
                </div>
            </a>
            <a href="logout.php" class="menu-btn" style="border-color: #f5c2c7; background: #fff5f5;">
                <div class="menu-icon-box text-danger"><i class="bi bi-power"></i></div>
                <div class="menu-text text-danger">
                    <span class="m-title">Cerrar Sesión</span>
                    <span class="m-sub">Salir del sistema</span>
                </div>
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'America/Argentina/Buenos_Aires' });
            document.getElementById('reloj').textContent = timeString;
        }
        setInterval(updateClock, 1000); updateClock();
    </script>
</body>
</html>