<?php
// devoluciones.php - CORREGIDO BASADO EN TU DASHBOARD.PHP
session_start();

// 1. CONEXIÓN (Igual que en dashboard.php)
if (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} elseif (file_exists('db.php')) {
    require_once 'db.php';
} else {
    die("Error: No se encuentra db.php");
}

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$venta = null;
$items = [];

// 3. BUSCAR TICKET
if (isset($_GET['id_ticket']) && $_GET['id_ticket'] > 0) {
    $id = $_GET['id_ticket'];

    // Buscar Venta
    $sql = "SELECT v.*, c.nombre as cliente 
            FROM ventas v 
            LEFT JOIN clientes c ON v.id_cliente = c.id 
            WHERE v.id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($venta) {
        // Buscar Productos (USANDO 'detalle_ventas' PLURAL SEGÚN TU SQL)
        $sqlDet = "SELECT d.*, p.descripcion 
                   FROM detalle_ventas d 
                   JOIN productos p ON d.id_producto = p.id 
                   WHERE d.id_venta = ?";
        $stmtDet = $conexion->prepare($sqlDet);
        $stmtDet->execute([$id]);
        $items = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $mensaje = "No se encontró el Ticket #$id";
    }
}

// 4. PROCESAR DEVOLUCIÓN
if (isset($_POST['devolver'])) {
    try {
        $conexion->beginTransaction();
        
        // Crear tabla si no existe (Seguridad)
        $conexion->exec("CREATE TABLE IF NOT EXISTS devoluciones (id INT AUTO_INCREMENT PRIMARY KEY, id_venta_original INT, id_producto INT, cantidad DECIMAL(10,2), monto_devuelto DECIMAL(10,2), fecha DATETIME, id_usuario INT)");

        // Insertar devolución
        $stmt = $conexion->prepare("INSERT INTO devoluciones (id_venta_original, id_producto, cantidad, monto_devuelto, fecha, id_usuario) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$_POST['id_venta'], $_POST['id_producto'], $_POST['cantidad'], $_POST['monto'], $_SESSION['usuario_id']]);

        // Retornar Stock
        $stmtUP = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
        $stmtUP->execute([$_POST['cantidad'], $_POST['id_producto']]);

        $conexion->commit();
        echo "<script>alert('Devolución procesada correctamente'); window.location='devoluciones.php?id_ticket=".$_POST['id_venta']."';</script>";
        
    } catch (Exception $e) {
        $conexion->rollBack();
        echo "<script>alert('Error: ".$e->getMessage()."');</script>";
    }
}

// 5. LISTA LATERAL
$ultimas = $conexion->query("SELECT v.id, v.total, v.fecha, c.nombre 
                             FROM ventas v 
                             LEFT JOIN clientes c ON v.id_cliente = c.id 
                             ORDER BY v.id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devoluciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if(file_exists('includes/menu.php')) {
        include 'includes/menu.php';
    } elseif(file_exists('menu.php')) {
        include 'menu.php';
    } else {
        echo "<div class='alert alert-danger'>No se encuentra el archivo de menú.</div>";
    }
    ?>

    <div class="container pb-5 mt-4">
        <h3 class="mb-4 fw-bold text-secondary"><i class="bi bi-arrow-counterclockwise"></i> Gestión de Devoluciones</h3>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-3 border-0">
                    <div class="card-header bg-dark text-white fw-bold">Buscar Ticket</div>
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <input type="number" name="id_ticket" class="form-control" placeholder="N° Ticket" required value="<?php echo $_GET['id_ticket'] ?? ''; ?>">
                            <button class="btn btn-primary fw-bold">Buscar</button>
                        </form>
                        <?php if($mensaje) echo "<div class='alert alert-warning mt-2 p-2 small'>$mensaje</div>"; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold border-bottom">Últimos Tickets</div>
                    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach($ultimas as $u): ?>
                            <a href="devoluciones.php?id_ticket=<?php echo $u['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-primary">#<?php echo $u['id']; ?></span>
                                    <small class="d-block text-muted" style="font-size:0.75rem"><?php echo $u['nombre'] ?? 'Consumidor Final'; ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-dark">$<?php echo number_format($u['total'], 0); ?></span>
                                    <small class="d-block text-muted" style="font-size:0.75rem"><?php echo date('d/m H:i', strtotime($u['fecha'])); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php if($venta): ?>
                    <div class="card shadow border-0">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5">Ticket #<?php echo $venta['id']; ?></span>
                            <span class="badge bg-white text-primary"><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border d-flex justify-content-between align-items-center">
                                <span><strong>Cliente:</strong> <?php echo $venta['cliente'] ?? 'Consumidor Final'; ?></span>
                                <span class="badge bg-success">Completada</span>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center">Cant.</th>
                                            <th class="text-end">Precio</th>
                                            <th class="text-end">Subtotal</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($items as $i): ?>
                                        <tr>
                                            <td><?php echo $i['descripcion']; ?></td>
                                            <td class="text-center fw-bold"><?php echo floatval($i['cantidad']); ?></td>
                                            <td class="text-end text-muted">$<?php echo number_format($i['precio_historico'] ?? $i['precio_unitario'], 2); ?></td>
                                            <td class="text-end fw-bold">$<?php echo number_format($i['subtotal'], 2); ?></td>
                                            <td class="text-center">
                                                <form method="POST" onsubmit="return confirm('¿Devolver ítem?');">
                                                    <input type="hidden" name="devolver" value="1">
                                                    <input type="hidden" name="id_venta" value="<?php echo $venta['id']; ?>">
                                                    <input type="hidden" name="id_producto" value="<?php echo $i['id_producto']; ?>">
                                                    <input type="hidden" name="cantidad" value="<?php echo $i['cantidad']; ?>">
                                                    <input type="hidden" name="monto" value="<?php echo $i['subtotal']; ?>">
                                                    <button class="btn btn-outline-danger btn-sm fw-bold">
                                                        <i class="bi bi-arrow-return-left"></i> Devolver
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary text-center py-5 border-0">
                        <i class="bi bi-receipt display-1 opacity-25"></i>
                        <h4 class="mt-3 text-muted fw-light">Selecciona un ticket</h4>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>