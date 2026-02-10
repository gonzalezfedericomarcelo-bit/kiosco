<?php
// clientes.php - VERSIÓN FINAL CORREGIDA (Sin conflicto de menú)
session_start();
error_reporting(0); // Ocultamos errores en pantalla para no romper JSON

// 1. CONEXIÓN
if (file_exists('db.php')) require_once 'db.php';
elseif (file_exists('includes/db.php')) require_once 'includes/db.php';
else die("Error: db.php no encontrado");

// 2. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); exit;
}

// 3. LÓGICA PHP
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    try {
        $checkCC = $conexion->prepare("SELECT COUNT(*) FROM movimientos_cc WHERE id_cliente = ?");
        $checkCC->execute([$id_borrar]);
        
        $checkVentas = $conexion->prepare("SELECT COUNT(*) FROM ventas WHERE id_cliente = ?");
        $checkVentas->execute([$id_borrar]);

        if ($checkCC->fetchColumn() > 0 || $checkVentas->fetchColumn() > 0) {
             header("Location: clientes.php?error=tiene_datos"); exit;
        }

        $conexion->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id_borrar]);
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

// 4. CONSULTA
$sql = "SELECT c.*, 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = c.id AND tipo = 'debe') - 
        (SELECT COALESCE(SUM(monto),0) FROM movimientos_cc WHERE id_cliente = c.id AND tipo = 'haber') as saldo_calculado,
        (SELECT MAX(fecha) FROM ventas WHERE id_cliente = c.id) as ultima_venta_fecha
        FROM clientes c 
        ORDER BY c.nombre ASC";

$clientes = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// JSON para JS
$clientes_json = [];
$totalDeudaCalle = 0;
$cntDeudores = 0;
$cntAlDia = 0;
$totalClientes = count($clientes);

