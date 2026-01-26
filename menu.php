<?php
// includes/menu.php - DISEÑO DARK PRO
if (session_status() === PHP_SESSION_NONE) session_start();
$rol = $_SESSION['rol'] ?? 3;
$pagina = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ESTILOS DEL MENÚ PRO */
    .navbar-custom { background: linear-gradient(90deg, #1a1a1a, #2c3e50); padding: 10px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .navbar-brand { font-family: 'Segoe UI', sans-serif; font-weight: 700; letter-spacing: 1px; color: #fff !important; }
    .nav-link { color: rgba(255,255,255,0.7) !important; font-weight: 500; transition: 0.3s; margin: 0 5px; border-radius: 5px; }
    .nav-link:hover { color: #fff !important; background: rgba(255,255,255,0.1); transform: translateY(-2px); }
    .nav-link.active { color: #fff !important; background: #0d6efd; font-weight: 700; box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4); }
    .nav-link i { margin-right: 6px; font-size: 1.1rem; vertical-align: text-bottom; }
    .user-badge { background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: white; }
</style>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-box-seam-fill text-primary"></i> KIOSCO<span class="text-primary">MANAGER</span>
        </a>
        
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#menuGlobal">
            <i class="bi bi-list fs-1"></i>
        </button>

        <div class="collapse navbar-collapse" id="menuGlobal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='dashboard.php'?'active':'';?>" href="dashboard.php"><i class="bi bi-speedometer2"></i> Inicio</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='ventas.php'?'active':'';?>" href="ventas.php"><i class="bi bi-cart4"></i> Caja</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='productos.php'?'active':'';?>" href="productos.php"><i class="bi bi-tags-fill"></i> Productos</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='clientes.php'?'active':'';?>" href="clientes.php"><i class="bi bi-people-fill"></i> Clientes</a></li>
                
                <?php if($rol <= 2): ?>
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='reportes.php'?'active':'';?>" href="reportes.php"><i class="bi bi-bar-chart-line-fill"></i> Reportes</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $pagina=='configuracion.php'?'active':'';?>" href="configuracion.php"><i class="bi bi-gear-fill"></i> Config</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                <a href="tienda.php" target="_blank" class="btn btn-outline-light btn-sm fw-bold rounded-pill px-3">
                    <i class="bi bi-shop"></i> Tienda
                </a>
                
                <div class="dropdown">
                    <a href="#" class="user-badge text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['usuario'] ?? 'Usuario'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-gear"></i> Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>