<?php
// ver_encuestas.php - CON FILTROS AVANZADOS
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN ROBUSTA
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 2. CONSTRUIR FILTROS
$where = "WHERE 1=1";
$params = [];

// A. Filtro por Fecha
$fecha = $_GET['fecha'] ?? '';
if (!empty($fecha)) {
    $where .= " AND DATE(fecha) = ?";
    $params[] = $fecha;
}

// B. Filtro por Estrellas
$estrellas = $_GET['estrellas'] ?? '';
if (!empty($estrellas)) {
    $where .= " AND nivel = ?";
    $params[] = $estrellas;
}

// C. Filtro por Tipo de Cliente
$tipo = $_GET['tipo'] ?? '';
if ($tipo === 'anonimo') {
    $where .= " AND cliente_nombre = 'Anónimo'";
} elseif ($tipo === 'cliente') {
    $where .= " AND cliente_nombre != 'Anónimo'";
}

// 3. CONSULTAS SQL DINÁMICAS
try {
    // Obtener la lista filtrada
    $sql = "SELECT * FROM encuestas $where ORDER BY fecha DESC LIMIT 100";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular KPIs basados en el filtro actual
    $sqlAvg = "SELECT AVG(nivel) FROM encuestas $where";
    $stmtAvg = $conexion->prepare($sqlAvg);
    $stmtAvg->execute($params);
    $promedio = $stmtAvg->fetchColumn() ?: 0;

    $sqlCount = "SELECT COUNT(*) FROM encuestas $where";
    $stmtCount = $conexion->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();

} catch (Exception $e) {
    $lista = []; $total = 0; $promedio = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultados Encuestas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if (file_exists('includes/menu.php')) include 'includes/menu.php';
    elseif (file_exists('menu.php')) include 'menu.php';
    else echo "<div class='alert alert-warning text-center'>⚠️ No se encuentra el menú.</div>";
    ?>

    <div class="container pb-5 mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary"><i class="bi bi-chat-heart-fill text-danger"></i> Opiniones</h3>
            <a href="encuesta.php" target="_blank" class="btn btn-primary shadow-sm">
                <i class="bi bi-box-arrow-up-right"></i> Ver Formulario Cliente
            </a>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white rounded">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">📅 Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?php echo $fecha; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">⭐ Calificación</label>
                        <select name="estrellas" class="form-select">
                            <option value="">Todas</option>
                            <option value="5" <?php if($estrellas=='5') echo 'selected'; ?>>5 Estrellas (😍)</option>
                            <option value="4" <?php if($estrellas=='4') echo 'selected'; ?>>4 Estrellas (🙂)</option>
                            <option value="3" <?php if($estrellas=='3') echo 'selected'; ?>>3 Estrellas (😐)</option>
                            <option value="2" <?php if($estrellas=='2') echo 'selected'; ?>>2 Estrellas (☹️)</option>
                            <option value="1" <?php if($estrellas=='1') echo 'selected'; ?>>1 Estrella (😡)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">👤 Quién Opina</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="anonimo" <?php if($tipo=='anonimo') echo 'selected'; ?>>Solo Anónimos</option>
                            <option value="cliente" <?php if($tipo=='cliente') echo 'selected'; ?>>Clientes (Con Nombre)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="bi bi-filter"></i> Filtrar</button>
                        <?php if($fecha || $estrellas || $tipo): ?>
                            <a href="ver_encuestas.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card bg-warning text-dark border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <h2 class="display-5 fw-bold mb-0"><?php echo number_format($promedio, 1); ?></h2>
                        <small class="text-uppercase fw-bold opacity-75">Promedio (Selección)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-primary text-white border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <h2 class="display-5 fw-bold mb-0"><?php echo $total; ?></h2>
                        <small class="text-uppercase fw-bold opacity-75">Opiniones Encontradas</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold border-bottom">Resultados</div>
            <div class="list-group list-group-flush">
                <?php if (count($lista) > 0): ?>
                    <?php foreach ($lista as $row): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="<?php echo ($row['cliente_nombre']=='Anónimo') ? 'text-muted fst-italic' : 'text-primary'; ?>">
                                        <?php echo htmlspecialchars($row['cliente_nombre']); ?>
                                    </strong>
                                    <?php if (!empty($row['contacto'])): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-2 rounded-pill">
                                            <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($row['contacto']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo date('d/m H:i', strtotime($row['fecha'])); ?></small>
                            </div>
                            
                            <div class="my-1">
                                <?php for($i=0; $i<$row['nivel']; $i++) echo '<i class="bi bi-star-fill text-warning"></i>'; ?>
                                <?php for($i=$row['nivel']; $i<5; $i++) echo '<i class="bi bi-star text-muted opacity-25"></i>'; ?>
                            </div>

                            <?php if (!empty($row['comentario'])): ?>
                                <p class="mb-0 text-dark">"<?php echo htmlspecialchars($row['comentario']); ?>"</p>
                            <?php else: ?>
                                <p class="mb-0 text-muted small fst-italic">Sin comentario escrito</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-funnel fs-1 opacity-25"></i>
                        <p class="mt-3">No hay opiniones con estos filtros.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>