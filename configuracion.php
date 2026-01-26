<?php
// configuracion.php - MENU GLOBAL + CUPONES AVANZADOS
session_start();
require_once 'includes/db.php';

// SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

// 1. GUARDAR DATOS NEGOCIO
if (isset($_POST['btn_config'])) {
    $stmt = $conexion->prepare("UPDATE configuracion SET nombre_negocio=?, telefono_whatsapp=?, email_notificaciones=?, direccion_local=? WHERE id=1");
    $stmt->execute([$_POST['nombre'], $_POST['whatsapp'], $_POST['email'], $_POST['direccion']]);
    $msg = "Configuración guardada.";
}

// 2. CREAR CUPÓN AVANZADO
if (isset($_POST['btn_cupon'])) {
    $codigo = strtoupper($_POST['codigo']);
    $desc = $_POST['descuento'];
    $fecha = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : NULL;
    $cliente = !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : NULL;

    try {
        $stmt = $conexion->prepare("INSERT INTO cupones (codigo, descuento_porcentaje, fecha_limite, id_cliente) VALUES (?, ?, ?, ?)");
        $stmt->execute([$codigo, $desc, $fecha, $cliente]);
    } catch (Exception $e) {
        $error_cupon = "El código ya existe.";
    }
}

// 3. BORRAR CUPÓN
if (isset($_GET['borrar_cupon'])) {
    $conexion->query("DELETE FROM cupones WHERE id=" . (int)$_GET['borrar_cupon']);
    header("Location: configuracion.php"); exit;
}

// DATOS
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch();
// Traemos cupones con nombre de cliente si existe
$sql_cupones = "SELECT cp.*, cl.nombre as nombre_cliente 
                FROM cupones cp 
                LEFT JOIN clientes cl ON cp.id_cliente = cl.id 
                ORDER BY cp.id DESC";
$cupones = $conexion->query($sql_cupones)->fetchAll();
$clientes = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container">
        <div class="row g-4">
            
            <div class="col-md-5">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-shop"></i> Datos Generales</div>
                    <div class="card-body">
                        <?php if(isset($msg)) echo "<div class='alert alert-success py-2'>$msg</div>"; ?>
                        <form method="POST">
                            <div class="mb-2"><label class="small fw-bold">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo $conf->nombre_negocio; ?>" required></div>
                            <div class="mb-2"><label class="small fw-bold">WhatsApp (Sin +)</label><input type="text" name="whatsapp" class="form-control" value="<?php echo $conf->telefono_whatsapp; ?>" required></div>
                            <div class="mb-2"><label class="small fw-bold">Email</label><input type="email" name="email" class="form-control" value="<?php echo $conf->email_notificaciones; ?>" required></div>
                            <div class="mb-3"><label class="small fw-bold">Dirección</label><input type="text" name="direccion" class="form-control" value="<?php echo $conf->direccion_local; ?>"></div>
                            <button type="submit" name="btn_config" class="btn btn-primary w-100 fw-bold">GUARDAR</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-warning text-dark fw-bold"><i class="bi bi-ticket-perforated"></i> Crear Cupón</div>
                    <div class="card-body">
                        <?php if(isset($error_cupon)) echo "<div class='alert alert-danger py-2'>$error_cupon</div>"; ?>
                        
                        <form method="POST" class="row g-2 align-items-end mb-4 border-bottom pb-3">
                            <div class="col-md-3">
                                <label class="small fw-bold">Código</label>
                                <input type="text" name="codigo" class="form-control text-uppercase" placeholder="Ej: VERANO" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">% Off</label>
                                <input type="number" name="descuento" class="form-control" placeholder="10" required>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Vence (Opc)</label>
                                <input type="date" name="fecha_limite" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Cliente (Opc)</label>
                                <select name="id_cliente" class="form-select">
                                    <option value="">Para Todos</option>
                                    <?php foreach($clientes as $cl): ?>
                                        <option value="<?php echo $cl->id; ?>"><?php echo $cl->nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" name="btn_cupon" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </form>

                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-striped table-sm align-middle small">
                                <thead><tr><th>Código</th><th>%</th><th>Vencimiento</th><th>Para</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach($cupones as $c): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo $c->codigo; ?></td>
                                        <td><?php echo $c->descuento_porcentaje; ?>%</td>
                                        <td>
                                            <?php 
                                                if($c->fecha_limite) {
                                                    echo date('d/m/Y', strtotime($c->fecha_limite));
                                                    if($c->fecha_limite < date('Y-m-d')) echo ' <span class="badge bg-danger">Vencido</span>';
                                                } else { echo '∞'; }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $c->nombre_cliente ? $c->nombre_cliente : '<span class="badge bg-success">Todos</span>'; ?>
                                        </td>
                                        <td><a href="configuracion.php?borrar_cupon=<?php echo $c->id; ?>" class="text-danger"><i class="bi bi-trash"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>