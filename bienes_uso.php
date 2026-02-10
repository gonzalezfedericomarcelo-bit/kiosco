<?php
// bienes_uso.php - VERSIÓN ESTANDARIZADA (40px)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
if (file_exists('db.php')) { require_once 'db.php'; } 
elseif (file_exists('includes/db.php')) { require_once 'includes/db.php'; } 
else { die("Error crítico: No se encuentra db.php"); }

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 3. ELIMINAR ACTIVO (Lógica Backend)
if (isset($_GET['borrar'])) {
    try {
        $id = $_GET['borrar'];
        $stmtFoto = $conexion->prepare("SELECT foto FROM bienes_uso WHERE id = ?");
        $stmtFoto->execute([$id]);
        $foto = $stmtFoto->fetchColumn();
        if($foto && file_exists($foto)) { unlink($foto); }

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

        $ruta_foto = ''; 
        if (!empty($_FILES['foto']['name'])) {
            $dir = 'uploads/activos/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = uniqid('activo_') . '.' . $ext;
            $ruta_dest = $dir . $nombre_archivo;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_dest)) {
                $ruta_foto = $ruta_dest;
            }
        }

        if (!empty($id_edit)) {
            $sql = "UPDATE bienes_uso SET nombre=?, marca=?, modelo=?, numero_serie=?, estado=?, ubicacion=?, fecha_compra=?, costo_compra=?, notas=?";
            $params = [$nombre, $marca, $modelo, $serie, $estado, $ubicacion, $fecha, $costo, $notas];
            if ($ruta_foto != '') {
                $sql .= ", foto=?";
                $params[] = $ruta_foto;
            }
            $sql .= " WHERE id=?";
            $params[] = $id_edit;
            $stmt = $conexion->prepare($sql);
            $stmt->execute($params);
            $mensaje = 'actualizado';
        } else {
            $sql = "INSERT INTO bienes_uso (nombre, marca, modelo, numero_serie, estado, ubicacion, fecha_compra, costo_compra, notas, foto) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $marca, $modelo, $serie, $estado, $ubicacion, $fecha, $costo, $notas, $ruta_foto]);
            $mensaje = 'creado';
        }
        header("Location: bienes_uso.php?msg=" . $mensaje); exit;
    } catch (PDOException $e) { $mensaje = "Error: " . $e->getMessage(); }
}

