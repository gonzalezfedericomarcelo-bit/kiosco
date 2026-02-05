<?php
// canje_puntos.php - TERMINAL DE FIDELIZACIÓN CON BÚSQUEDA PREDICTIVA
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

// 2. LÓGICA DE BÚSQUEDA CLÁSICA (Fallback si da Enter)
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = trim($_GET['q']);
    $term = "%$q%";
    $sql = "SELECT * FROM clientes WHERE nombre LIKE ? OR dni LIKE ? OR dni_cuit LIKE ? OR id = ? LIMIT 20";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$term, $term, $term, $q]);
    $resultados_busqueda = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // NOTA: Eliminé la redirección automática (count == 1) para que siempre elijas tú.
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
            header("Location: canje_puntos.php?id_cliente=$id_cliente&exito=1&msg=".urlencode($txt_log));
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Canje de Puntos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        
        /* BUSCADOR PREDICTIVO */
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
        .suggestion-item:last-child { border-bottom: none; }
        
        /* TARJETA CLIENTE */
        .client-card-header { background: linear-gradient(135deg, #198754 0%, #0f5132 100%); color: white; padding: 20px; border-radius: 15px 15px 0 0; }
        .prize-card { transition: transform 0.2s; border: none; shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .prize-card:hover { transform: translateY(-3px); }
        .prize-card.disabled { opacity: 0.5; filter: grayscale(1); }
    </style>
</head>
<body>

    <?php 
    if (file_exists('includes/menu.php')) include 'includes/menu.php';
    elseif (file_exists('menu.php')) include 'menu.php';
    ?>

    <div class="container mt-4">
        
        <?php if (!$cliente_seleccionado): ?>
            
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-success"><i class="bi bi-gift"></i> Canje de Puntos</h2>
                        <p class="text-muted">Busca al cliente para ver sus premios disponibles.</p>
                    </div>
                    
                    <div class="card border-0 shadow-sm p-4">
                        <form method="GET" action="canje_puntos.php" autocomplete="off">
                            <div class="search-container">
                                <label class="fw-bold small mb-1 text-muted">Nombre, DNI o CUIT</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" id="buscador" name="q" class="form-control form-control-lg border-start-0" 
                                           placeholder="Escribe para predecir..." value="<?php echo $_GET['q'] ?? ''; ?>" autofocus>
                                </div>
                                <div id="sugerencias" class="suggestions-list"></div>
                            </div>
                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-success fw-bold">VER RESULTADOS</button>
                            </div>
                        </form>
                    </div>

                    <?php if (!empty($resultados_busqueda)): ?>
                        <div class="mt-4">
                            <h6 class="text-muted small fw-bold text-uppercase">Coincidencias encontradas:</h6>
                            <div class="list-group shadow-sm">
                                <?php foreach ($resultados_busqueda as $cli): ?>
                                    <a href="canje_puntos.php?id_cliente=<?php echo $cli['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo $cli['nombre']; ?></div>
                                            <small class="text-muted">DNI: <?php echo $cli['dni']; ?></small>
                                        </div>
                                        <span class="badge bg-warning text-dark rounded-pill">
                                            <i class="bi bi-star-fill"></i> <?php echo $cli['puntos_acumulados']; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            
            <div class="row">
                <div class="col-12 mb-3">
                    <a href="canje_puntos.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver al buscador</a>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow h-100">
                        <div class="client-card-header text-center">
                            <div class="display-1 mb-2"><i class="bi bi-person-circle"></i></div>
                            <h4 class="fw-bold m-0"><?php echo $cliente_seleccionado['nombre']; ?></h4>
                            <small class="opacity-75">DNI: <?php echo $cliente_seleccionado['dni'] ?: '--'; ?></small>
                        </div>
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="py-3">
                                <small class="text-uppercase fw-bold text-muted">Puntos Disponibles</small>
                                <div class="display-3 fw-bold text-warning" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">
                                    <?php echo number_format($cliente_seleccionado['puntos_acumulados']); ?>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <div class="row">
                                    <div class="col-6 border-end">
                                        <div class="small text-muted">A Favor</div>
                                        <div class="fw-bold text-success">$<?php echo $cliente_seleccionado['saldo_favor']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Deuda</div>
                                        <div class="fw-bold text-danger">$<?php echo $cliente_seleccionado['saldo_deudor'] ?? 0; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <h5 class="fw-bold mb-3"><i class="bi bi-trophy text-warning"></i> Catálogo de Premios</h5>
                    <div class="row row-cols-2 row-cols-md-3 g-3">
                        <?php foreach($premios as $p): 
                            $pts = $cliente_seleccionado['puntos_acumulados'];
                            $req = $p['puntos_necesarios'];
                            $alcanza = $pts >= $req;
                        ?>
                        <div class="col">
                            <div class="card prize-card h-100 <?php echo $alcanza ? 'border-success' : 'disabled bg-light'; ?>">
                                <div class="card-body text-center d-flex flex-column">
                                    <div class="mb-2">
                                        <?php if($p['es_cupon']): ?>
                                            <i class="bi bi-ticket-perforated fs-1 text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-gift fs-1 text-primary"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-title fw-bold text-truncate" title="<?php echo $p['nombre']; ?>">
                                        <?php echo $p['nombre']; ?>
                                    </h6>
                                    <?php if($p['es_cupon']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success mb-2">+$<?php echo $p['monto_dinero']; ?> Crédito</span>
                                    <?php endif; ?>
                                    
                                    <div class="mt-auto pt-2">
                                        <div class="fw-bold <?php echo $alcanza ? 'text-success' : 'text-danger'; ?> fs-5">
                                            <?php echo $req; ?> Pts
                                        </div>
                                        <?php if($alcanza): ?>
                                            <button onclick="canjear(<?php echo $p['id']; ?>, '<?php echo $p['nombre']; ?>', <?php echo $req; ?>)" class="btn btn-success btn-sm w-100 mt-2 fw-bold">Canjear</button>
                                        <?php else: ?>
                                            <small class="text-muted d-block mt-2">Faltan <?php echo $req - $pts; ?></small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ALERTAS
        <?php echo $mensaje_sweet; ?>
        if(new URLSearchParams(window.location.search).get('exito')==='1') Swal.fire('¡Canje Exitoso!', 'Puntos descontados.', 'success');

        // LÓGICA DE CANJE
        function canjear(id, nombre, pts) {
            Swal.fire({
                title: '¿Canjear ' + nombre + '?',
                text: 'Se descontarán ' + pts + ' puntos.',
                icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, canjear', cancelButtonText: 'Cancelar', confirmButtonColor: '#198754'
            }).then((r) => { if(r.isConfirmed) { document.getElementById('inputPremio').value = id; document.getElementById('formCanje').submit(); }});
        }

        // --- LÓGICA PREDICTIVA (JS PURO) ---
        const input = document.getElementById('buscador');
        const lista = document.getElementById('sugerencias');

        if(input){
            input.addEventListener('input', function() {
                const val = this.value;
                if (val.length < 2) { lista.style.display = 'none'; return; }

                fetch('buscar_cliente_ajax.php?term=' + encodeURIComponent(val))
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
                                        <div class="fw-bold">${c.nombre}</div>
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
                    });
            });

            // Cerrar lista si clic fuera
            document.addEventListener('click', function(e) {
                if (e.target !== input && e.target !== lista) {
                    lista.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
