<?php
// bienes_uso.php - GESTIÓN DE ACTIVOS FIJOS (CORREGIDO)
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

// 2. SEGURIDAD
// CORRECCIÓN AQUÍ: Usamos 'rol' que es como lo guarda tu login, no 'id_rol'
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] > 2) {
    // Si no está la variable rol, recargamos del dashboard o salimos
    if(isset($_SESSION['usuario_id']) && !isset($_SESSION['rol'])) {
         header("Location: dashboard.php"); exit;
    }
    // Si tiene rol pero no es admin (mayor a 2)
    if(isset($_SESSION['rol']) && $_SESSION['rol'] > 2) {
         header("Location: dashboard.php"); exit;
    }
}

// 3. ELIMINAR ACTIVO
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

    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// 5. OBTENER DATOS
$activos = $conexion->query("SELECT * FROM bienes_uso ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Activos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Poppins', sans-serif; padding-bottom: 60px; }
        
        .page-header { background: white; padding: 20px; border-radius: 0 0 20px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 30px; }
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .search-box input { padding-left: 40px; border-radius: 50px; border: 1px solid #eee; background: #f8f9fa; }
        .filter-btn { border-radius: 50px; font-size: 0.9rem; padding: 6px 15px; border: 1px solid transparent; transition: all 0.2s; }
        .filter-btn.active { background-color: #0d6efd; color: white; }
        .filter-btn:hover:not(.active) { background-color: #e9ecef; }

        .card-activo { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s; height: 100%; overflow: hidden; }
        .card-activo:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        
        .img-zone { height: 160px; background-color: #e9ecef; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .img-zone img { width: 100%; height: 100%; object-fit: cover; }
        .img-placeholder { color: #adb5bd; font-size: 3rem; }
        
        .badge-estado { position: absolute; top: 10px; right: 10px; padding: 5px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        .card-body { padding: 15px; }
        .activo-title { font-weight: 600; font-size: 1.1rem; color: #333; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .activo-meta { font-size: 0.85rem; color: #777; margin-bottom: 10px; }
        
        .info-row { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #555; margin-bottom: 4px; }
        .info-row i { color: #0d6efd; width: 16px; text-align: center; }

        .card-footer-custom { padding: 12px 15px; background: #fff; border-top: 1px solid #f8f9fa; display: flex; justify-content: space-between; }
        
        .modal-content { border-radius: 15px; border: none; }
        .modal-header { border-bottom: 1px solid #f1f1f1; }
        
        .bg-nuevo { background-color: #d1e7dd; color: #0f5132; }
        .bg-bueno { background-color: #cfe2ff; color: #084298; }
        .bg-reparar { background-color: #fff3cd; color: #664d03; }
        .bg-malo { background-color: #f8d7da; color: #842029; }

        .btn-float { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; border-radius: 50%; font-size: 24px; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4); z-index: 1000; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="fw-bold mb-0"><i class="bi bi-box-seam text-primary"></i> Mis Activos</h2>
                    <p class="text-muted mb-0 small">Inventario de Mobiliario y Equipos</p>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                    <div class="search-box flex-grow-1">
                        <i class="bi bi-search"></i>
                        <input type="text" id="buscador" class="form-control" placeholder="Buscar activo...">
                    </div>
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="filter-btn active" onclick="filtrar('todos', this)">Todos</button>
                        <button class="filter-btn" onclick="filtrar('Nuevo', this)">Nuevos</button>
                        <button class="filter-btn" onclick="filtrar('Reparar', this)">Reparar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-3" id="gridActivos">
            <?php foreach ($activos as $a): 
                $estadoClass = 'bg-secondary text-white';
                if ($a['estado'] == 'Nuevo') $estadoClass = 'bg-nuevo';
                if ($a['estado'] == 'Bueno') $estadoClass = 'bg-bueno';
                if ($a['estado'] == 'Regular') $estadoClass = 'bg-warning text-dark';
                if ($a['estado'] == 'Reparar') $estadoClass = 'bg-reparar';
                if ($a['estado'] == 'Malo') $estadoClass = 'bg-malo';

                $img = !empty($a['foto']) && file_exists($a['foto']) ? $a['foto'] : null;
            ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 item-activo" 
                 data-nombre="<?php echo strtolower($a['nombre'] . ' ' . $a['marca']); ?>" 
                 data-estado="<?php echo $a['estado']; ?>">
                
                <div class="card card-activo">
                    <div class="img-zone">
                        <?php if($img): ?>
                            <img src="<?php echo $img; ?>" alt="Foto">
                        <?php else: ?>
                            <i class="bi bi-image img-placeholder"></i>
                        <?php endif; ?>
                        <span class="badge badge-estado <?php echo $estadoClass; ?>"><?php echo $a['estado']; ?></span>
                    </div>

                    <div class="card-body">
                        <h5 class="activo-title" title="<?php echo $a['nombre']; ?>"><?php echo $a['nombre']; ?></h5>
                        <div class="activo-meta text-truncate"><?php echo $a['marca']; ?> <?php echo $a['modelo']; ?></div>
                        
                        <div class="info-row"><i class="bi bi-geo-alt-fill"></i> <?php echo $a['ubicacion'] ?: 'Sin ubicación'; ?></div>
                        <div class="info-row"><i class="bi bi-upc-scan"></i> <?php echo $a['numero_serie'] ?: 'S/N'; ?></div>
                    </div>

                    <div class="card-footer-custom">
                        <button class="btn btn-sm btn-outline-primary border-0" onclick='verDetalle(<?php echo json_encode($a); ?>)' title="Ver Detalles">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light text-primary" onclick='editar(<?php echo json_encode($a); ?>)'>
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <a href="bienes_uso.php?borrar=<?php echo $a['id']; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('¿Eliminar este activo?')">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="noResults" class="text-center py-5 d-none">
            <i class="bi bi-search display-4 text-muted opacity-25"></i>
            <p class="text-muted mt-3">No se encontraron activos.</p>
        </div>
    </div>

    <button class="btn btn-primary btn-float" onclick="abrirModalCrear()">
        <i class="bi bi-plus-lg"></i>
    </button>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Activo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="bienes_uso.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id_edit" id="id_edit">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Nombre del Bien</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Ej: Heladera Exhibidora">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control" placeholder="Ej: Gafa">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="Nuevo">Nuevo</option>
                                    <option value="Bueno">Bueno</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Reparar">A Reparar</option>
                                    <option value="Malo">Malo / Desuso</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Ubicación Física</label>
                                <input type="text" name="ubicacion" id="ubicacion" class="form-control" placeholder="Ej: Depósito">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Nro Serie</label>
                                <input type="text" name="serie" id="serie" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted">Fecha Compra</label>
                                <input type="date" name="fecha" id="fecha" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Costo Compra ($)</label>
                                <input type="number" step="0.01" name="costo" id="costo" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small text-muted">Fotografía</label>
                                <input type="file" name="foto" class="form-control" accept="image/*">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label small text-muted">Notas / Observaciones</label>
                                <textarea name="notas" id="notas" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Guardar Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white" data-bs-dismiss="modal" style="z-index:10"></button>
                        <div class="img-zone" style="height: 250px;" id="view-img-container"></div>
                    </div>
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="fw-bold mb-0" id="view-nombre">Nombre</h4>
                                <span class="text-muted" id="view-marca">Marca Modelo</span>
                            </div>
                            <span class="badge bg-primary" id="view-estado">Estado</span>
                        </div>
                        <hr>
                        <div class="row g-3">
                            <div class="col-6"><small class="text-muted d-block">Ubicación</small><strong id="view-ubicacion">-</strong></div>
                            <div class="col-6"><small class="text-muted d-block">Nro Serie</small><strong id="view-serie">-</strong></div>
                            <div class="col-6"><small class="text-muted d-block">Fecha Compra</small><strong id="view-fecha">-</strong></div>
                            <div class="col-6"><small class="text-muted d-block">Valor</small><strong id="view-costo">-</strong></div>
                            <div class="col-12"><small class="text-muted d-block">Notas</small><p class="mb-0 bg-light p-2 rounded small" id="view-notas">-</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));
        const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));

        function abrirModalCrear() {
            document.querySelector('form').reset();
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
            document.getElementById('view-ubicacion').innerText = item.ubicacion || '-';
            document.getElementById('view-serie').innerText = item.numero_serie || '-';
            document.getElementById('view-fecha').innerText = item.fecha_compra || '-';
            document.getElementById('view-costo').innerText = item.costo_compra ? '$' + item.costo_compra : '-';
            document.getElementById('view-notas').innerText = item.notas || 'Sin notas.';

            const container = document.getElementById('view-img-container');
            if(item.foto) {
                container.innerHTML = `<img src="${item.foto}" style="width:100%; height:100%; object-fit:cover;">`;
            } else {
                container.innerHTML = `<i class="bi bi-image text-secondary" style="font-size:4rem;"></i>`;
            }
            modalDetalle.show();
        }

        const buscador = document.getElementById('buscador');
        let filtroEstado = 'todos';

        function filtrar(estado, btn) {
            filtroEstado = estado;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            if(btn) btn.classList.add('active');
            aplicarFiltros();
        }

        buscador.addEventListener('keyup', aplicarFiltros);

        function aplicarFiltros() {
            const txt = buscador.value.toLowerCase();
            const items = document.querySelectorAll('.item-activo');
            let visibles = 0;

            items.forEach(item => {
                const nombre = item.dataset.nombre;
                const estado = item.dataset.estado;
                let cumpleTxt = nombre.includes(txt);
                let cumpleEst = (filtroEstado === 'todos' || estado === filtroEstado);

                if(cumpleTxt && cumpleEst) {
                    item.classList.remove('d-none');
                    visibles++;
                } else {
                    item.classList.add('d-none');
                }
            });

            const noRes = document.getElementById('noResults');
            if(visibles === 0) noRes.classList.remove('d-none'); else noRes.classList.add('d-none');
        }
    </script>
</body>
</html>