// 5. OBTENER DATOS Y ESTADÍSTICAS
$activos = $conexion->query("SELECT * FROM bienes_uso ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Cálculos para Widgets
$total_activos = count($activos);
$valor_total = 0;
$reparar_cnt = 0;
foreach($activos as $a) {
    $valor_total += $a['costo_compra'];
    if($a['estado'] == 'Reparar') $reparar_cnt++;
}
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    /* ESTILOS ESTANDARIZADOS (40px) */
    .header-blue {
        background-color: #102A57; /* Azul Institucional */
        color: white;
        padding: 40px 0; /* 40px PARA IGUALAR A PROVEEDORES */
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

    /* Widgets / Stat Cards */
    .stat-card {
        border: none; border-radius: 15px; padding: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
        background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
        position: relative; z-index: 1;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    /* Buscador */
    .search-box { position: relative; }
    .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666; }
    .search-box input { padding-left: 40px; border-radius: 50px; border: none; height: 45px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

    /* Cards de Activos */
    .card-activo { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.2s; overflow: hidden; background: white; }
    .card-activo:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
    .img-zone { height: 180px; background-color: #e9ecef; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .img-zone img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Estados */
    .bg-nuevo { background-color: #d1e7dd; color: #0f5132; }
    .bg-bueno { background-color: #cfe2ff; color: #084298; }
    .bg-reparar { background-color: #fff3cd; color: #664d03; }
    .bg-malo { background-color: #f8d7da; color: #842029; }
</style>

<div class="header-blue">
    <i class="bi bi-box-seam bg-icon-large"></i>

    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Mis Activos</h2>
                <p class="opacity-75 mb-0">Control de inventario y equipos</p>
            </div>
            <div>
                <button class="btn btn-light text-dark fw-bold rounded-pill px-4 shadow-sm" onclick="abrirModalCrear()">
                    <i class="bi bi-plus-lg me-2"></i> Nuevo Activo
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card" onclick="filtrar('todos')" style="cursor: pointer;">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Equipos</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $total_activos; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-pc-display"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Valor Inventario</h6>
                        <h2 class="mb-0 fw-bold text-dark">$<?php echo number_format($valor_total, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" onclick="filtrar('Reparar')" style="cursor: pointer;">
    <div>
        <h6 class="text-muted text-uppercase small fw-bold mb-1">A Reparar</h6>
                        <h2 class="mb-0 fw-bold <?php echo ($reparar_cnt > 0) ? 'text-danger' : 'text-dark'; ?>"><?php echo $reparar_cnt; ?></h2>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="buscador" class="form-control" placeholder="Buscar por nombre, marca, serie...">
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <div class="btn-group shadow-sm">
                <button class="btn btn-white border active" onclick="filtrar('todos')">Todos</button>
                <button class="btn btn-white border" onclick="filtrar('Nuevo')">Nuevos</button>
                <button class="btn btn-white border" onclick="filtrar('Reparar')">Reparar</button>
            </div>
        </div>
    </div>

    <div class="row g-4" id="gridActivos">
        <?php foreach ($activos as $a): 
            $estadoClass = 'bg-secondary text-white';
            if ($a['estado'] == 'Nuevo') $estadoClass = 'bg-nuevo';
            if ($a['estado'] == 'Bueno') $estadoClass = 'bg-bueno';
            if ($a['estado'] == 'Regular') $estadoClass = 'bg-warning text-dark';
            if ($a['estado'] == 'Reparar') $estadoClass = 'bg-reparar';
            if ($a['estado'] == 'Malo') $estadoClass = 'bg-malo';
            
            $img = !empty($a['foto']) && file_exists($a['foto']) ? $a['foto'] : null;
            $jsonItem = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 item-activo" 
             data-nombre="<?php echo strtolower($a['nombre'] . ' ' . $a['marca'] . ' ' . $a['numero_serie']); ?>" 
             data-estado="<?php echo $a['estado']; ?>">
            
            <div class="card card-activo h-100 d-flex flex-column">
                <div class="img-zone">
                    <?php if($img): ?>
                        <img src="<?php echo $img; ?>" alt="Foto">
                    <?php else: ?>
                        <i class="bi bi-image text-muted fs-1"></i>
                    <?php endif; ?>
                    <span class="badge position-absolute top-0 end-0 m-2 <?php echo $estadoClass; ?> shadow-sm"><?php echo $a['estado']; ?></span>
                </div>

                <div class="card-body flex-grow-1">
                    <h6 class="fw-bold mb-1 text-truncate" title="<?php echo $a['nombre']; ?>"><?php echo $a['nombre']; ?></h6>
                    <small class="text-muted d-block mb-2"><?php echo $a['marca']; ?> <?php echo $a['modelo']; ?></small>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                         <div class="small text-muted"><i class="bi bi-geo-alt-fill text-primary"></i> <?php echo substr($a['ubicacion'], 0, 15) ?: '-'; ?></div>
                         <?php if($a['costo_compra'] > 0): ?>
                            <div class="fw-bold text-success small">$<?php echo number_format($a['costo_compra'], 0, ',', '.'); ?></div>
                         <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3 pt-0">
                    <button class="btn btn-sm btn-outline-primary border-0 rounded-pill px-3" onclick='verDetalle(<?php echo $jsonItem; ?>)' title="Ver Detalle">
                        <i class="bi bi-eye-fill me-1"></i> Ver
                    </button>
                    <div>
                        <button class="btn btn-sm btn-light text-primary rounded-circle" onclick='editar(<?php echo $jsonItem; ?>)'>
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger rounded-circle" onclick="confirmarBorrar(<?php echo $a['id']; ?>, '<?php echo $a['nombre']; ?>')">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(count($activos) == 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-box-seam display-1 opacity-25"></i>
            <p class="mt-3">No tienes activos registrados aún.</p>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Activo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="bienes_uso.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_edit" id="id_edit">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nombre del Bien</label>
                            <input type="text" name="nombre" id="nombre" class="form-control fw-bold" required placeholder="Ej: Notebook Dell">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Marca</label>
                            <input type="text" name="marca" id="marca" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Modelo</label>
                            <input type="text" name="modelo" id="modelo" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="Nuevo">Nuevo</option>
                                <option value="Bueno">Bueno</option>
                                <option value="Regular">Regular</option>
                                <option value="Reparar">A Reparar</option>
                                <option value="Malo">Malo / Desuso</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Ubicación</label>
                            <input type="text" name="ubicacion" id="ubicacion" class="form-control" placeholder="Ej: Oficina 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Nro Serie</label>
                            <input type="text" name="serie" id="serie" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Fecha Compra</label>
                            <input type="date" name="fecha" id="fecha" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Costo ($)</label>
                            <input type="number" step="0.01" name="costo" id="costo" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Foto del Activo</label>
                            <input type="file" name="foto" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Notas Adicionales</label>
                            <textarea name="notas" id="notas" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 rounded-pill">Guardar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-body p-0">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white p-2 shadow-sm rounded-circle" data-bs-dismiss="modal" style="z-index:10; opacity:1;"></button>
                <div class="img-zone" style="height: 250px;" id="view-img-container"></div>
                <div class="p-4">
                    <h4 class="fw-bold mb-0 text-primary" id="view-nombre">Nombre</h4>
                    <span class="text-muted small text-uppercase fw-bold" id="view-marca">Marca Modelo</span>
                    <hr class="my-3">
                    <div class="row g-3 small">
                        <div class="col-6"><span class="text-muted d-block">Estado</span> <strong id="view-estado" class="fs-6">-</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Ubicación</span> <strong id="view-ubicacion" class="fs-6">-</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Nro. Serie</span> <strong id="view-serie" class="font-monospace">-</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Valor Compra</span> <strong id="view-costo" class="text-success fs-6">-</strong></div>
                        <div class="col-12 p-3 bg-light rounded mt-2">
                            <span class="text-muted fw-bold d-block mb-1">Notas:</span> 
                            <span id="view-notas" class="fst-italic">Sin notas.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include 'includes/layout_footer.php'; ?>

<script>
    // Inicialización de Modales
    let modalForm, modalDetalle;
    document.addEventListener('DOMContentLoaded', function() {
        modalForm = new bootstrap.Modal(document.getElementById('modalForm'));
        modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
    });

    function abrirModalCrear() {
        document.querySelector('#modalForm form').reset();
        document.getElementById('id_edit').value = '';
        document.getElementById('modalTitle').innerText = 'Nuevo Activo';
        modalForm.show();
    }

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
        document.getElementById('modalTitle').innerText = 'Editar Activo';
        modalForm.show();
    }

    function verDetalle(item) {
        document.getElementById('view-nombre').innerText = item.nombre;
        document.getElementById('view-marca').innerText = (item.marca || '') + ' ' + (item.modelo || '');
        document.getElementById('view-estado').innerText = item.estado;
        document.getElementById('view-ubicacion').innerText = item.ubicacion || 'Sin ubicación';
        document.getElementById('view-serie').innerText = item.numero_serie || 'S/N';
        document.getElementById('view-costo').innerText = item.costo_compra ? '$' + new Intl.NumberFormat('es-AR').format(item.costo_compra) : '$0';
        document.getElementById('view-notas').innerText = item.notas || 'No hay notas registradas.';

        const container = document.getElementById('view-img-container');
        if(item.foto) {
            container.innerHTML = `<img src="${item.foto}" style="width:100%; height:100%; object-fit:cover;">`;
        } else {
            container.innerHTML = `<i class="bi bi-box-seam text-secondary" style="font-size:5rem; opacity:0.3;"></i>`;
        }
        modalDetalle.show();
    }

    function confirmarBorrar(id, nombre) {
        Swal.fire({
            title: '¿Eliminar ' + nombre + '?',
            text: "Se borrará permanentemente del inventario.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'bienes_uso.php?borrar=' + id;
            }
        })
    }

    // Buscador
    document.getElementById('buscador').addEventListener('keyup', function() {
        let texto = this.value.toLowerCase();
        document.querySelectorAll('.item-activo').forEach(item => {
            let nombre = item.dataset.nombre;
            if(nombre.includes(texto)) item.classList.remove('d-none');
            else item.classList.add('d-none');
        });
    });

    // Filtros Rápidos
    function filtrar(estado) {
        document.querySelectorAll('.item-activo').forEach(item => {
            if(estado === 'todos' || item.dataset.estado === estado) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
        
        // Actualizar botón activo
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
            if(btn.innerText.includes(estado === 'todos' ? 'Todos' : estado)) {
                btn.classList.add('active');
            }
        });
    }
</script>