foreach($clientes as $c) {
    if($c['saldo_calculado'] > 0.1) {
        $totalDeudaCalle += $c['saldo_calculado'];
        $cntDeudores++;
    } else {
        $cntAlDia++;
    }
    
    $clientes_json[$c['id']] = [
        'id' => $c['id'],
        'nombre' => htmlspecialchars($c['nombre']),
        'dni' => $c['dni'] ?? $c['dni_cuit'] ?? 'No registrado',
        'telefono' => $c['telefono'] ?? 'No registrado',
        'limite' => $c['limite_credito'],
        'deuda' => $c['saldo_calculado'],
        'puntos' => $c['puntos_acumulados'],
        'ultima_venta' => $c['ultima_venta_fecha'] ? date('d/m/Y', strtotime($c['ultima_venta_fecha'])) : 'Nunca'
    ];
}
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    /* BANNER AZUL */
    .header-blue {
        background-color: #102A57;
        color: white;
        padding: 40px 0;
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
    .stat-card {
        border: none; border-radius: 15px; padding: 15px 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s;
        background: white; height: 100%; display: flex; align-items: center; justify-content: space-between;
    }
    .stat-card:hover { transform: translateY(-3px); cursor: pointer; }
    .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

    /* ESTILOS TABLA */
    .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; }
    .avatar-circle { width: 45px; height: 45px; background-color: #e9ecef; color: #495057; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .badge-deuda { background-color: #ffe5e5; color: #d63384; font-weight: 600; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
    .badge-aldia { background-color: #e6fcf5; color: #20c997; font-weight: 600; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
    
    .btn-action { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: none; margin-left: 5px; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-decoration: none; }
    .btn-action:hover { transform: scale(1.1); filter: brightness(0.95); }
    .btn-eye { background: #e0f2fe; color: #0284c7; }
    .btn-wallet { background: #dcfce7; color: #16a34a; }
    .btn-edit { background: #ffedd5; color: #ea580c; }
    .btn-del { background: #fee2e2; color: #dc2626; }

    .modal-header-custom { background: #102A57; color: white; }
    .stat-box { background: #f8f9fa; border-radius: 10px; padding: 15px; text-align: center; height: 100%; border: 1px solid #eee; }
    .stat-value { font-size: 1.5rem; font-weight: 700; margin-bottom: 0; }
    .stat-label { font-size: 0.8rem; text-transform: uppercase; color: #6c757d; }
</style>

<div class="header-blue">
    <i class="bi bi-people-fill bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Cartera de Clientes</h2>
                <p class="opacity-75 mb-0">Administración de cuentas corrientes</p>
            </div>
            <div>
                <button type="button" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm" onclick="abrirModalCrear()">
                    <i class="bi bi-person-plus-fill me-2"></i> Nuevo Cliente
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarEstado('todos')">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Clientes</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $totalClientes; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarEstado('deuda')">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Deuda en Calle</h6>
                        <h2 class="mb-0 fw-bold text-danger">$<?php echo number_format($totalDeudaCalle, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-graph-down-arrow"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" onclick="filtrarEstado('aldia')">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Clientes al Día</h6>
                        <h2 class="mb-0 fw-bold text-success"><?php echo $cntAlDia; ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="card card-custom">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="buscador" class="form-control bg-light border-start-0" placeholder="Buscar por nombre o DNI..." onkeyup="filtrarClientes()">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaClientes">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Cliente</th>
                            <th class="d-none d-md-table-cell">Datos Contacto</th>
                            <th class="text-center">Estado Deuda</th>
                            <th class="text-end pe-4" style="min-width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($clientes) > 0): ?>
                            <?php foreach($clientes as $c): 
                                $iniciales = strtoupper(substr($c['nombre'], 0, 2));
                                $deuda = floatval($c['saldo_calculado']);
                                $limite = floatval($c['limite_credito']);
                                $porcentajeDeuda = ($limite > 0) ? ($deuda / $limite) * 100 : 0;
                                $colorDeuda = ($deuda > 0.1) ? 'badge-deuda' : 'badge-aldia';
                                $textoDeuda = ($deuda > 0.1) ? 'Debe $'.number_format($deuda,0,',','.') : 'Al día';
                                $esDeudor = ($deuda > 0.1) ? '1' : '0';
                            ?>
                            <tr class="cliente-row" data-es-deudor="<?php echo $esDeudor; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3"><?php echo $iniciales; ?></div>
                                        <div>
                                            <div class="fw-bold text-dark nombre-cliente"><?php echo $c['nombre']; ?></div>
                                            <small class="text-muted dni-cliente">DNI: <?php echo $c['dni'] ?: '--'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php if($c['telefono']): ?>
                                        <div class="small"><i class="bi bi-whatsapp text-success me-1"></i> <?php echo $c['telefono']; ?></div>
                                    <?php else: ?>
                                        <div class="small text-muted">Sin teléfono</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="<?php echo $colorDeuda; ?> mb-1 d-inline-block"><?php echo $textoDeuda; ?></span>
                                    <?php if($limite > 0 && $deuda > 0): ?>
                                        <div class="progress mt-1" style="height: 4px; width: 80px; margin: 0 auto;">
                                            <div class="progress-bar <?php echo ($porcentajeDeuda>80)?'bg-danger':'bg-primary'; ?>" role="progressbar" style="width: <?php echo min($porcentajeDeuda, 100); ?>%"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn-action btn-eye" onclick="verResumen(<?php echo $c['id']; ?>)" title="Ver Detalle">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    
                                    <a href="cuenta_cliente.php?id=<?php echo $c['id']; ?>" class="btn-action btn-wallet" title="Cuenta Corriente">
                                        <i class="bi bi-wallet2"></i>
                                    </a>

                                    <button type="button" class="btn-action btn-edit" onclick="editar(<?php echo $c['id']; ?>)" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    
                                    <button type="button" class="btn-action btn-del" onclick="borrarCliente(<?php echo $c['id']; ?>)" title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No hay clientes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGestionCliente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="titulo-modal">Nuevo Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="formCliente">
                    <input type="hidden" name="id_edit" id="id_edit">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control form-control-lg fw-bold" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">DNI</label>
                            <input type="text" name="dni" id="dni" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-4 bg-light p-3 rounded border">
                        <label class="form-label small fw-bold text-danger">Límite de Fiado ($)</label>
                        <input type="number" name="limite" id="limite" class="form-control fw-bold text-danger" value="0">
                        <small class="text-muted d-block mt-1">Poner 0 para ilimitado.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" id="btn-guardar">GUARDAR CLIENTE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResumen" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-custom border-0 pb-4">
                <h5 class="modal-title fw-bold">Resumen de Cuenta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body position-relative pt-0">
                <div class="text-center" style="margin-top: -30px;">
                    <div class="bg-white rounded-circle shadow d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; font-size: 2rem; font-weight: bold; color: #102A57;" id="modal-avatar">NN</div>
                    <h4 class="mt-2 fw-bold" id="modal-nombre">--</h4>
                    <span class="badge bg-light text-dark border" id="modal-dni">--</span>
                </div>
                <div class="row g-2 mt-3">
                    <div class="col-6">
                        <div class="stat-box border-danger border-start border-4 shadow-sm">
                            <div class="stat-value text-danger" id="modal-deuda">$0</div>
                            <div class="stat-label">Deuda Actual</div>
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
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Última Compra:</span><span class="fw-bold" id="modal-ultima">--</span></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Puntos:</span><span class="fw-bold text-warning" id="modal-puntos">0</span></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Contacto:</span><span class="fw-bold" id="modal-telefono">--</span></div>
                </div>
                <div class="d-grid mt-4">
                    <a href="#" id="btn-ir-cuenta" class="btn btn-primary btn-lg fw-bold shadow">VER HISTORIAL COMPLETO</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // BASE DE DATOS JS
    const clientesDB = <?php echo json_encode($clientes_json); ?>;
    
    // --- MODALES CON JQUERY (Infalible) ---
    function abrirModalCrear() {
        document.getElementById('formCliente').reset();
        document.getElementById('id_edit').value = '';
        document.getElementById('titulo-modal').innerText = "Nuevo Cliente";
        let btn = document.getElementById('btn-guardar');
        btn.innerText = "GUARDAR CLIENTE";
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-primary');
        $('#modalGestionCliente').modal('show');
    }

    function editar(id) {
        const data = clientesDB[id];
        if(!data) return;
        document.getElementById('id_edit').value = data.id;
        document.getElementById('nombre').value = data.nombre;
        document.getElementById('dni').value = (data.dni == 'No registrado') ? '' : data.dni;
        document.getElementById('telefono').value = (data.telefono == 'No registrado') ? '' : data.telefono;
        document.getElementById('limite').value = data.limite;
        document.getElementById('titulo-modal').innerText = "Editar Cliente";
        let btn = document.getElementById('btn-guardar');
        btn.innerText = "ACTUALIZAR DATOS";
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-warning');
        $('#modalGestionCliente').modal('show');
    }

    function verResumen(id) {
        const data = clientesDB[id];
        if(!data) return;
        document.getElementById('modal-nombre').innerText = data.nombre;
        document.getElementById('modal-dni').innerText = 'DNI: ' + data.dni;
        document.getElementById('modal-avatar').innerText = data.nombre.substring(0,2).toUpperCase();
        
        const format = (n) => '$ ' + new Intl.NumberFormat('es-AR').format(n);
        let deuda = parseFloat(data.deuda);
        let limite = parseFloat(data.limite);
        
        let elDeuda = document.getElementById('modal-deuda');
        elDeuda.innerText = format(deuda);
        elDeuda.className = (deuda > 0.1) ? 'stat-value text-danger' : 'stat-value text-success';
        document.getElementById('modal-disponible').innerText = (limite > 0) ? format(limite - deuda) : 'Ilimitado';
        document.getElementById('modal-ultima').innerText = data.ultima_venta;
        document.getElementById('modal-puntos').innerText = data.puntos;
        document.getElementById('modal-telefono').innerText = data.telefono;
        document.getElementById('btn-ir-cuenta').href = 'cuenta_cliente.php?id=' + data.id;
        $('#modalResumen').modal('show');
    }

    // --- FILTROS Y BUSCADOR ---
    function filtrarEstado(tipo) {
        let tr = document.querySelectorAll('.cliente-row');
        tr.forEach(row => {
            let esDeudor = row.getAttribute('data-es-deudor') === '1';
            if(tipo === 'deuda') row.style.display = esDeudor ? '' : 'none';
            else if(tipo === 'aldia') row.style.display = !esDeudor ? '' : 'none';
            else row.style.display = '';
        });
    }

    function filtrarClientes() {
        let filter = document.getElementById("buscador").value.toUpperCase();
        let tr = document.getElementById("tablaClientes").getElementsByTagName("tr");
        for (let i = 0; i < tr.length; i++) {
            let td = tr[i].querySelector(".nombre-cliente");
            let tdDni = tr[i].querySelector(".dni-cliente");
            if (td) {
                let txt = td.textContent || td.innerText;
                let dni = tdDni ? (tdDni.textContent || tdDni.innerText) : '';
                if (txt.toUpperCase().indexOf(filter) > -1 || dni.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    // --- BORRAR ---
    function borrarCliente(id) {
        Swal.fire({
            title: '¿Eliminar Cliente?',
            text: "No se podrá recuperar.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'clientes.php?borrar=' + id;
            }
        });
    }

    // --- ALERTAS ---
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
        Swal.fire('Eliminado', 'El cliente ha sido eliminado.', 'success');
    <?php endif; ?>
    <?php if(isset($_GET['error']) && $_GET['error'] == 'tiene_datos'): ?>
        Swal.fire('Atención', 'No se puede eliminar porque tiene historial de ventas o movimientos.', 'warning');
    <?php endif; ?>
    <?php if(isset($_GET['error']) && $_GET['error'] == 'db'): ?>
        Swal.fire('Error', 'Error en la base de datos.', 'error');
    <?php endif; ?>
</script>

<?php include 'includes/layout_footer.php'; ?>