<?php
session_start();
require_once 'includes/db.php';

// Seguridad
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// Procesar Creación de Sorteo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_sorteo'])) {
    $titulo = $_POST['titulo'];
    $precio = $_POST['precio'];
    $cant = $_POST['cantidad'];
    $fecha = $_POST['fecha'];
    
    $stmt = $conexion->prepare("INSERT INTO sorteos (titulo, precio_ticket, cantidad_tickets, fecha_sorteo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titulo, $precio, $cant, $fecha]);
    $idSorteo = $conexion->lastInsertId();
    
    // Guardar premios (Simplificado: 1er, 2do, 3er lugar como texto o ID producto se manejará en detalle)
    // Por ahora redirigimos al detalle para configurar premios
    header("Location: detalle_sorteo.php?id=$idSorteo&msg=creado");
    exit;
}

// Listar Sorteos
$sorteos = $conexion->query("SELECT * FROM sorteos ORDER BY estado ASC, fecha_sorteo DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    .header-blue { background-color: #102A57; color: white; padding: 40px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; position: relative; overflow: hidden; }
    .bg-icon-large { position: absolute; top: 50%; right: 20px; transform: translateY(-50%) rotate(-10deg); font-size: 10rem; opacity: 0.1; color: white; }
    .card-sorteo { transition: transform 0.2s; border:none; shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .card-sorteo:hover { transform: translateY(-5px); }
</style>

<div class="header-blue">
    <i class="bi bi-ticket-perforated-fill bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Gestión de Sorteos</h2>
                <p class="opacity-75 mb-0">Crea rifas, vende tickets y sortea premios.</p>
            </div>
            <button class="btn btn-light text-primary fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNuevoSorteo">
                <i class="bi bi-plus-lg me-2"></i> Nueva Rifa
            </button>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <?php foreach($sorteos as $s): 
            $badge = $s['estado'] == 'activo' ? 'bg-success' : ($s['estado']=='finalizado'?'bg-secondary':'bg-danger');
            $vendidos = $conexion->query("SELECT COUNT(*) FROM sorteo_tickets WHERE id_sorteo = {$s['id']}")->fetchColumn();
            $progreso = ($vendidos / $s['cantidad_tickets']) * 100;
        ?>
        <div class="col-md-4">
            <div class="card card-sorteo h-100 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge <?php echo $badge; ?> rounded-pill"><?php echo strtoupper($s['estado']); ?></span>
                        <small class="text-muted"><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime($s['fecha_sorteo'])); ?></small>
                    </div>
                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($s['titulo']); ?></h5>
                    <h3 class="text-primary fw-bold">$<?php echo number_format($s['precio_ticket'], 0, ',', '.'); ?> <small class="fs-6 text-muted">/ticket</small></h3>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Vendidos: <?php echo $vendidos; ?></span>
                            <span>Total: <?php echo $s['cantidad_tickets']; ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progreso; ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pt-0 pb-3">
                    <a href="detalle_sorteo.php?id=<?php echo $s['id']; ?>" class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                        ADMINISTRAR / SORTEAR
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalNuevoSorteo" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold">Nueva Rifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Título del Sorteo</label>
                    <input type="text" name="titulo" class="form-control" required placeholder="Ej: Rifa Día de los Enamorados">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Precio Ticket ($)</label>
                        <input type="number" name="precio" class="form-control" required step="0.01">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Cant. Tickets</label>
                        <input type="number" name="cantidad" class="form-control" value="100" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Fecha del Sorteo</label>
                    <input type="date" name="fecha" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" name="crear_sorteo" class="btn btn-primary w-100 rounded-pill fw-bold">CREAR RIFA</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>