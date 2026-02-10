<?php
// canje_puntos.php - VERSIÓN FINAL (Doble Columna: Buscador + Top Clientes)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. CONEXIÓN
$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$mensaje_sweet = '';
$resultados_busqueda = [];
$cliente_seleccionado = null;

// --- DATOS PARA WIDGETS ---
// 1. Total Premios
$stmtW1 = $conexion->query("SELECT COUNT(*) FROM premios WHERE activo = 1");
$totalPremios = $stmtW1->fetchColumn();

// 2. Canjes Hoy
$stmtW2 = $conexion->query("SELECT COUNT(*) FROM auditoria WHERE accion = 'CANJE' AND DATE(fecha) = CURDATE()");
$canjesHoy = $stmtW2->fetchColumn();

// 3. Puntos Circulantes
$stmtW3 = $conexion->query("SELECT SUM(puntos_acumulados) FROM clientes");
$puntosTotales = $stmtW3->fetchColumn() ?: 0;

// 4. TOP 10 CLIENTES CON MÁS PUNTOS (Para la columna derecha)
$topClientes = $conexion->query("SELECT * FROM clientes WHERE puntos_acumulados > 0 ORDER BY puntos_acumulados DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);


// 2. LÓGICA DE BÚSQUEDA CLÁSICA
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = trim($_GET['q']);
    $term = "%$q%";
    $sql = "SELECT * FROM clientes WHERE nombre LIKE ? OR dni LIKE ? OR dni_cuit LIKE ? OR id = ? LIMIT 20";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$term, $term, $term, $q]);
    $resultados_busqueda = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. SELECCIÓN DE CLIENTE
if (isset($_GET['id_cliente'])) {
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['id_cliente']]);
    $cliente_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. PROCESAR CANJE
if (isset($_POST['canjear']) && $cliente_seleccionado) {
    $id_cliente = $_POST['id_cliente'];
    $id_premio = $_POST['id_premio'];
    
    try {
        $conexion->beginTransaction();
        
        $stmtC = $conexion->prepare("SELECT puntos_acumulados FROM clientes WHERE id = ?");
        $stmtC->execute([$id_cliente]);
        $pts_actuales = $stmtC->fetchColumn();
        
        $stmtP = $conexion->prepare("SELECT * FROM premios WHERE id = ?");
        $stmtP->execute([$id_premio]);
        $premio = $stmtP->fetch(PDO::FETCH_ASSOC);
        
        if ($pts_actuales >= $premio['puntos_necesarios']) {
            $nuevo_saldo = $pts_actuales - $premio['puntos_necesarios'];
            $conexion->prepare("UPDATE clientes SET puntos_acumulados = ? WHERE id = ?")->execute([$nuevo_saldo, $id_cliente]);
            
            if ($premio['es_cupon'] == 1) {
                $monto = $premio['monto_dinero'];
                $conexion->prepare("UPDATE clientes SET saldo_favor = saldo_favor + ? WHERE id = ?")->execute([$monto, $id_cliente]);
                $txt_log = "Canje Cupón $$monto";
            } else {
                $conexion->prepare("UPDATE premios SET stock = stock - 1 WHERE id = ?")->execute([$id_premio]);
                $txt_log = "Canje Producto: " . $premio['nombre'];
            }
            
            $detalle = "$txt_log (-" . $premio['puntos_necesarios'] . " pts)";
            $conexion->prepare("INSERT INTO auditoria (id_usuario, accion, detalles, fecha) VALUES (?, 'CANJE', ?, NOW())")
                     ->execute([$_SESSION['usuario_id'], $detalle]);
            
            $conexion->commit();
            header("Location: canje_puntos.php?id_cliente=$id_cliente&exito=1");
            exit;
        } else {
            throw new Exception("Puntos insuficientes.");
        }
    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje_sweet = "Swal.fire('Error', '".$e->getMessage()."', 'error');";
    }
}

