<?php
// clientes.php - TU LÓGICA ORIGINAL + MENÚ GLOBAL
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 1. PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $nombre = $_POST['nombre'];
    $dni = $_POST['dni'];
    $whatsapp = $_POST['whatsapp'];
    $direccion = $_POST['direccion'];
    $email = $_POST['email'];
    
    try {
        if ($_POST['accion'] == 'crear') {
            // Validar que no exista DNI duplicado (si tiene DNI)
            if (!empty($dni)) {
                $check = $conexion->prepare("SELECT id FROM clientes WHERE dni = ?");
                $check->execute([$dni]);
                if ($check->rowCount() > 0) {
                    throw new Exception("Ya existe un cliente con ese DNI.");
                }
            }

            $sql = "INSERT INTO clientes (nombre, dni, whatsapp, direccion, email, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $dni, $whatsapp, $direccion, $email]);
            $msg = 'creado';
        } elseif ($_POST['accion'] == 'editar') {
            $id = $_POST['id_cliente'];
            $sql = "UPDATE clientes SET nombre=?, dni=?, whatsapp=?, direccion=?, email=? WHERE id=?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $dni, $whatsapp, $direccion, $email, $id]);
            $msg = 'editado';
        }
        header("Location: clientes.php?status=" . $msg);
        exit;
    } catch (Exception $e) {
        // Enviar el error específico por URL para verlo
        $error_msg = urlencode($e->getMessage());
        header("Location: clientes.php?status=error&msg=" . $error_msg);
        exit;
    }
}

// 2. ELIMINAR
if (isset($_GET['borrar'])) {
    $id = $_GET['borrar'];
    if($id == 1) { header("Location: clientes.php?status=error&msg=No se puede borrar al Consumidor Final"); exit; }
    
    try {
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: clientes.php?status=borrado");
    } catch (Exception $e) {
        header("Location: clientes.php?status=error&msg=El cliente tiene ventas asociadas, no se puede borrar.");
    }
    exit;
}

$clientes = $conexion->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <title>Clientes - Kiosco Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .avatar-initial {
            width: 40px; height: 40px; background-color: #0d6efd; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem;
        }
        /* Estilos para la lista de autocompletado de dirección */
        #lista-direcciones {
            position: absolute; z-index: 1060; width: 100%;
            background: white; border: 1px solid #ccc;
            max-height: 200px; overflow-y: auto; display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .item-direccion { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .item-direccion:hover { background-color: #f0f2f5; color: #0d6efd; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-secondary fw-bold mb-0">Cartera de Clientes</h4>
            <button class="btn btn-success fw-bold shadow-sm" onclick="abrirModal('crear')">
                <i class="bi bi-person-plus-fill"></i> NUEVO CLIENTE
            </button>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 700px;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nombre</th>
                                <th>DNI</th>
                                <th>WhatsApp</th>
                                <th>Dirección</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initial me-3 bg-gradient">
                                            <?php echo strtoupper(substr($c->nombre, 0, 1)); ?>
                                        </div>
                                        <div class="fw-bold"><?php echo $c->nombre; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $c->dni ?: '--'; ?></td>
                                <td>Puntos: <?php echo $c->puntos_acumulados; ?></td>
                                <td>
                                    <?php if($c->whatsapp): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $c->whatsapp); ?>" target="_blank" class="text-success text-decoration-none fw-bold">
                                            <i class="bi bi-whatsapp"></i> Chat
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 200px;">
                                    <?php echo $c->direccion ?: 'Sin dirección'; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($c->id != 1): ?>
                                    <button class="btn btn-sm btn-outline-primary border-0" onclick='editar(<?php echo json_encode($c); ?>)'>
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger border-0" onclick="confirmarBorrado(<?php echo $c->id; ?>)">
                                        <i class="bi bi-trash-fill fs-5"></i>
                                    </button>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sistema</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <a href="cartel_qr.php" target="_blank" class="btn btn-dark">
                    <i class="bi bi-qr-code"></i> Imprimir QR Mostrador
                </a>
                <div class="modal-body">
                    <form method="POST" id="formCliente">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_cliente" id="id_cliente">

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nombre Completo *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control form-control-lg" required placeholder="Ej: Juan Perez">
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">DNI / CUIT</label>
                                <input type="text" name="dni" id="dni" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small">WhatsApp</label>
                                <input type="text" name="whatsapp" id="whatsapp" class="form-control" placeholder="54911...">
                            </div>
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold small">Dirección (Autocompletar)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Escribe calle y altura..." autocomplete="off">
                            </div>
                            <div id="lista-direcciones"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="cliente@email.com">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2">GUARDAR DATOS</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // ALERTAS
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if(status === 'creado') Swal.fire('Éxito', 'Cliente guardado correctamente', 'success');
        if(status === 'editado') Swal.fire('Éxito', 'Datos actualizados', 'success');
        if(status === 'borrado') Swal.fire('Eliminado', 'Cliente eliminado', 'success');
        if(status === 'error') Swal.fire('Error', msg || 'Ocurrió un error inesperado', 'error');

        // MODAL
        const modalEl = document.getElementById('modalCliente');
        const modal = new bootstrap.Modal(modalEl);

        function abrirModal(modo) {
            document.getElementById('formCliente').reset();
            document.getElementById('accion').value = 'crear';
            document.getElementById('modalTitulo').innerText = 'Nuevo Cliente';
            document.getElementById('lista-direcciones').style.display = 'none';
            modal.show();
        }

        function editar(c) {
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_cliente').value = c.id;
            document.getElementById('nombre').value = c.nombre;
            document.getElementById('dni').value = c.dni;
            document.getElementById('whatsapp').value = c.whatsapp;
            document.getElementById('direccion').value = c.direccion;
            document.getElementById('email').value = c.email;
            document.getElementById('modalTitulo').innerText = 'Editar Cliente';
            modal.show();
        }

        function confirmarBorrado(id) {
            Swal.fire({
                title: '¿Eliminar Cliente?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((r) => {
                if(r.isConfirmed) window.location.href = 'clientes.php?borrar=' + id;
            });
        }

        // --- LÓGICA DE AUTOCOMPLETADO DE DIRECCIONES (OpenStreetMap) ---
        let timeout = null;
        
        $('#direccion').on('keyup', function() {
            let query = $(this).val();
            if(query.length < 4) {
                $('#lista-direcciones').hide();
                return;
            }

            // Usamos timeout para no saturar la API mientras escribes
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                // Buscamos solo en Argentina (countrycodes=ar)
                let url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ar&limit=5`;
                
                $.getJSON(url, function(data) {
                    let html = '';
                    if(data.length > 0) {
                        data.forEach(item => {
                            html += `<div class="item-direccion" onclick="seleccionarDireccion('${item.display_name}')">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> ${item.display_name}
                                     </div>`;
                        });
                        $('#lista-direcciones').html(html).show();
                    } else {
                        $('#lista-direcciones').hide();
                    }
                });
            }, 500); // Espera 500ms después de dejar de escribir
        });

        window.seleccionarDireccion = function(direccion) {
            $('#direccion').val(direccion);
            $('#lista-direcciones').hide();
        };

        // Ocultar lista si hago click afuera
        $(document).click(function(e) {
            if (!$(e.target).closest('#direccion, #lista-direcciones').length) {
                $('#lista-direcciones').hide();
            }
        });
    </script>
</body>
</html>