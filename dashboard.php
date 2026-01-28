<?php
// dashboard.php - VERSIÓN RESPONSIVA
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <title>Panel - Peca's Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-menu {
            transition: transform 0.2s; cursor: pointer; text-decoration: none; display: block;
            border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card-menu:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .icon-large { font-size: 3rem; margin-bottom: 10px; display: block; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark">Hola, <?php echo htmlspecialchars($nombre_usuario); ?> 👋</h4>
            <span class="badge bg-success rounded-pill px-3 py-2">Sistema Online</span>
        </div>

        <div class="row g-3 g-md-4">
            
            <div class="col-12 col-md-6 col-lg-4">
                <a href="ventas.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-cash-coin icon-large text-primary"></i>
                    <h3 class="fw-bold text-dark">Caja</h3>
                    <p class="text-muted mb-0">Vender y Cobrar</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="productos.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-box-seam icon-large text-success"></i>
                    <h3 class="fw-bold text-dark">Productos</h3>
                    <p class="text-muted mb-0">Stock y Precios</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="clientes.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-people-fill icon-large text-info"></i>
                    <h3 class="fw-bold text-dark">Clientes</h3>
                    <p class="text-muted mb-0">Deudores y Fiado</p>
                </a>
            </div>

            <?php if($_SESSION['rol'] <= 2): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="reportes.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-bar-chart-fill icon-large text-warning"></i>
                    <h3 class="fw-bold text-dark">Reportes</h3>
                    <p class="text-muted mb-0">Ventas y Ganancias</p>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="configuracion.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-gear-fill icon-large text-secondary"></i>
                    <h3 class="fw-bold text-dark">Configuración</h3>
                    <p class="text-muted mb-0">Datos del Negocio</p>
                </a>
            </div>
            <?php endif; ?>

            <div class="col-12 col-md-6 col-lg-4">
                <a href="perfil.php" class="card card-menu h-100 p-4 text-center">
                    <i class="bi bi-person-circle icon-large text-dark"></i>
                    <h3 class="fw-bold text-dark">Mi Perfil</h3>
                    <p class="text-muted mb-0">Firma y Clave</p>
                </a>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>