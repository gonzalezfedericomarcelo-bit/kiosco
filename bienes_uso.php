<?php
// bienes_uso.php - GESTIÓN DE ACTIVOS FIJOS (MAQUINARIA, MUEBLES, EQUIPOS)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN ROBUSTA
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("<h1>ERROR CRÍTICO: No se encuentra db.php.</h1>");
}

// 2. SEGURIDAD (Solo Dueño y Admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

// 3. ELIMINAR ACTIVO
if (isset($_GET['borrar'])) {
    try {
        $id = $_GET['borrar'];
        $conexion->prepare("DELETE FROM bienes_uso WHERE id = ?")->execute([$id]);
        header("Location: bienes_uso.php?msg=eliminado"); exit;
    } catch (Exception $e) { /* Silencio */ }
}

// 4. GUARDAR (NUEVO O EDITAR)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $serie = trim($_POST['serie']);
        $estado = $_POST['estado'];
        $ubicacion = trim($_POST['ubicacion']);
        $fecha = !empty($_POST['fecha']) ? $_POST['fecha'] : NULL;
        $costo = !empty($_POST['costo']) ? $_POST['costo'] : 0;
        $notas = trim($_POST['notas']);
        $id_edit = $_POST['id_edit'];

        if (!empty($id_edit)) {
            // EDITAR
            $sql = "UPDATE bienes_uso SET nombre=?, marca=?, modelo=?, numero_serie=?, estado=?, ubicacion=?, fecha_compra=?, costo_compra=?, notas=? WHERE id=?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $marca, $modelo, $serie, $estado, $ubicacion, $fecha, $costo, $notas, $id_edit]);
            $mensaje = '<div class="alert alert-primary shadow-sm">✅ Activo actualizado correctamente.</div>';
        } else {
            // NUEVO
            $sql = "INSERT INTO bienes_uso (nombre, marca, modelo, numero_serie, estado, ubicacion, fecha_compra, costo_compra, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $marca, $modelo, $serie, $estado, $ubicacion, $fecha, $costo, $notas]);
            $mensaje = '<div class="alert alert-success shadow-sm">✅ Nuevo activo registrado.</div>';
        }
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

// 5. LISTAR ACTIVOS
try {
    $activos = $conexion->query("SELECT * FROM bienes_uso ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario de Activos Fijos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container py-4">
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card shadow border-0 sticky-top" style="top: 90px; z-index: 100;">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-hdd-network me-2"></i>Gestión de Activos
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="id_edit" id="id_edit">
                            
                            <div class="mb-2">
                                <label class="fw-bold small">Nombre del Bien</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Ej: Heladera Mostrador">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted">Marca</label>
                                    <input type="text" name="marca" id="marca" class="form-control form-control-sm">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Modelo</label>
                                    <input type="text" name="modelo" id="modelo" class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="small text-muted">N° de Serie</label>
                                <input type="text" name="serie" id="serie" class="form-control form-control-sm" placeholder="S/N...">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="fw-bold small">Estado</label>
                                    <select name="estado" id="estado" class="form-select form-select-sm">
                                        <option value="nuevo">Nuevo</option>
                                        <option value="bueno" selected>Bueno</option>
                                        <option value="mantenimiento">En Reparación</option>
                                        <option value="roto">Roto / Dañado</option>
                                        <option value="baja">Dado de Baja</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Ubicación</label>
                                    <input type="text" name="ubicacion" id="ubicacion" class="form-control form-control-sm" placeholder="Ej: Depósito">
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted">Compra</label>
                                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Costo $</label>
                                    <input type="number" name="costo" id="costo" step="0.01" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted">Notas / Observaciones</label>
                                <textarea name="notas" id="notas" class="form-control form-control-sm" rows="2"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary fw-bold" id="btn-guardar">GUARDAR ACTIVO</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarForm()">Cancelar / Limpiar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-ul me-2"></i>Inventario de Bienes de Uso</span>
                        <span class="badge bg-secondary"><?php echo count($activos); ?> Items</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Equipo / Bien</th>
                                        <th>Estado</th>
                                        <th>Ubicación</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($activos) > 0): ?>
                                        <?php foreach($activos as $a): 
                                            // Colores según estado
                                            $badgeColor = 'bg-secondary';
                                            if($a['estado'] == 'nuevo') $badgeColor = 'bg-success';
                                            if($a['estado'] == 'bueno') $badgeColor = 'bg-primary';
                                            if($a['estado'] == 'mantenimiento') $badgeColor = 'bg-warning text-dark';
                                            if($a['estado'] == 'roto') $badgeColor = 'bg-danger';
                                            if($a['estado'] == 'baja') $badgeColor = 'bg-dark';
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-dark"><?php echo $a['nombre']; ?></div>
                                                <div class="small text-muted">
                                                    <?php echo $a['marca']; ?> <?php echo $a['modelo']; ?>
                                                    <?php if($a['numero_serie']): ?>
                                                        <br><i class="bi bi-barcode me-1"></i>S/N: <?php echo $a['numero_serie']; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeColor; ?> text-uppercase"><?php echo $a['estado']; ?></span>
                                                <?php if($a['fecha_compra']): ?>
                                                    <div style="font-size: 0.75rem;" class="text-muted mt-1">
                                                        Comp: <?php echo date('d/m/y', strtotime($a['fecha_compra'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <i class="bi bi-geo-alt me-1"></i><?php echo $a['ubicacion'] ?: 'Local'; ?>
                                                <?php if($a['costo_compra'] > 0): ?>
                                                    <div class="fw-bold text-success mt-1">$<?php echo number_format($a['costo_compra'],0,',','.'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <button class="btn btn-sm btn-outline-primary border-0 me-1" 
                                                    onclick='editar(<?php echo json_encode($a); ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="bienes_uso.php?borrar=<?php echo $a['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger border-0"
                                                   onclick="return confirm('¿Seguro que deseas eliminar este activo?');">
                                                    <i class="bi bi-trash3"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">No hay activos registrados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editar(item) {
            document.getElementById('id_edit').value = item.id;
            document.getElementById('nombre').value = item.nombre;
            document.getElementById('marca').value = item.marca || '';
            document.getElementById('modelo').value = item.modelo || '';
            document.getElementById('serie').value = item.numero_serie || '';
            document.getElementById('estado').value = item.estado;
            document.getElementById('ubicacion').value = item.ubicacion || '';
            document.getElementById('fecha').value = item.fecha_compra || '';
            document.getElementById('costo').value = item.costo_compra || '';
            document.getElementById('notas').value = item.notas || '';
            
            document.getElementById('btn-guardar').innerHTML = "ACTUALIZAR DATOS";
            document.getElementById('btn-guardar').classList.replace('btn-primary', 'btn-warning');
            
            // Scroll suave hacia arriba en móviles
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function limpiarForm() {
            document.querySelector('form').reset();
            document.getElementById('id_edit').value = '';
            document.getElementById('btn-guardar').innerHTML = "GUARDAR ACTIVO";
            document.getElementById('btn-guardar').classList.replace('btn-warning', 'btn-primary');
        }
    </script>
</body>
</html>