<?php
session_start();
require_once 'includes/db.php';

// Validar ID
if (!isset($_GET['id'])) { header("Location: sorteos.php"); exit; }
$id = $_GET['id'];

// Obtener datos del sorteo
$stmt = $conexion->prepare("SELECT * FROM sorteos WHERE id = ?");
$stmt->execute([$id]);
$sorteo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sorteo) { header("Location: sorteos.php"); exit; }

// Obtener premios y tickets
$premios = $conexion->query("SELECT sp.*, p.descripcion as prod_nombre FROM sorteo_premios sp LEFT JOIN productos p ON sp.id_producto = p.id WHERE id_sorteo = $id ORDER BY posicion ASC")->fetchAll(PDO::FETCH_ASSOC);
$tickets = $conexion->query("SELECT st.*, c.nombre FROM sorteo_tickets st JOIN clientes c ON st.id_cliente = c.id WHERE id_sorteo = $id ORDER BY numero_ticket ASC")->fetchAll(PDO::FETCH_ASSOC);
$clientes = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $conexion->query("SELECT id, descripcion FROM productos WHERE activo = 1 ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

$cantidad_vendidos = count($tickets);
$numeros_ocupados = array_column($tickets, 'numero_ticket');

// --- 1. AGREGAR PREMIO ---
if (isset($_POST['add_premio'])) {
    $pos = $_POST['posicion'];
    $tipo = $_POST['tipo'];
    $id_prod = ($tipo == 'interno') ? $_POST['id_producto'] : NULL;
    $desc_ext = ($tipo == 'externo') ? $_POST['descripcion_externa'] : NULL;
    
    $conexion->prepare("INSERT INTO sorteo_premios (id_sorteo, posicion, tipo, id_producto, descripcion_externa) VALUES (?,?,?,?,?)")
             ->execute([$id, $pos, $tipo, $id_prod, $desc_ext]);
    header("Location: detalle_sorteo.php?id=$id"); exit;
}

// --- 2. VENDER TICKET (CON AUDITORÍA Y CAJA REAL) ---
if (isset($_POST['vender_ticket'])) {
    $idCliente = $_POST['id_cliente'];
    $numeroElegido = $_POST['numero_elegido'];
    $precioTicket = $sorteo['precio_ticket'];
    $idUsuario = $_SESSION['usuario_id'];
    
    if(empty($numeroElegido)) {
        echo "<script>alert('¡Debes seleccionar un número de la grilla!'); window.location.href='detalle_sorteo.php?id=$id';</script>"; exit;
    }

    $chk = $conexion->query("SELECT id FROM sorteo_tickets WHERE id_sorteo = $id AND numero_ticket = $numeroElegido")->fetch();
    if($chk) {
        echo "<script>alert('¡Ese número ya fue vendido!'); window.location.href='detalle_sorteo.php?id=$id';</script>"; exit;
    }
    
    $caja = $conexion->query("SELECT id FROM cajas_sesion WHERE id_usuario = $idUsuario AND estado = 'abierta'")->fetch(PDO::FETCH_ASSOC);
    if (!$caja) {
        echo "<script>alert('¡ERROR! Abrí la caja para poder vender.'); window.location.href='detalle_sorteo.php?id=$id';</script>"; exit;
    }
    $idCaja = $caja['id'];

    try {
        $conexion->beginTransaction();

        // 1. Ticket en tabla sorteos
        $conexion->prepare("INSERT INTO sorteo_tickets (id_sorteo, id_cliente, numero_ticket) VALUES (?,?,?)")
                 ->execute([$id, $idCliente, $numeroElegido]);

        // 2. Registrar Venta en Caja (Identificada)
        $codRef = "RIFA-{$id}-NUM-{$numeroElegido}";
        $sqlVenta = "INSERT INTO ventas (codigo_ticket, id_caja_sesion, id_usuario, id_cliente, total, metodo_pago, estado, origen) VALUES (?, ?, ?, ?, ?, 'Efectivo', 'completada', 'local')";
        $conexion->prepare($sqlVenta)->execute([$codRef, $idCaja, $idUsuario, $idCliente, $precioTicket]);
        $idVenta = $conexion->lastInsertId();
        
        // 3. Actualizar Caja
        $conexion->query("UPDATE cajas_sesion SET total_ventas = total_ventas + $precioTicket, monto_final = IFNULL(monto_final, 0) + $precioTicket WHERE id = $idCaja");

        // 4. AUDITORÍA (ESTO FALTABA PARA QUE APAREZCA EN LOS LOGS)
        $nombreCliente = $conexion->query("SELECT nombre FROM clientes WHERE id = $idCliente")->fetchColumn();
        $detalleAudit = "Venta Ticket Rifa #$numeroElegido | Sorteo: {$sorteo['titulo']} | Valor: $$precioTicket | Cliente: $nombreCliente";
        $conexion->prepare("INSERT INTO auditoria (fecha, id_usuario, accion, detalles) VALUES (NOW(), ?, 'VENTA_RIFA', ?)")
                 ->execute([$idUsuario, $detalleAudit]);

        $conexion->commit();
        header("Location: detalle_sorteo.php?id=$id&msg=ticket_ok"); 
        exit;

    } catch (Exception $e) {
        $conexion->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// --- 3. EDITAR/ELIMINAR ---
if (isset($_POST['editar_sorteo'])) {
    $titulo = $_POST['titulo'];
    $fecha = $_POST['fecha'];
    if ($cantidad_vendidos == 0) {
        $precio = $_POST['precio']; $cantidad = $_POST['cantidad'];
        $sql = "UPDATE sorteos SET titulo=?, fecha_sorteo=?, precio_ticket=?, cantidad_tickets=? WHERE id=?";
        $params = [$titulo, $fecha, $precio, $cantidad, $id];
    } else {
        $sql = "UPDATE sorteos SET titulo=?, fecha_sorteo=? WHERE id=?";
        $params = [$titulo, $fecha, $id];
    }
    $conexion->prepare($sql)->execute($params);
    header("Location: detalle_sorteo.php?id=$id&msg=editado"); exit;
}

if (isset($_POST['eliminar_sorteo'])) {
    if ($cantidad_vendidos > 0) { echo "<script>alert('Hay tickets vendidos.');</script>"; } 
    else { $conexion->prepare("DELETE FROM sorteos WHERE id = ?")->execute([$id]); header("Location: sorteos.php?msg=eliminado"); exit; }
}

// --- 5. LOGICA DEL SORTEO (CORREGIDA: GASTOS REALES Y AUDITORÍA) ---
if (isset($_POST['ejecutar_sorteo'])) {
    if ($sorteo['estado'] != 'activo') die(json_encode(['error' => 'Ya finalizado']));
    
    $participantes = $conexion->query("SELECT id, id_cliente, numero_ticket FROM sorteo_tickets WHERE id_sorteo = $id")->fetchAll(PDO::FETCH_ASSOC);
    if (count($participantes) == 0) die(json_encode(['error' => 'No hay tickets vendidos.']));
    
    shuffle($participantes); 
    $ganadores = [];
    $uid = $_SESSION['usuario_id'];
    
    foreach ($premios as $index => $premio) {
        if (!isset($participantes[$index])) break; 
        $ganador = $participantes[$index];
        $ganadores[] = [
            'posicion' => $premio['posicion'],
            'premio' => ($premio['tipo']=='interno' ? $premio['prod_nombre'] : $premio['descripcion_externa']),
            'cliente' => $conexion->query("SELECT nombre FROM clientes WHERE id = {$ganador['id_cliente']}")->fetchColumn(),
            'ticket' => $ganador['numero_ticket']
        ];

        // LOGICA DE STOCK Y COSTO REAL (SOLUCIÓN AL $0)
        if ($premio['tipo'] == 'interno' && $premio['id_producto']) {
            // 1. Descontar Stock Principal
            $conexion->query("UPDATE productos SET stock_actual = stock_actual - 1 WHERE id = {$premio['id_producto']}");
            
            // 2. Calcular Costo Real
            // Buscamos si es un combo para sumar el costo de sus hijos
            $prodInfo = $conexion->query("SELECT tipo, precio_costo FROM productos WHERE id = {$premio['id_producto']}")->fetch(PDO::FETCH_ASSOC);
            $costo = $prodInfo['precio_costo'];

            if ($prodInfo['tipo'] == 'combo') {
                // Si es combo, el costo suele ser 0, así que sumamos los componentes
                $sqlCostoCombo = "SELECT SUM(p.precio_costo * ci.cantidad) as costo_total 
                                  FROM combo_items ci 
                                  JOIN productos p ON ci.id_producto = p.id 
                                  WHERE ci.id_combo = {$premio['id_producto']}";
                $costoCalc = $conexion->query($sqlCostoCombo)->fetchColumn();
                // Si la suma dio algo mayor a 0, usamos eso. Si no, usamos lo que tenía el producto.
                if ($costoCalc > 0) $costo = $costoCalc;
            }

            // 3. Registrar Gasto
            $descGasto = "Premio Sorteo #$id: " . $premio['prod_nombre'];
            // Buscamos caja abierta para asignar el gasto, sino a la última cerrada o null (para que aparezca en reportes)
            $cajaActiva = $conexion->query("SELECT id FROM cajas_sesion WHERE id_usuario = $uid AND estado = 'abierta'")->fetchColumn();
            $idCajaGasto = $cajaActiva ? $cajaActiva : 1; 

            $conexion->prepare("INSERT INTO gastos (descripcion, monto, categoria, id_usuario, fecha, id_caja_sesion) VALUES (?, ?, 'Sorteo', ?, NOW(), ?)")
                     ->execute([$descGasto, $costo, $uid, $idCajaGasto]);
        }
    }
    
    // GUARDAR GANADORES
    $jsonGanadores = json_encode($ganadores);
    $conexion->prepare("UPDATE sorteos SET estado = 'finalizado', ganadores_json = ? WHERE id = ?")->execute([$jsonGanadores, $id]);
    
    // REGISTRAR EN AUDITORÍA (SOLUCIÓN: LOG DEL SORTEO)
    $auditDetalle = "Sorteo Finalizado #$id ($titulo). Se entregaron " . count($ganadores) . " premios.";
    $conexion->prepare("INSERT INTO auditoria (fecha, id_usuario, accion, detalles) VALUES (NOW(), ?, 'SORTEO_FINALIZADO', ?)")
             ->execute([$uid, $auditDetalle]);

    echo json_encode(['status' => 'ok', 'ganadores' => $ganadores]);
    exit;
}
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    .roulette-container { border: 4px solid #102A57; border-radius: 15px; overflow: hidden; position: relative; background: #222; height: 100px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 20px rgba(0,0,0,0.8); }
    .roulette-window { font-size: 3rem; font-weight: bold; color: #fff; text-shadow: 0 0 10px #ff00de; font-family: 'Courier New', monospace; }
    .winner-card { animation: popIn 0.5s ease; border: 2px solid #ffc107; background: #fffbe6; }
    @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .confetti { position: absolute; width: 10px; height: 10px; background-color: #f00; animation: fall linear forwards; }
    @keyframes fall { to { transform: translateY(100vh) rotate(720deg); } }
    /* GRILLA */
    .grid-numeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 8px; max-height: 350px; overflow-y: auto; padding: 15px; border: 1px solid #ddd; border-radius: 10px; background: #fff; }
    .num-box { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.9rem; border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .num-libre { background: #f8f9fa; color: #495057; }
    .num-libre:hover { background: #e9ecef; border-color: #adb5bd; transform: translateY(-2px); }
    .num-ocupado { background: #dc3545; color: white; cursor: not-allowed; opacity: 0.5; border-color: #dc3545; }
    .num-seleccionado { background: #198754; color: white; transform: scale(1.1); box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3); border-color: #198754; }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="sorteos.php" class="text-decoration-none text-muted mb-2 d-block"><i class="bi bi-arrow-left"></i> Volver</a>
            <h2 class="fw-bold text-primary mb-0">
                <?php echo htmlspecialchars($sorteo['titulo']); ?>
                <span class="badge <?php echo $sorteo['estado']=='activo'?'bg-success':'bg-secondary'; ?> fs-6 align-middle ms-2"><?php echo strtoupper($sorteo['estado']); ?></span>
            </h2>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditar"><i class="bi bi-pencil"></i> Editar</button>
            <?php if($cantidad_vendidos == 0): ?>
            <form method="POST" onsubmit="return confirm('¿Eliminar sorteo?');">
                <button type="submit" name="eliminar_sorteo" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php else: ?>
                <button class="btn btn-outline-secondary" disabled title="Hay tickets vendidos"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
            <?php if($sorteo['estado'] == 'activo'): ?>
            <button class="btn btn-warning fw-bold shadow" onclick="iniciarSorteoVisual()"><i class="bi bi-trophy-fill me-2"></i> ¡SORTEAR!</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="zonaSorteo" class="mb-5 d-none">
        <div class="card bg-dark text-white text-center p-4 rounded-4 shadow-lg">
            <h3 class="mb-3 text-warning">¡BUSCANDO GANADOR!</h3>
            <div class="roulette-container mb-3"><div class="roulette-window" id="rouletteDisplay">000</div></div>
            <h4 id="premioActualDisplay" class="text-info"></h4>
        </div>
    </div>

    <?php if($sorteo['estado'] == 'finalizado' && $sorteo['ganadores_json']): 
        $ganadoresData = json_decode($sorteo['ganadores_json'], true);
    ?>
    <div class="card border-warning mb-4 shadow-sm bg-light">
        <div class="card-header bg-warning text-dark fw-bold text-center"><i class="bi bi-star-fill me-2"></i> GANADORES OFICIALES</div>
        <div class="card-body">
            <div class="row text-center g-3">
                <?php foreach($ganadoresData as $g): ?>
                <div class="col-md-4">
                    <div class="card winner-card h-100 p-3">
                        <div class="display-4">🥇</div>
                        <h5 class="fw-bold mt-2">Puesto #<?php echo $g['posicion']; ?></h5>
                        <h4 class="text-primary fw-bold text-uppercase"><?php echo $g['cliente']; ?></h4>
                        <p class="text-muted mb-0">Ticket #<?php echo str_pad($g['ticket'], 3, '0', STR_PAD_LEFT); ?></p>
                        <hr>
                        <small class="text-success fw-bold"><?php echo $g['premio']; ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white fw-bold py-3">1. Definir Premios</div>
                <div class="card-body">
                    <div class="alert alert-info small py-2"><i class="bi bi-info-circle me-1"></i> Tip: Crea un <strong>Pack/Combo</strong> en Inventario para sortear varios productos juntos.</div>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach($premios as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><span class="badge bg-primary rounded-circle me-2"><?php echo $p['posicion']; ?></span><?php echo $p['tipo']=='interno' ? $p['prod_nombre'] : $p['descripcion_externa']; ?></div>
                            <?php if($p['tipo']=='interno'): ?><span class="badge bg-light text-dark border">Stock</span><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if($sorteo['estado'] == 'activo'): ?>
                    <form method="POST" class="bg-light p-3 rounded">
                        <h6 class="fw-bold small text-muted">AGREGAR PREMIO</h6>
                        <div class="row g-2">
                            <div class="col-4"><input type="number" name="posicion" class="form-control form-control-sm" placeholder="Puesto" required></div>
                            <div class="col-8"><select name="tipo" class="form-select form-select-sm" onchange="togglePremio(this.value)"><option value="interno">Producto / Combo</option><option value="externo">Externo (Texto)</option></select></div>
                        </div>
                        <div class="mt-2" id="inputInterno">
                            <select name="id_producto" class="form-select form-select-sm select2">
                                <?php foreach($productos as $prod): ?><option value="<?php echo $prod['id']; ?>"><?php echo $prod['descripcion']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mt-2 d-none" id="inputExterno"><input type="text" name="descripcion_externa" class="form-control form-control-sm" placeholder="Ej: Microondas..."></div>
                        <button type="submit" name="add_premio" class="btn btn-primary btn-sm w-100 mt-2 fw-bold">Guardar Premio</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span>2. Venta de Tickets (<?php echo $cantidad_vendidos; ?>/<?php echo $sorteo['cantidad_tickets']; ?>)</span>
                    <span class="badge bg-success text-white p-2">$<?php echo number_format($cantidad_vendidos * $sorteo['precio_ticket'], 2); ?> Recaudado</span>
                </div>
                <div class="card-body">
                    <?php if($sorteo['estado'] == 'activo'): ?>
                    <div class="row">
                        <div class="col-md-5">
                            <form method="POST" id="formVenta" class="bg-light p-3 rounded">
                                <label class="small fw-bold">1. Elegir Cliente</label>
                                <select name="id_cliente" class="form-select mb-3">
                                    <?php foreach($clientes as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option><?php endforeach; ?>
                                </select>
                                
                                <label class="small fw-bold">2. Número Elegido</label>
                                <input type="text" name="numero_elegido" id="inputNumeroElegido" class="form-control mb-3 text-center fw-bold fs-4" readonly placeholder="Click en grilla" required>

                                <button type="submit" name="vender_ticket" class="btn btn-success w-100 fw-bold"><i class="bi bi-cash"></i> Cobrar Ticket</button>
                            </form>
                        </div>
                        <div class="col-md-7">
                            <label class="small fw-bold mb-2">Disponibilidad de Números:</label>
                            <div class="grid-numeros">
                                <?php for($i=1; $i<=$sorteo['cantidad_tickets']; $i++): 
                                    $ocupado = in_array($i, $numeros_ocupados);
                                    $claseNum = $ocupado ? 'num-ocupado' : 'num-libre';
                                ?>
                                <div class="num-box <?php echo $claseNum; ?>" onclick="<?php echo $ocupado ? '' : "seleccionarNumero($i, this)"; ?>">
                                    <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr>
                    <h6 class="text-muted small fw-bold">Últimos Vendidos:</h6>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm table-hover">
                            <thead class="table-light"><tr><th>#Ticket</th><th>Cliente</th><th>Fecha</th></tr></thead>
                            <tbody>
                                <?php foreach($tickets as $t): ?>
                                <tr>
                                    <td><span class="badge bg-dark rounded-pill">#<?php echo str_pad($t['numero_ticket'], 3, '0', STR_PAD_LEFT); ?></span></td>
                                    <td><?php echo $t['nombre']; ?></td>
                                    <td class="small text-muted"><?php echo date('d/m H:i', strtotime($t['fecha_compra'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Sorteo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label>Título</label><input type="text" name="titulo" class="form-control" value="<?php echo $sorteo['titulo']; ?>" required></div>
                <div class="mb-3"><label>Fecha Sorteo</label><input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d', strtotime($sorteo['fecha_sorteo'])); ?>" required></div>
                <?php if($cantidad_vendidos == 0): ?>
                <div class="row">
                    <div class="col-6 mb-3"><label>Precio Ticket</label><input type="number" name="precio" class="form-control" step="0.01" value="<?php echo $sorteo['precio_ticket']; ?>" required></div>
                    <div class="col-6 mb-3"><label>Cantidad Total</label><input type="number" name="cantidad" class="form-control" value="<?php echo $sorteo['cantidad_tickets']; ?>" required></div>
                </div>
                <?php else: ?><div class="alert alert-warning small"><i class="bi bi-lock-fill"></i> Precio y cantidad bloqueados.</div><?php endif; ?>
            </div>
            <div class="modal-footer"><button type="submit" name="editar_sorteo" class="btn btn-primary">Guardar Cambios</button></div>
        </form>
    </div>
</div>

<script>
function togglePremio(val) {
    if(val === 'interno') { document.getElementById('inputInterno').classList.remove('d-none'); document.getElementById('inputExterno').classList.add('d-none'); } 
    else { document.getElementById('inputInterno').classList.add('d-none'); document.getElementById('inputExterno').classList.remove('d-none'); }
}
function seleccionarNumero(num, element) {
    document.getElementById('inputNumeroElegido').value = num;
    document.querySelectorAll('.num-box').forEach(el => el.classList.remove('num-seleccionado'));
    element.classList.add('num-seleccionado');
}
function iniciarSorteoVisual() { Swal.fire({ title: '¿Confirmar Sorteo?', text: "Se sortearán los premios disponibles entre los participantes.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, sortear', confirmButtonColor: '#ffc107' }).then((result) => { if (result.isConfirmed) { realizarSorteoBackend(); } }); }
function realizarSorteoBackend() {
    document.getElementById('zonaSorteo').classList.remove('d-none'); window.scrollTo({ top: 0, behavior: 'smooth' });
    const formData = new FormData(); formData.append('ejecutar_sorteo', true);
    fetch(window.location.href, { method: 'POST', body: formData }).then(response => response.json()).then(data => {
        if(data.error) { Swal.fire('Error', data.error, 'error'); return; }
        animarGanadores(data.ganadores, 0);
    }).catch(err => console.error(err));
}
function animarGanadores(ganadores, index) {
    if (index >= ganadores.length) { confettiEffect(); setTimeout(() => { Swal.fire({ title: '¡Sorteo Finalizado!', icon: 'success' }).then(() => location.reload()); }, 3000); return; }
    const ganador = ganadores[index];
    const display = document.getElementById('rouletteDisplay');
    const premioDisplay = document.getElementById('premioActualDisplay');
    premioDisplay.innerText = `Sorteando: ${ganador.premio} (Puesto ${ganador.posicion})`;
    let counter = 0;
    const interval = setInterval(() => {
        display.innerText = Math.floor(Math.random() * 100).toString().padStart(3, '0'); counter++;
        if (counter > 20) { clearInterval(interval); display.innerText = ganador.cliente.toUpperCase(); display.style.color = '#ffc107'; setTimeout(() => { display.style.color = '#fff'; animarGanadores(ganadores, index + 1); }, 2000); }
    }, 100);
}
function confettiEffect() { for(let i=0; i<50; i++) { const conf = document.createElement('div'); conf.classList.add('confetti'); conf.style.left = Math.random() * 100 + 'vw'; conf.style.backgroundColor = ['#f00', '#0f0', '#00f', '#ff0'][Math.floor(Math.random()*4)]; conf.style.animationDuration = Math.random() * 3 + 2 + 's'; document.body.appendChild(conf); } }
</script>

<?php include 'includes/layout_footer.php'; ?>