$premios = $conexion->query("SELECT * FROM premios WHERE activo = 1 ORDER BY puntos_necesarios ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/layout_header.php'; ?>

<style>
    /* BANNER AZUL ESTANDARIZADO */
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
    .stat-card:hover { transform: translateY(-3px); }
    .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

    /* ESTILOS PROPIOS DE CANJE */
    .search-container { position: relative; }
    .suggestions-list {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: white; border: 1px solid #ddd; border-radius: 0 0 15px 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; display: none;
    }
    .suggestion-item {
        padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;
    }
    .suggestion-item:hover { background-color: #f8f9fa; }
    
    .client-card-header { background: #102A57; color: white; padding: 20px; border-radius: 15px 15px 0 0; }
    .prize-card { transition: all 0.3s; border: 0; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow:hidden; }
    .prize-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .prize-card.disabled { opacity: 0.6; filter: grayscale(1); }
    
    .prize-icon-box { height: 100px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; margin-bottom: 15px; }
    
    /* Estilos Tabla Top */
    .table-top tr { cursor: pointer; transition: 0.2s; }
    .table-top tr:hover { background-color: #f1f8ff; }
</style>

<div class="header-blue">
    <i class="bi bi-gift-fill bg-icon-large"></i>
    <div class="container position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Centro de Canjes</h2>
                <p class="opacity-75 mb-0">Fidelización y recompensas</p>
            </div>
            <div>
                <?php if($cliente_seleccionado): ?>
                    <a href="canje_puntos.php" class="btn btn-outline-light rounded-pill fw-bold btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Cambiar Cliente
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Premios Activos</h6>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $totalPremios; ?></h2>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-award"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Canjes Hoy</h6>
                        <h2 class="mb-0 fw-bold text-success"><?php echo $canjesHoy; ?></h2>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card" title="Total de puntos en poder de clientes">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Puntos Circulantes</h6>
                        <h2 class="mb-0 fw-bold text-warning"><?php echo number_format($puntosTotales, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-stars"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    
    <?php if (!$cliente_seleccionado): ?>
        
        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg p-4 rounded-4 h-100">
                    <div class="text-center mb-4">
                        <div class="mb-3 text-primary"><i class="bi bi-search display-1"></i></div>
                        <h4 class="fw-bold">Buscar Cliente</h4>
                        <p class="text-muted small">Ingresa nombre o DNI para ver premios disponibles</p>
                    </div>
                    
                    <form method="GET" action="canje_puntos.php" autocomplete="off" class="position-relative">
                        <div class="search-container">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" id="buscador" name="q" class="form-control border-start-0" 
                                       placeholder="Escribe aquí..." value="<?php echo $_GET['q'] ?? ''; ?>" autofocus>
                            </div>
                            <div id="sugerencias" class="suggestions-list"></div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill">BUSCAR AHORA</button>
                        </div>
                    </form>

                    <?php if (!empty($resultados_busqueda)): ?>
                        <div class="card border-0 shadow-sm mt-4 rounded-4 overflow-hidden">
                            <div class="card-header bg-white fw-bold py-3">Resultados:</div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($resultados_busqueda as $cli): ?>
                                    <a href="canje_puntos.php?id_cliente=<?php echo $cli['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo $cli['nombre']; ?></div>
                                            <small class="text-muted">DNI: <?php echo $cli['dni']; ?></small>
                                        </div>
                                        <span class="badge bg-warning text-dark rounded-pill shadow-sm px-3 py-2">
                                            <i class="bi bi-star-fill"></i> <?php echo $cli['puntos_acumulados']; ?> pts
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-uppercase"><i class="bi bi-trophy text-warning me-2"></i> Top Clientes (Puntos)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-top table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th class="ps-4">Cliente</th>
                                        <th class="text-end pe-4">Puntos Acumulados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($topClientes) > 0): ?>
                                        <?php foreach($topClientes as $tc): ?>
                                            <tr onclick="window.location.href='canje_puntos.php?id_cliente=<?php echo $tc['id']; ?>'">
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark"><?php echo $tc['nombre']; ?></div>
                                                    <small class="text-muted">DNI: <?php echo $tc['dni']; ?></small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge bg-warning text-dark rounded-pill shadow-sm px-3">
                                                        <i class="bi bi-star-fill"></i> <?php echo number_format($tc['puntos_acumulados']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center py-4 text-muted">Aún no hay clientes con puntos.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <?php else: ?>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow h-100 rounded-4 overflow-hidden sticky-top" style="top: 20px; z-index: 1;">
                    <div class="client-card-header text-center pb-5">
                        <div class="display-1 mb-2"><i class="bi bi-person-circle"></i></div>
                        <h4 class="fw-bold m-0"><?php echo $cliente_seleccionado['nombre']; ?></h4>
                        <small class="opacity-75">DNI: <?php echo $cliente_seleccionado['dni'] ?: '--'; ?></small>
                    </div>
                    <div class="card-body text-center" style="margin-top: -40px;">
                        <div class="card border-0 shadow-sm mx-3 mb-4 rounded-4">
                            <div class="card-body py-4">
                                <small class="text-uppercase fw-bold text-muted">Puntos Disponibles</small>
                                <div class="display-4 fw-bold text-warning" style="text-shadow: 1px 1px 0px rgba(0,0,0,0.2);">
                                    <?php echo number_format($cliente_seleccionado['puntos_acumulados']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-3">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Saldo a Favor</span>
                                <span class="fw-bold text-success">$<?php echo $cliente_seleccionado['saldo_favor']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Deuda Actual</span>
                                <span class="fw-bold text-danger">$<?php echo $cliente_seleccionado['saldo_deudor'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <h5 class="fw-bold mb-3 d-flex align-items-center">
                    <span class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;"><i class="bi bi-trophy-fill"></i></span>
                    Premios Disponibles
                </h5>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                    <?php foreach($premios as $p): 
                        $pts = $cliente_seleccionado['puntos_acumulados'];
                        $req = $p['puntos_necesarios'];
                        $alcanza = $pts >= $req;
                    ?>
                    <div class="col">
                        <div class="card prize-card h-100 <?php echo $alcanza ? 'border-2 border-success' : 'disabled bg-light'; ?>">
                            <div class="prize-icon-box">
                                <?php if($p['es_cupon']): ?>
                                    <i class="bi bi-ticket-perforated display-4 text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-gift display-4 text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body text-center d-flex flex-column pt-0">
                                <h6 class="card-title fw-bold text-dark mb-1"><?php echo $p['nombre']; ?></h6>
                                
                                <?php if($p['es_cupon']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success mb-3 align-self-center">+$<?php echo $p['monto_dinero']; ?> en Crédito</span>
                                <?php else: ?>
                                    <span class="text-muted small mb-3">Stock: <?php echo $p['stock']; ?></span>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <div class="fw-bold fs-4 <?php echo $alcanza ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $req; ?> <small class="fs-6">pts</small>
                                    </div>
                                    
                                    <?php if($alcanza): ?>
                                        <button onclick="canjear(<?php echo $p['id']; ?>, '<?php echo $p['nombre']; ?>', <?php echo $req; ?>)" class="btn btn-success w-100 mt-2 fw-bold shadow-sm rounded-pill">
                                            CANJEAR
                                        </button>
                                    <?php else: ?>
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar bg-warning" style="width: <?php echo ($pts/$req)*100; ?>%"></div>
                                        </div>
                                        <small class="text-muted fw-bold d-block mt-1">Faltan <?php echo $req - $pts; ?> pts</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <form id="formCanje" method="POST"><input type="hidden" name="canjear" value="1"><input type="hidden" name="id_cliente" value="<?php echo $cliente_seleccionado['id']; ?>"><input type="hidden" name="id_premio" id="inputPremio"></form>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ALERTAS
    <?php echo $mensaje_sweet; ?>
    if(new URLSearchParams(window.location.search).get('exito')==='1') Swal.fire('¡Canje Exitoso!', 'Los puntos han sido descontados correctamente.', 'success');

    // LÓGICA DE CANJE
    function canjear(id, nombre, pts) {
        Swal.fire({
            title: '¿Confirmar Canje?',
            html: "Premio: <b>" + nombre + "</b><br>Costo: <b class='text-danger'>" + pts + " puntos</b>",
            icon: 'question', 
            showCancelButton: true, 
            confirmButtonText: 'Sí, confirmar', 
            cancelButtonText: 'Cancelar', 
            confirmButtonColor: '#102A57'
        }).then((r) => { 
            if(r.isConfirmed) { 
                document.getElementById('inputPremio').value = id; 
                document.getElementById('formCanje').submit(); 
            }
        });
    }

    // --- LÓGICA PREDICTIVA (JS PURO) ---
    const input = document.getElementById('buscador');
    const lista = document.getElementById('sugerencias');

    if(input){
        input.addEventListener('input', function() {
            const val = this.value;
            if (val.length < 2) { lista.style.display = 'none'; return; }

            // Usamos la misma lógica que en clientes para buscar
            // (Asumiendo que buscar_cliente_ajax.php devuelve JSON con id, nombre, dni, puntos)
            fetch('acciones/buscar_cliente_ajax.php?term=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    lista.innerHTML = '';
                    if (data.length > 0) {
                        lista.style.display = 'block';
                        data.forEach(c => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.innerHTML = `
                                <div>
                                    <div class="fw-bold text-dark">${c.nombre}</div>
                                    <small class="text-muted">DNI: ${c.dni}</small>
                                </div>
                                <span class="badge bg-warning text-dark rounded-pill ms-2">
                                    <i class="bi bi-star-fill"></i> ${c.puntos}
                                </span>
                            `;
                            item.onclick = () => {
                                window.location.href = 'canje_puntos.php?id_cliente=' + c.id;
                            };
                            lista.appendChild(item);
                        });
                    } else {
                        lista.style.display = 'none';
                    }
                })
                .catch(err => console.log('Error buscando clientes:', err));
        });

        // Cerrar lista si clic fuera
        document.addEventListener('click', function(e) {
            if (e.target !== input && e.target !== lista) {
                lista.style.display = 'none';
            }
        });
    }
</script>

<?php include 'includes/layout_footer.php'; ?>