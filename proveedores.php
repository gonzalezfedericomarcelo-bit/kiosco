<?php
// proveedores.php - VERSIÓN DASHBOARD MODERNO
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN Y SEGURIDAD
if (file_exists('db.php')) { require_once 'db.php'; } 
elseif (file_exists('includes/db.php')) { require_once 'includes/db.php'; } 
else { die("Error: No se encuentra db.php"); }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 2. LÓGICA: GUARDAR
$mensaje_sistema = '';
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
                $mensaje_sistema = 'actualizado';
            } else {
                $sql = "INSERT INTO proveedores (empresa, contacto, telefono) VALUES (?, ?, ?)";
                $conexion->prepare($sql)->execute([$empresa, $contacto, $telefono]);
                $mensaje_sistema = 'creado';
            }
            // Redirección limpia para evitar reenvío de formulario
            header("Location: proveedores.php?msg=" . $mensaje_sistema); exit;
        } catch (Exception $e) { 
            $error = "Error: " . $e->getMessage(); 
        }
    }
}

// 3. LÓGICA: BORRAR
if (isset($_GET['borrar'])) {
    try {
        $id = $_GET['borrar'];
        // Verificamos si tiene productos activos
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM productos WHERE id_proveedor = ? AND activo = 1");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            header("Location: proveedores.php?error=tiene_productos"); exit;
        } else {
            $conexion->prepare("DELETE FROM proveedores WHERE id = ?")->execute([$id]);
            header("Location: proveedores.php?msg=eliminado"); exit;
        }
    } catch (Exception $e) { header("Location: proveedores.php?error=db"); exit; }
}

// 4. CONSULTA INTELIGENTE (Trae cantidad de productos)
try {
    // Subquery para contar productos asociados
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM productos WHERE id_proveedor = p.id AND activo=1) as cant_productos
            FROM proveedores p 
            ORDER BY p.empresa ASC";
    $proveedores = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Cálculos para Widgets
    $total_prov = count($proveedores);
    $total_prod_vinc = 0;
    foreach($proveedores as $p) { $total_prod_vinc += $p['cant_productos']; }

} catch (Exception $e) { die("Error DB: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Proveedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        .header-gradient {
            background: linear-gradient(135deg, #212529 0%, #343a40 100%);
            color: white; padding: 30px 0; margin-bottom: 30px;
            border-radius: 0 0 30px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-card {
            border: none; border-radius: 15px; padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
            background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        .table-card { border: none; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .avatar-initial {
            width: 40px; height: 40px; background-color: #e9ecef;
            color: #495057; font-weight: bold; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; margin-right: 15px;
        }
        
        .btn-fab {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; border-radius: 50%;
            font-size: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
    </style>
</head>
<body>

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="header-gradient">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Mis Proveedores</h2>
                    <p class="opacity-75 mb-0">Gestión de compras y contactos</p>
                </div>
                <div class="d-none d-md-block">
                    <button class="btn btn-light text-dark fw-bold rounded-pill px-4 shadow-sm" onclick="abrirModal()">
                        <i class="bi bi-plus-lg me-2"></i> Nuevo Proveedor
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Empresas</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $total_prov; ?></h2>
                        </div>
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Productos Vinculados</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $total_prod_vinc; ?></h2>
                        </div>
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='gastos.php'">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Gastos del Mes</h6>
                            <h6 class="mb-0 fw-bold text-secondary">Ver Reporte <i class="bi bi-arrow-right"></i></h6>
                        </div>
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        
        <?php if(isset($_GET['msg']) && $_GET['msg']=='creado'): ?>
            <div class="alert alert-success shadow-sm rounded-pill text-center fw-bold">¡Proveedor registrado con éxito!</div>
        <?php endif; ?>
        <?php if(isset($_GET['error']) && $_GET['error']=='tiene_productos'): ?>
            <div class="alert alert-warning shadow-sm rounded-3 fw-bold text-center">
                <i class="bi bi-exclamation-triangle-fill"></i> No podés borrar este proveedor: Tiene productos en stock.
            </div>
        <?php endif; ?>

        <div class="card table-card bg-white">
            <div class="card-header bg-white py-3 border-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar por nombre, teléfono o contacto..." onkeyup="filtrarTabla()">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                    <thead class="bg-light small text-uppercase text-muted">
                        <tr>
                            <th class="ps-4">Empresa</th>
                            <th>Contacto / Teléfono</th>
                            <th>Catálogo</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($proveedores) > 0): ?>
                            <?php foreach($proveedores as $p): 
                                $letra = strtoupper(substr($p['empresa'], 0, 1));
                            ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initial"><?php echo $letra; ?></div>
                                        <div>
                                            <div class="fw-bold text-dark empresa-nombre"><?php echo $p['empresa']; ?></div>
                                            <small class="text-muted">ID: #<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($p['contacto']): ?>
                                        <div class="small fw-bold text-secondary"><i class="bi bi-person"></i> <?php echo $p['contacto']; ?></div>
                                    <?php endif; ?>
                                    <?php if($p['telefono']): ?>
                                        <div class="small text-muted"><i class="bi bi-telephone"></i> <?php echo $p['telefono']; ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted fw-normal">Sin teléfono</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($p['cant_productos'] > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                            <?php echo $p['cant_productos']; ?> Productos
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3">Sin productos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="cuenta_proveedor.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Ver Cuenta Corriente">
                                            <i class="bi bi-journal-text"></i> Cuenta
                                        </a>
                                        
                                        <button class="btn btn-sm btn-outline-primary" 
                                            onclick='editar(<?php echo json_encode($p); ?>)' title="Editar Datos">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        
                                        <a href="proveedores.php?borrar=<?php echo $p['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Estás seguro de eliminar a <?php echo $p['empresa']; ?>?');" title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Todavía no cargaste proveedores.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button class="btn btn-primary btn-fab d-md-none rounded-circle" onclick="abrirModal()">
        <i class="bi bi-plus-lg"></i>
    </button>

    <div class="modal fade" id="modalProveedor" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" id="formProveedor">
                        <input type="hidden" name="id_edit" id="id_edit">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nombre de la Empresa</label>
                            <input type="text" name="empresa" id="empresa" class="form-control form-control-lg fw-bold" required placeholder="Ej: Distribuidora Norte">
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Nombre Contacto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" name="contacto" id="contacto" class="form-control" placeholder="Ej: Juan">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Teléfono / WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" name="telefono" id="telefono" class="form-control" placeholder="Sin 0 ni 15">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill">GUARDAR DATOS</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEl = document.getElementById('modalProveedor');
        const modal = new bootstrap.Modal(modalEl);

        function abrirModal() {
            document.getElementById('formProveedor').reset();
            document.getElementById('id_edit').value = '';
            document.getElementById('modalTitulo').innerText = 'Nuevo Proveedor';
            modal.show();
        }

        function editar(p) {
            document.getElementById('id_edit').value = p.id;
            document.getElementById('empresa').value = p.empresa;
            document.getElementById('contacto').value = p.contacto || '';
            document.getElementById('telefono').value = p.telefono || '';
            document.getElementById('modalTitulo').innerText = 'Editar Proveedor';
            modal.show();
        }

        function filtrarTabla() {
            const filtro = document.getElementById('buscador').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaProveedores tbody tr');

            filas.forEach(fila => {
                const texto = fila.innerText.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        }
    </script>
</body>
</html>