<?php
// proveedores.php - CORREGIDO PARA USAR OBJETOS (->) SEGÚN TU DB.PHP
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN ROBUSTA (Busca en raíz o en includes)
if (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("<h1>ERROR CRÍTICO: No se encuentra db.php ni en la raíz ni en includes.</h1>");
}

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); exit;
}

// 3. BORRAR PROVEEDOR
if (isset($_GET['borrar'])) {
    try {
        $id = $_GET['borrar'];
        // Verificamos si tiene productos (Query compatible)
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE id_proveedor = ? AND activo = 1");
        $stmt->execute([$id]);
        
        // fetchColumn funciona igual
        if ($stmt->fetchColumn() > 0) {
            $error = "⚠️ No se puede borrar: Tiene productos asociados.";
        } else {
            $conexion->prepare("DELETE FROM proveedores WHERE id = ?")->execute([$id]);
            header("Location: proveedores.php?msg=ok"); exit;
        }
    } catch (Exception $e) {
        $error = "Error DB: " . $e->getMessage();
    }
}

// 4. GUARDAR (NUEVO O EDITAR)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $empresa = trim($_POST['empresa']);
    $contacto = trim($_POST['contacto']);
    $telefono = trim($_POST['telefono']);
    $id_edit = $_POST['id_edit'] ?? '';

    if (!empty($empresa)) {
        try {
            if ($id_edit) {
                $sql = "UPDATE proveedores SET empresa=?, contacto=?, telefono=? WHERE id=?";
                $conexion->prepare($sql)->execute([$empresa, $contacto, $telefono, $id_edit]);
            } else {
                $sql = "INSERT INTO proveedores (empresa, contacto, telefono) VALUES (?, ?, ?)";
                $conexion->prepare($sql)->execute([$empresa, $contacto, $telefono]);
            }
            header("Location: proveedores.php"); exit;
        } catch (Exception $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// 5. LISTAR (Aquí estaba el error 500: Ahora usamos la conexión tal cual es)
try {
    $stmt = $conexion->query("SELECT * FROM proveedores ORDER BY empresa ASC");
    $proveedores = $stmt->fetchAll(); // Devuelve OBJETOS por defecto
} catch (Exception $e) {
    die("Error de consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="container pb-5 pt-4">
        <div class="row">
            
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-plus-circle"></i> Nuevo Proveedor</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="id_edit" id="id_edit">
                            <div class="mb-2">
                                <label class="fw-bold small">Empresa</label>
                                <input type="text" name="empresa" id="campo_empresa" class="form-control" required placeholder="Ej: Coca-Cola">
                            </div>
                            <div class="mb-2">
                                <label class="small">Contacto</label>
                                <input type="text" name="contacto" id="campo_contacto" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="small">Teléfono</label>
                                <input type="text" name="telefono" id="campo_telefono" class="form-control">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success fw-bold">GUARDAR</button>
                                <button type="button" onclick="limpiar()" class="btn btn-outline-secondary btn-sm">Limpiar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger mb-3"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">Listado de Proveedores</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Contacto</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($proveedores) > 0): ?>
                                        <?php foreach($proveedores as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo $p->empresa; ?></div>
                                                <small class="text-muted">ID: <?php echo $p->id; ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo $p->contacto; ?></div>
                                                <small><?php echo $p->telefono; ?></small>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary border-0" 
                                                    onclick="editar('<?php echo $p->id; ?>','<?php echo $p->empresa; ?>','<?php echo $p->contacto ?? ''; ?>','<?php echo $p->telefono ?? ''; ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="proveedores.php?borrar=<?php echo $p->id; ?>" 
                                                   class="btn btn-sm btn-outline-danger border-0" 
                                                   onclick="return confirm('¿Borrar?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center p-4">No hay proveedores cargados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function editar(id,e,c,t){
        document.getElementById('id_edit').value=id;
        document.getElementById('campo_empresa').value=e;
        document.getElementById('campo_contacto').value=c;
        document.getElementById('campo_telefono').value=t;
    }
    function limpiar(){
        document.getElementById('id_edit').value='';
        document.getElementById('campo_empresa').value='';
        document.getElementById('campo_contacto').value='';
        document.getElementById('campo_telefono').value='';
    }
    </script>
</body>
</html>