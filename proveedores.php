<?php
// proveedores.php - VERSIÓN FINAL ESTANDARIZADA (40px)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
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

// 4. CONSULTA
try {
    $sql = "SELECT p.*, (SELECT COUNT(*) FROM productos WHERE id_proveedor = p.id AND activo=1) as cant_productos FROM proveedores p ORDER BY p.empresa ASC";
    $proveedores = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $total_prov = count($proveedores);
    $total_prod_vinc = 0;
    foreach($proveedores as $p) { $total_prod_vinc += $p['cant_productos']; }
} catch (Exception $e) { die("Error DB: " . $e->getMessage()); }
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    /* BANNER ESTANDARIZADO (40px igual que Bienes de Uso) */
    .header-blue {
        background-color: #102A57; /* Azul Institucional */
        color: white; 
        padding: 40px 0;  /* 40px PARA IGUALAR A BIENES DE USO */
        margin-bottom: 30px;
        border-radius: 0 0 30px 30px; 
        box-shadow: 0 4px 15px rgba(16, 42, 87, 0.25);
        position: relative; 
        overflow: hidden;
    }
    .bg-icon-large {
        position: absolute; top: 50%; right: 20px;
        transform: translateY(-50%) rotate(-10deg);
        font-size: 10rem; opacity: 0.1; color: white; pointer-events: none;
    }
    
    /* Stats Cards */
    .stat-card {
        border: none; border-radius: 15px; padding: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
        background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        position: relative; z-index: 1;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    /* Tabla */
    .table-card { border: none; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); overflow: hidden; }
    .avatar-initial { width: 40px; height: 40px; background-color: #e9ecef; color: #495057; font-weight: bold; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
    
    .btn-fab {
        position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;
        border-radius: 50%; font-size: 24px; box-shadow: 0 4px 15px rgba(16, 42, 87, 0.4);
        z-index: 1000; background-color: #102A57; border: none; color: white;
    }
</style>

<div class="header-blue">
    <i class="bi bi-building bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Mis Proveedores</h2>
                <p class="opacity-75 mb-0 small">Gestión de compras</p>
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
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Empresas</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $total_prov; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-building"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Productos</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $total_prod_vinc; ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='gastos.php'">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Gastos Mes</h6>
                        <h6 class="mb-0 fw-bold text-secondary">Ver Reporte <i class="bi bi-arrow-right"></i></h6>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if(isset($_GET['msg']) && $_GET['msg']=='creado'): ?>
        <div class="alert alert-success shadow-sm rounded-pill text-center fw-bold">¡Proveedor registrado!</div>
    <?php endif; ?>
    <?php if(isset($_GET['error']) && $_GET['error']=='tiene_productos'): ?>
        <div class="alert alert-warning shadow-sm rounded-3 fw-bold text-center">
            <i class="bi bi-exclamation-triangle-fill"></i> No podés borrar este proveedor: Tiene productos.
        </div>
    <?php endif; ?>

    <div class="card table-card bg-white">
        <div class="card-header bg-white py-3 border-0">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar..." onkeyup="filtrarTabla()">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                <thead class="bg-light small text-uppercase text-muted">
                    <tr>
                        <th class="ps-4">Empresa</th>
                        <th>Contacto</th>
                        <th>Catálogo</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($proveedores) > 0): ?>
                        <?php foreach($proveedores as $p): 
                            $letra = strtoupper(substr($p['empresa'], 0, 1));
                            $jsonP = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initial"><?php echo $letra; ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $p['empresa']; ?></div>
                                        <small class="text-muted">ID: #<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold text-secondary"><i class="bi bi-person"></i> <?php echo $p['contacto'] ?: '-'; ?></div>
                                <div class="small text-muted"><i class="bi bi-telephone"></i> <?php echo $p['telefono'] ?: '-'; ?></div>
                            </td>
                            <td>
                                <?php if($p['cant_productos'] > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3"><?php echo $p['cant_productos']; ?> Prod.</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-3">Vacio</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="cuenta_proveedor.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Cuenta"><i class="bi bi-journal-text"></i></a>
                                    <button class="btn btn-sm btn-outline-primary" onclick='editar(<?php echo $jsonP; ?>)' title="Editar"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarBorrar(<?php echo $p['id']; ?>, '<?php echo $p['empresa']; ?>')" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Sin proveedores.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<button class="btn-fab d-md-none" onclick="abrirModal()">
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
                        <label class="form-label small fw-bold text-muted">Empresa</label>
                        <input type="text" name="empresa" id="empresa" class="form-control form-control-lg fw-bold" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Contacto</label>
                            <input type="text" name="contacto" id="contacto" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mt-4 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill">GUARDAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include 'includes/layout_footer.php'; ?>

<script>
    // Inicialización segura del Modal
    let modalProveedor;
    document.addEventListener('DOMContentLoaded', function() {
        modalProveedor = new bootstrap.Modal(document.getElementById('modalProveedor'));
    });

    function abrirModal() {
        document.getElementById('formProveedor').reset();
        document.getElementById('id_edit').value = '';
        document.getElementById('modalTitulo').innerText = 'Nuevo Proveedor';
        modalProveedor.show();
    }

    function editar(p) {
        document.getElementById('id_edit').value = p.id;
        document.getElementById('empresa').value = p.empresa;
        document.getElementById('contacto').value = p.contacto || '';
        document.getElementById('telefono').value = p.telefono || '';
        document.getElementById('modalTitulo').innerText = 'Editar Proveedor';
        modalProveedor.show();
    }

    function confirmarBorrar(id, nombre) {
        Swal.fire({
            title: '¿Eliminar a ' + nombre + '?',
            text: "No podrás deshacer esto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'proveedores.php?borrar=' + id;
            }
        })
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