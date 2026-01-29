<?php
// activos.php - GESTIÓN DE BIENES DE USO CON FOTO
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

// 1. GUARDAR (NUEVO O EDITAR)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $nombre = $_POST['nombre'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $serie = $_POST['numero_serie'];
    $ubicacion = $_POST['ubicacion'];
    $estado = $_POST['estado'];
    $fecha = !empty($_POST['fecha_compra']) ? $_POST['fecha_compra'] : null;
    $costo = !empty($_POST['costo_compra']) ? $_POST['costo_compra'] : 0;
    $notas = $_POST['notas'];

    // PROCESAR FOTO
    $ruta_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = 'activo_' . time() . '_' . rand(100, 999) . '.' . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $destino = 'uploads/' . $nombre_archivo;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $ruta_foto = $destino;
        }
    }

    try {
        if ($_POST['accion'] == 'crear') {
            $sql = "INSERT INTO bienes_uso (nombre, marca, modelo, numero_serie, ubicacion, estado, fecha_compra, costo_compra, notas, foto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $marca, $modelo, $serie, $ubicacion, $estado, $fecha, $costo, $notas, $ruta_foto]);
        } elseif ($_POST['accion'] == 'editar') {
            $id = $_POST['id_bien'];
            
            // Si subió nueva foto, actualizamos. Si no, mantenemos la vieja (si no borra explícitamente)
            if ($ruta_foto) {
                $sql = "UPDATE bienes_uso SET nombre=?, marca=?, modelo=?, numero_serie=?, ubicacion=?, estado=?, fecha_compra=?, costo_compra=?, notas=?, foto=? WHERE id=?";
                $params = [$nombre, $marca, $modelo, $serie, $ubicacion, $estado, $fecha, $costo, $notas, $ruta_foto, $id];
            } else {
                $sql = "UPDATE bienes_uso SET nombre=?, marca=?, modelo=?, numero_serie=?, ubicacion=?, estado=?, fecha_compra=?, costo_compra=?, notas=? WHERE id=?";
                $params = [$nombre, $marca, $modelo, $serie, $ubicacion, $estado, $fecha, $costo, $notas, $id];
            }
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute($params);
        }
        header("Location: activos.php?msg=ok"); exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// 2. ELIMINAR
if (isset($_GET['borrar'])) {
    // Borrar archivo físico si existe
    $stmt = $conexion->prepare("SELECT foto FROM bienes_uso WHERE id = ?");
    $stmt->execute([$_GET['borrar']]);
    $foto = $stmt->fetchColumn();
    if($foto && file_exists($foto)) { unlink($foto); }

    $conexion->prepare("DELETE FROM bienes_uso WHERE id = ?")->execute([$_GET['borrar']]);
    header("Location: activos.php?msg=borrado"); exit;
}

// 3. LISTAR
$bienes = $conexion->query("SELECT * FROM bienes_uso ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activos Fijos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .badge-estado-nuevo { background-color: #198754; }
        .badge-estado-bueno { background-color: #0d6efd; }
        .badge-estado-mantenimiento { background-color: #ffc107; color: #000; }
        .badge-estado-roto { background-color: #dc3545; }
        .badge-estado-baja { background-color: #6c757d; }
        .thumb-activo { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #ddd; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-secondary"><i class="bi bi-pc-display-horizontal"></i> Bienes de Uso (Activos)</h4>
            <button class="btn btn-dark fw-bold shadow-sm" onclick="abrirModal('crear')">
                <i class="bi bi-camera-fill"></i> AGREGAR BIEN
            </button>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Foto</th>
                                <th>Equipo / Bien</th>
                                <th>Marca/Modelo</th>
                                <th>Serie</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($bienes)): ?>
                                <tr><td colspan="7" class="text-center p-4 text-muted">No hay activos registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach($bienes as $b): 
                                    $clase = 'badge-estado-' . $b['estado'];
                                    $img = $b['foto'] && file_exists($b['foto']) ? $b['foto'] : 'https://via.placeholder.com/50?text=S/F';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if($b['foto']): ?>
                                            <img src="<?php echo $b['foto']; ?>" class="thumb-activo" onclick="verFoto('<?php echo $b['foto']; ?>', '<?php echo $b['nombre']; ?>')">
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-primary"><?php echo $b['nombre']; ?></td>
                                    <td>
                                        <div><?php echo $b['marca']; ?></div>
                                        <small class="text-muted"><?php echo $b['modelo']; ?></small>
                                    </td>
                                    <td class="small font-monospace"><?php echo $b['numero_serie'] ?: '--'; ?></td>
                                    <td><i class="bi bi-geo-alt"></i> <?php echo $b['ubicacion']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $clase; ?> text-uppercase"><?php echo $b['estado']; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary border-0" onclick='editar(<?php echo json_encode($b); ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="activos.php?borrar=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('¿Eliminar este activo?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBien" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalTitulo">Nuevo Activo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="formBien" enctype="multipart/form-data">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_bien" id="id_bien">

                        <div class="row g-3 mb-3">
                            <div class="col-12 text-center mb-2">
                                <label class="btn btn-outline-primary w-100 py-3 border-2 border-dashed">
                                    <i class="bi bi-camera h3 d-block"></i>
                                    <span class="fw-bold">Tomar Foto / Subir Imagen</span>
                                    <input type="file" name="foto" id="foto" class="d-none" accept="image/*" capture="environment" onchange="previewImage(this)">
                                </label>
                                <div id="preview-box" class="mt-2 d-none">
                                    <img id="preview-img" src="" style="max-height: 150px; border-radius: 8px;">
                                    <div class="small text-success fw-bold mt-1"><i class="bi bi-check-circle"></i> Imagen seleccionada</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre del Bien *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Heladera Exhibidora" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control" placeholder="Ej: Gafa">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">N° Serie (Seguro)</label>
                                <input type="text" name="numero_serie" id="numero_serie" class="form-control" placeholder="S/N...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Ubicación</label>
                                <input type="text" name="ubicacion" id="ubicacion" class="form-control" placeholder="Ej: Depósito">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="nuevo">Nuevo</option>
                                    <option value="bueno">Bueno / Usado</option>
                                    <option value="mantenimiento">En Reparación</option>
                                    <option value="roto">Roto</option>
                                    <option value="baja">Dado de Baja</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha Compra</label>
                                <input type="date" name="fecha_compra" id="fecha_compra" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Costo de Compra ($)</label>
                                <input type="number" step="0.01" name="costo_compra" id="costo_compra" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Notas Adicionales</label>
                            <textarea name="notas" id="notas" class="form-control" rows="2" placeholder="Detalles, garantía, service..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold">GUARDAR</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('modalBien'));
        
        function abrirModal(modo) {
            document.getElementById('formBien').reset();
            document.getElementById('accion').value = 'crear';
            document.getElementById('modalTitulo').innerText = 'Nuevo Activo';
            document.getElementById('preview-box').classList.add('d-none');
            modal.show();
        }

        function editar(b) {
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_bien').value = b.id;
            document.getElementById('nombre').value = b.nombre;
            document.getElementById('marca').value = b.marca;
            document.getElementById('modelo').value = b.modelo;
            document.getElementById('numero_serie').value = b.numero_serie;
            document.getElementById('ubicacion').value = b.ubicacion;
            document.getElementById('estado').value = b.estado;
            document.getElementById('fecha_compra').value = b.fecha_compra;
            document.getElementById('costo_compra').value = b.costo_compra;
            document.getElementById('notas').value = b.notas;
            
            // Preview de foto existente
            if(b.foto) {
                document.getElementById('preview-img').src = b.foto;
                document.getElementById('preview-box').classList.remove('d-none');
            } else {
                document.getElementById('preview-box').classList.add('d-none');
            }

            document.getElementById('modalTitulo').innerText = 'Editar Activo';
            modal.show();
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview-box').classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function verFoto(url, titulo) {
            Swal.fire({
                title: titulo,
                imageUrl: url,
                imageAlt: titulo,
                showCloseButton: true,
                showConfirmButton: false,
                width: '600px'
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'ok') Swal.fire('Éxito', 'Guardado correctamente', 'success');
        if(urlParams.get('msg') === 'borrado') Swal.fire('Listo', 'Activo eliminado', 'info');
    </script>
</body>
</html>