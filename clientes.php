<?php
// clientes.php - VERSIÓN BLINDADA PARA MÓVIL
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error: db.php no encontrado");

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); exit;
}

// 3. LÓGICA: GUARDAR / EDITAR / BORRAR
if (isset($_GET['borrar'])) {
    try {
        $check = $conexion->prepare("SELECT COUNT(*) FROM movimientos_cc WHERE id_cliente = ?");
        $check->execute([$_GET['borrar']]);
        if($check->fetchColumn() > 0){
             header("Location: clientes.php?error=tiene_movimientos"); exit;
        }
        $conexion->prepare("DELETE FROM clientes WHERE id = ?")->execute([$_GET['borrar']]);
        header("Location: clientes.php?msg=eliminado"); exit;
    } catch (Exception $e) { 
        header("Location: clientes.php?error=db"); exit; 
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $telefono = trim($_POST['telefono']);
    $limite = $_POST['limite'];
    $id_edit = $_POST['id_edit'] ?? '';

    if (!empty($nombre)) {
        if ($id_edit) {
            $sql = "UPDATE clientes SET nombre=?, dni=?, telefono=?, limite_credito=? WHERE id=?";
            $conexion->prepare($sql)->execute([$nombre, $dni, $telefono, $limite, $id_edit]);
        } else {
            $sql = "INSERT INTO clientes (nombre, dni, telefono, limite_credito, fecha_registro) VALUES (?, ?, ?, ?, NOW())";
            $conexion->prepare($sql)->execute([$nombre, $dni, $telefono, $limite]);
        }
        header("Location: clientes.php"); exit;
    }
}

// 4. CONSULTA INTELIGENTE
$sql = "SELECT c.*, 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = c.id AND tipo = 'debe') - 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = c.id AND tipo = 'haber') as saldo_calculado,
        (SELECT MAX(fecha) FROM ventas WHERE id_cliente = c.id) as ultima_venta_fecha,
        (SELECT COUNT(*) FROM ventas WHERE id_cliente = c.id) as total_compras_cant
        FROM clientes c 
        ORDER BY c.nombre ASC";

$clientes = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para JavaScript de forma segura (Arregla el bug del clic en móvil)
$clientes_json = [];
$totalDeudaCalle = 0;
foreach($clientes as $c) {
    if($c['saldo_calculado'] > 0) $totalDeudaCalle += $c['saldo_calculado'];
    
    // Guardamos solo los datos necesarios en un array indexado por ID
    $clientes_json[$c['id']] = [
        'id' => $c['id'],
        'nombre' => $c['nombre'],
        'dni' => $c['dni'] ?? $c['dni_cuit'] ?? 'No registrado',
        'telefono' => $c['telefono'] ?? 'No registrado',
        'direccion' => $c['direccion'] ?? '',
        'limite' => $c['limite_credito'],
        'deuda' => $c['saldo_calculado'],
        'puntos' => $c['puntos_acumulados'],
        'ultima_venta' => $c['ultima_venta_fecha'] ? date('d/m/Y', strtotime($c['ultima_venta_fecha'])) : 'Nunca',
        'total_compras' => $c['total_compras_cant']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary-color: #4361ee; --secondary-color: #3f37c9; --bg-color: #f8f9fa; }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: #333; }
        
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; }
        
        .avatar-circle {
            width: 45px; height: 45px; background-color: #e9ecef; color: #495057;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; text-transform: uppercase; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .badge-deuda { background-color: #ffe5e5; color: #d63384; font-weight: 600; padding: 5px 10px; border-radius: 20px; }
        .badge-aldia { background-color: #e6fcf5; color: #20c997; font-weight: 600; padding: 5px 10px; border-radius: 20px; }
        
        /* Botones más grandes para dedo en móvil */
        .btn-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; margin-left: 3px; }
        
        @media (max-width: 768px) {
            .col-formulario { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 1050; transform: translateY(110%); transition: transform 0.3s; padding: 0; }
            .col-formulario.active { transform: translateY(0); }
            .form-card { border-radius: 20px 20px 0 0 !important; box-shadow: 0 -5px 20px rgba(0,0,0,0.2); }
            .fab-add { position: fixed; bottom: 20px; right: 20px; z-index: 1040; width: 60px; height: 60px; border-radius: 30px; font-size: 24px; box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4); }
            .overlay-mobile { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1045; display: none; }
            /* Espacio extra abajo para que no se tape el último cliente */
            body { padding-bottom: 80px; }
        }
        
        .modal-header-custom { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; }
        .stat-box { background: #f8f9fa; border-radius: 10px; padding: 15px; text-align: center; height: 100%; }
        .stat-value { font-size: 1.5rem; font-weight: 700; margin-bottom: 0; }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; color: #6c757d; }
    </style>
</head>
<body>

    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="overlay-mobile" onclick="toggleFormulario()"></div>

    <div class="container-fluid py-4 px-md-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold mb-0">Cartera de Clientes</h2>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="card bg-white border-0 shadow-sm px-3 py-2 d-flex flex-row align-items-center gap-3">
                            <div class="rounded-circle bg-light p-2 text-danger"><i class="bi bi-graph-down-arrow"></i></div>
                            <div>
                                <div class="small text-muted fw-bold">DEUDA CALLE</div>
                                <div class="fw-bold fs-5 text-danger">$<?php echo number_format($totalDeudaCalle, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8 col-lg-9 order-2 order-md-1">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscador" class="form-control bg-light border-start-0" placeholder="Buscar cliente..." onkeyup="filtrarClientes()">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaClientes">
                                <thead class="bg-light text-secondary small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Cliente</th>
                                        <th class="d-none d-md-table-cell">Contacto</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($clientes) > 0): ?>
                                        <?php foreach($clientes as $c): 
                                            $iniciales = strtoupper(substr($c['nombre'], 0, 2));
                                            $deuda = $c['saldo_calculado'];
                                            $limite = $c['limite_credito'];
                                            $porcentajeDeuda = ($limite > 0) ? ($deuda / $limite) * 100 : 0;
                                            $colorDeuda = ($deuda > 0) ? 'badge-deuda' : 'badge-aldia';
                                            $textoDeuda = ($deuda > 0) ? 'Debe $'.number_format($deuda,0,',','.') : 'Al día';
                                        ?>
                                        <tr class="cliente-row">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-3"><?php echo $iniciales; ?></div>
                                                    <div>
                                                        <div class="fw-bold text-dark nombre-cliente"><?php echo $c['nombre']; ?></div>
                                                        <small class="text-muted d-block d-md-none dni-cliente"><?php echo $c['dni'] ?: 'S/DNI'; ?></small>
                                                        <small class="text-muted d-none d-md-block dni-cliente">DNI: <?php echo $c['dni'] ?: '--'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="small"><i class="bi bi-whatsapp text-success"></i> <?php echo $c['telefono'] ?: '--'; ?></div>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?php echo $colorDeuda; ?> small mb-1 d-inline-block"><?php echo $textoDeuda; ?></span>
                                                <?php if($limite > 0): ?>
                                                    <div class="progress mt-1" style="height: 4px; width: 80px; margin: 0 auto;">
                                                        <div class="progress-bar <?php echo ($porcentajeDeuda>80)?'bg-danger':'bg-primary'; ?>" role="progressbar" style="width: <?php echo min($porcentajeDeuda, 100); ?>%"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end">
                                                    <button class="btn-icon bg-info text-white shadow-sm" onclick="verResumen(<?php echo $c['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <a href="cuenta_cliente.php?id=<?php echo $c['id']; ?>" class="btn-icon bg-success text-white shadow-sm">
                                                        <i class="bi bi-wallet2"></i>
                                                    </a>

                                                    <button class="btn-icon bg-warning text-dark shadow-sm" 
                                                        onclick="editar(<?php echo $c['id']; ?>)">
                                                        <i class="bi bi-pencil-fill" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    
                                                    <a href="clientes.php?borrar=<?php echo $c['id']; ?>" class="btn-icon bg-white text-danger border shadow-sm" onclick="return confirm('¿Borrar?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">Sin clientes.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-3 col-formulario order-1 order-md-2" id="colFormulario">
                <div class="card form-card border-0 h-100 bg-white">
                    <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-plus-fill"></i> <span id="titulo-form">Nuevo Cliente</span></span>
                        <button class="btn btn-sm text-white d-md-none" onclick="toggleFormulario()"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="id_edit" id="id_edit">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre" id="nombre" class="form-control form-control-lg fw-bold" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">DNI</label>
                                <input type="text" name="dni" id="dni" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" class="form-control">
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-danger">Límite Fiado ($)</label>
                                <input type="number" name="limite" id="limite" class="form-control fw-bold text-danger" value="0">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold" id="btn-guardar">GUARDAR</button>
                                <button type="button" onclick="limpiar()" class="btn btn-light text-muted">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary fab-add d-md-none" onclick="toggleFormulario()">
        <i class="bi bi-plus-lg"></i>
    </button>

    <div class="modal fade" id="modalResumen" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-custom border-0 pb-4">
                    <h5 class="modal-title fw-bold">Resumen de Cuenta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body position-relative pt-0">
                    <div class="text-center" style="margin-top: -30px;">
                        <div class="bg-white rounded-circle shadow d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; font-size: 2rem; font-weight: bold; color: var(--primary-color);" id="modal-avatar">
                            NN
                        </div>
                        <h4 class="mt-2 fw-bold" id="modal-nombre">--</h4>
                        <span class="badge bg-light text-dark border" id="modal-dni">--</span>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <div class="stat-box border-danger border-start border-4 shadow-sm">
                                <div class="stat-value text-danger" id="modal-deuda">$0</div>
                                <div class="stat-label">Deuda</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box border-success border-start border-4 shadow-sm">
                                <div class="stat-value text-success" id="modal-disponible">$0</div>
                                <div class="stat-label">Disponible</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 px-2">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Última Compra:</span>
                            <span class="fw-bold" id="modal-ultima">--</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Puntos:</span>
                            <span class="fw-bold" id="modal-puntos">0</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Teléfono:</span>
                            <span class="fw-bold" id="modal-telefono">--</span>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <a href="#" id="btn-ir-cuenta" class="btn btn-primary btn-lg fw-bold shadow">VER DETALLE COMPLETO</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // BASE DE DATOS JS (Generada por PHP)
        const clientesDB = <?php echo json_encode($clientes_json); ?>;
        const modalResumen = new bootstrap.Modal(document.getElementById('modalResumen'));

        // BUSCADOR
        function filtrarClientes() {
            let filter = document.getElementById("buscador").value.toUpperCase();
            let tr = document.getElementById("tablaClientes").getElementsByTagName("tr");
            for (let i = 0; i < tr.length; i++) {
                let td = tr[i].querySelector(".nombre-cliente");
                if (td) {
                    let txt = td.textContent || td.innerText;
                    tr[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        // FUNCIONES ACCIÓN
        function verResumen(id) {
            const data = clientesDB[id]; // Buscamos en la lista segura
            if(!data) return alert("Error al cargar datos");

            document.getElementById('modal-nombre').innerText = data.nombre;
            document.getElementById('modal-dni').innerText = 'DNI: ' + data.dni;
            document.getElementById('modal-avatar').innerText = data.nombre.substring(0,2).toUpperCase();
            
            const format = (n) => '$ ' + new Intl.NumberFormat('es-AR').format(n);
            let deuda = parseFloat(data.deuda);
            let limite = parseFloat(data.limite);
            
            document.getElementById('modal-deuda').innerText = format(deuda);
            document.getElementById('modal-deuda').className = (deuda > 0) ? 'stat-value text-danger' : 'stat-value text-success';
            document.getElementById('modal-disponible').innerText = (limite > 0) ? format(limite - deuda) : 'Ilimitado';
            
            document.getElementById('modal-ultima').innerText = data.ultima_venta;
            document.getElementById('modal-puntos').innerText = data.puntos;
            document.getElementById('modal-telefono').innerText = data.telefono;
            document.getElementById('btn-ir-cuenta').href = 'cuenta_cliente.php?id=' + data.id;

            modalResumen.show();
        }

        function editar(id) {
            const data = clientesDB[id];
            document.getElementById('id_edit').value = data.id;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('dni').value = (data.dni == 'No registrado') ? '' : data.dni;
            document.getElementById('telefono').value = (data.telefono == 'No registrado') ? '' : data.telefono;
            document.getElementById('limite').value = data.limite;
            
            document.getElementById('titulo-form').innerText = "Editar Cliente";
            document.getElementById('btn-guardar').innerText = "ACTUALIZAR";
            document.getElementById('btn-guardar').classList.replace('btn-primary', 'btn-warning');
            
            if(window.innerWidth < 768) toggleFormulario();
        }

        function limpiar() {
            document.querySelector('form').reset();
            document.getElementById('id_edit').value = '';
            document.getElementById('titulo-form').innerText = "Nuevo Cliente";
            document.getElementById('btn-guardar').innerText = "GUARDAR";
            document.getElementById('btn-guardar').classList.replace('btn-warning', 'btn-primary');
            if(window.innerWidth < 768) toggleFormulario();
        }

        function toggleFormulario() {
            document.getElementById('colFormulario').classList.toggle('active');
            let overlay = document.querySelector('.overlay-mobile');
            overlay.style.display = (overlay.style.display === 'block') ? 'none' : 'block';
        }
    </script>
</body>
</html>
