<?php
// menu.php - VERSIÓN FINAL UNIFORME (SIN COLORES RAROS)
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. CONEXIÓN SEGURA
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php'];
$db_encontrada = false;
foreach ($rutas_db as $ruta) {
    if (file_exists($ruta)) {
        require_once $ruta;
        $db_encontrada = true;
        break;
    }
}

// Configuración por defecto
$color_nav = '#212529'; 
$nombre_negocio = 'SISTEMA KIOSCO';
$logo_url = '';

if ($db_encontrada && isset($conexion)) {
    try {
        $stmt = $conexion->query("SELECT * FROM configuracion WHERE id=1");
        if ($stmt) {
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);
            $conf = (is_object($conf)) ? (array)$conf : $conf;
            if ($conf) {
                $color_nav = $conf['color_barra_nav'] ?? $color_nav;
                $nombre_negocio = $conf['nombre_negocio'] ?? $nombre_negocio;
                $logo_url = $conf['logo_url'] ?? $logo_url;
            }
        }
    } catch (Exception $e) { }
}

$rol = $_SESSION['rol'] ?? 3;
$pagina = basename($_SERVER['PHP_SELF']);
?>
<style>
    .navbar-custom { background: <?php echo $color_nav; ?> !important; padding: 10px 0; }
    .navbar-brand { font-weight: bold; color: white !important; letter-spacing: 0.5px; }
    .nav-link { color: rgba(255,255,255,0.85) !important; margin: 0 4px; transition: all 0.2s; }
    .nav-link:hover { color: white !important; transform: translateY(-1px); }
    .nav-link.active { font-weight: bold; background: rgba(255,255,255,0.2); border-radius: 6px; color: white !important; }
    .logo-menu { max-height: 40px; margin-right: 10px; }
</style>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <?php if(!empty($logo_url) && file_exists($logo_url)): ?>
                <img src="<?php echo $logo_url; ?>" class="logo-menu" alt="Logo">
            <?php else: ?>
                <i class="bi bi-shop window me-2"></i> 
            <?php endif; ?>
            <span><?php echo $nombre_negocio; ?></span>
        </a>
        
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#menuGlobal">
            <i class="bi bi-list fs-1"></i>
        </button>

        <div class="collapse navbar-collapse" id="menuGlobal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='dashboard.php'?'active':'';?>" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Inicio
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='ventas.php'?'active':'';?>" href="ventas.php">
                        <i class="bi bi-cart4"></i> Caja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='cierre_caja.php'?'active':'';?>" href="cierre_caja.php">
                        <i class="bi bi-cash-coin"></i> Cerrar Caja
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='productos.php'?'active':'';?>" href="productos.php">
                        <i class="bi bi-tags-fill"></i> Productos
                    </a>
                </li>

                <?php if($rol <= 2): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='precios_masivos.php'?'active':'';?>" href="precios_masivos.php">
                        <i class="bi bi-graph-up-arrow"></i> Aumentos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='proveedores.php'?'active':'';?>" href="proveedores.php">
                        <i class="bi bi-truck"></i> Proveedores
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $pagina=='clientes.php'?'active':'';?>" href="clientes.php">
                        <i class="bi bi-people-fill"></i> Clientes
                    </a>
                </li>

                <?php if($rol <= 2): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pagina=='reportes.php'?'active':'';?>" href="reportes.php">
                            <i class="bi bi-bar-chart"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pagina=='configuracion.php'?'active':'';?>" href="configuracion.php">
                            <i class="bi bi-gear"></i> Config
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small opacity-75 d-none d-lg-block">
                    <i class="bi bi-person-circle"></i> <?php echo $_SESSION['usuario'] ?? 'Usuario'; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm fw-bold rounded-pill px-3">Salir</a>
            </div>
        </div>
    </div>
</nav>