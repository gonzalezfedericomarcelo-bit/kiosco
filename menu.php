<?php
// menu.php - ACTUALIZADO CON ACCESOS A REVISTA Y TIENDA
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Conexión y Configuración
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', '../db.php']; 
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

// Configuración visual por defecto
$color_nav = '#212529'; 
$nombre_negocio = 'SISTEMA KIOSCO';
$logo_url = '';

if (isset($conexion)) {
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

$rol = $_SESSION['rol'] ?? 3; // 1:Admin, 2:Dueño, 3:Empleado
$pagina = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Estilos Originales Preservados */
    .navbar-custom { background: <?php echo $color_nav; ?> !important; padding: 10px 0; }
    .navbar-brand { font-weight: bold; color: white !important; letter-spacing: 0.5px; }
    .nav-link { color: rgba(255,255,255,0.9) !important; margin: 0 2px; transition: all 0.2s; font-size: 0.95rem; }
    .nav-link:hover { color: white !important; transform: translateY(-1px); }
    .nav-link.active { font-weight: bold; background: rgba(255,255,255,0.2); border-radius: 6px; color: white !important; }
    .logo-menu { max-height: 40px; margin-right: 10px; }
    
    .dropdown-menu { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border-radius: 10px; }
    .dropdown-item { padding: 8px 20px; font-size: 0.9rem; }
    .dropdown-item:active { background-color: <?php echo $color_nav; ?>; }
    .dropdown-item i { margin-right: 8px; opacity: 0.7; }
</style>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4 shadow-sm">
    <div class="container">
        
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <?php if(!empty($logo_url)): ?>
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
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($pagina, ['ventas.php','cierre_caja.php','devoluciones.php'])?'active':'';?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i> Caja
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="ventas.php"><i class="bi bi-cart4 text-success"></i> Nueva Venta</a></li>
                        <li><a class="dropdown-item" href="cierre_caja.php"><i class="bi bi-calculator text-primary"></i> Cerrar Caja</a></li>
                        <?php if($rol <= 2): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="devoluciones.php"><i class="bi bi-arrow-counterclockwise text-danger"></i> Devoluciones</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($pagina, ['productos.php','proveedores.php','bienes_uso.php'])?'active':'';?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam"></i> Inventario
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="productos.php"><i class="bi bi-tags-fill text-primary"></i> Productos</a></li>
                        
                        <?php if($rol <= 2): ?>
                            <li><a class="dropdown-item" href="proveedores.php"><i class="bi bi-truck text-dark"></i> Proveedores</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="bienes_uso.php"><i class="bi bi-hdd-network text-secondary"></i> Activos / Bienes de Uso</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($pagina, ['clientes.php','canje_puntos.php','ver_encuestas.php'])?'active':'';?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill"></i> El Club
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="clientes.php"><i class="bi bi-person-lines-fill text-info"></i> Clientes</a></li>
                        <li><a class="dropdown-item" href="canje_puntos.php"><i class="bi bi-gift text-success"></i> Canje de Puntos</a></li>
                        <li><a class="dropdown-item" href="ver_encuestas.php"><i class="bi bi-chat-heart text-danger"></i> Encuestas</a></li>
                    </ul>
                </li>

                <?php if($rol <= 2): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($pagina, ['gastos.php','mermas.php','precios_masivos.php','gestionar_cupones.php', 'admin_revista.php'])?'active':'';?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-wallet2"></i> Finanzas
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="gastos.php"><i class="bi bi-wallet2 text-danger"></i> Gastos y Retiros</a></li>
                        <li><a class="dropdown-item" href="mermas.php"><i class="bi bi-trash3 text-secondary"></i> Mermas y Roturas</a></li>
                        <li><a class="dropdown-item" href="precios_masivos.php"><i class="bi bi-graph-up-arrow text-warning"></i> Aumentos Masivos</a></li>
                        <li><a class="dropdown-item" href="gestionar_cupones.php"><i class="bi bi-ticket-perforated text-success"></i> Cupones de Descuento</a></li>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold" href="admin_revista.php"><i class="bi bi-newspaper text-danger"></i> Armar Revista Digital</a></li>
                        <li><a class="dropdown-item" href="tienda.php" target="_blank"><i class="bi bi-shop text-primary"></i> Ver Tienda Online</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($pagina, ['reportes.php','configuracion.php','usuarios.php','roles.php'])?'active':'';?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill"></i> Admin
                    </a>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="reportes.php"><i class="bi bi-bar-chart-fill text-primary"></i> Reportes</a></li>
                        <li><a class="dropdown-item" href="configuracion.php"><i class="bi bi-sliders text-dark"></i> Configuración</a></li>
                        <li><a class="dropdown-item" href="usuarios.php"><i class="bi bi-shield-lock text-secondary"></i> Usuarios y Roles</a></li>
                        <li><a class="dropdown-item" href="auditoria.php"><i class="bi bi-eye text-danger"></i> Auditoría</a></li>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>
            
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 bg-white bg-opacity-10 rounded-pill px-3 py-1" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span><?php echo $_SESSION['usuario'] ?? 'Usuario'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow mt-2">
                        <li><a class="dropdown-item fw-bold" href="perfil.php"><i class="bi bi-person-badge"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
</nav>