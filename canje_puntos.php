<?php
// canje_puntos.php - VERSIÓN FINAL AUTOMATIZADA
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$rutas_db = ['db.php', 'includes/db.php'];
foreach ($rutas_db as $ruta) { if (file_exists($ruta)) { require_once $ruta; break; } }

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$mensaje_sweet = ''; // Variable para disparar JS
$cliente = null;

// BUSCAR CLIENTE
if (isset($_GET['buscar_dni'])) {
    $busqueda = trim($_GET['buscar_dni']);
    // Busca en ambos campos
    $sql = "SELECT * FROM clientes WHERE dni_cuit = ? OR dni = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$busqueda, $busqueda]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        $mensaje_sweet = "Swal.fire('Error', 'No se encontró el cliente', 'error');";
    }
}

// PROCESAR CANJE
if (isset($_POST['canjear'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_premio = $_POST['id_premio'];
    
    try {
        $conexion->beginTransaction();
        
        // Datos frescos del cliente
        $stmtC = $conexion->prepare("SELECT puntos_acumulados, nombre FROM clientes WHERE id = ?");
        $stmtC->execute([$id_cliente]);
        $cliData = $stmtC->fetch(PDO::FETCH_ASSOC);
        
        // Datos del premio
        $stmtP = $conexion->prepare("SELECT * FROM premios WHERE id = ?");
        $stmtP->execute([$id_premio]);
        $premio = $stmtP->fetch(PDO::FETCH_ASSOC);
        
        if ($cliData['puntos_acumulados'] >= $premio['puntos_necesarios']) {
            
            // 1. Restar Puntos
            $nuevo_saldo_pts = $cliData['puntos_acumulados'] - $premio['puntos_necesarios'];
            $conexion->prepare("UPDATE clientes SET puntos_acumulados = ? WHERE id = ?")
                     ->execute([$nuevo_saldo_pts, $id_cliente]);
            
            // 2. Lógica según tipo de premio
            if ($premio['es_cupon'] == 1) {
                // ES DINERO: Sumar al saldo a favor del cliente
                $monto = $premio['monto_dinero'];
                $conexion->prepare("UPDATE clientes SET saldo_favor = saldo_favor + ? WHERE id = ?")
                         ->execute([$monto, $id_cliente]);
                         
                $txt_final = "Se acreditaron $$monto a la cuenta del cliente para su próxima compra.";
            } else {
                // ES PRODUCTO: Restar stock
                $conexion->prepare("UPDATE premios SET stock = stock - 1 WHERE id = ?")
                         ->execute([$id_premio]);
                $txt_final = "Entregue el producto: " . $premio['nombre'];
            }
            
            // 3. Auditoría
            $detalle = "Canje: " . $premio['nombre'] . " (" . $premio['puntos_necesarios'] . " pts)";
            $conexion->prepare("INSERT INTO auditoria (id_usuario, accion, detalles, fecha) VALUES (?, 'CANJE', ?, NOW())")
                     ->execute([$_SESSION['usuario_id'], $detalle]);
            
            $conexion->commit();
            
            // Redirección con mensaje de éxito para SweetAlert
            header("Location: canje_puntos.php?buscar_dni=".$_POST['dni_cliente']."&exito=1&txt=".urlencode($txt_final));
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
    <title>Canje de Puntos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <?php 
    if (file_exists('includes/menu.php')) include 'includes/menu.php';
    elseif (file_exists('menu.php')) include 'menu.php';
    ?>

    <div class="container mt-4 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-success"><i class="bi bi-stars"></i> Canje de Premios</h3>
            <a href="gestionar_premios.php" class="btn btn-dark btn-sm"><i class="bi bi-gear-fill"></i> Configurar Premios</a>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <label class="fw-bold mb-2">Buscar Cliente</label>
                        <form method="GET" class="d-flex gap-2">
                            <input type="number" name="buscar_dni" class="form-control" placeholder="DNI del Cliente" required value="<?php echo $_GET['buscar_dni'] ?? ''; ?>">
                            <button class="btn btn-success"><i class="bi bi-search"></i></button>
                        </form>
                    </div>
                </div>

                <?php if($cliente): ?>
                <div class="card bg-success text-white border-0 shadow mb-3">
                    <div class="card-body text-center py-4">
                        <p class="mb-0 text-white-50 fw-bold">PUNTOS DISPONIBLES</p>
                        <h1 class="display-3 fw-bold my-1"><?php echo number_format($cliente['puntos_acumulados']); ?></h1>
                        <h4 class="fw-bold mt-2"><?php echo $cliente['nombre']; ?></h4>
                        
                        <?php if($cliente['saldo_favor'] > 0): ?>
                            <div class="mt-3 bg-white text-success rounded p-2 fw-bold shadow-sm">
                                <i class="bi bi-cash-coin"></i> Saldo a Favor: $<?php echo number_format($cliente['saldo_favor'], 2); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">Catálogo de Premios</div>
                    <div class="list-group list-group-flush">
                        <?php foreach($premios as $p): ?>
                            <?php 
                                $alcanza = ($cliente && $cliente['puntos_acumulados'] >= $p['puntos_necesarios']);
                                $disabled = $alcanza ? '' : 'disabled';
                                $opacity = $alcanza ? '' : 'opacity-50';
                            ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center <?php echo $opacity; ?>">
                                <div>
                                    <h5 class="mb-1 fw-bold text-dark">
                                        <?php if($p['es_cupon']): ?>
                                            <i class="bi bi-ticket-perforated text-warning"></i> 
                                        <?php else: ?>
                                            <i class="bi bi-box-seam text-primary"></i> 
                                        <?php endif; ?>
                                        <?php echo $p['nombre']; ?>
                                    </h5>
                                    <span class="badge bg-secondary rounded-pill"><?php echo $p['puntos_necesarios']; ?> Pts</span>
                                    <?php if($p['es_cupon']): ?>
                                        <small class="text-success fw-bold ms-2">Credita $<?php echo $p['monto_dinero']; ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($cliente): ?>
                                    <button onclick="confirmarCanje('<?php echo $p['id']; ?>', '<?php echo $p['nombre']; ?>', <?php echo $p['puntos_necesarios']; ?>)" 
                                            class="btn btn-outline-success fw-bold" <?php echo $disabled; ?>>
                                        CANJEAR
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($cliente): ?>
    <form id="formCanje" method="POST">
        <input type="hidden" name="canjear" value="1">
        <input type="hidden" name="id_cliente" value="<?php echo $cliente['id']; ?>">
        <input type="hidden" name="dni_cliente" value="<?php echo $cliente['dni'] ?? $cliente['dni_cuit']; ?>"> <input type="hidden" name="id_premio" id="inputPremio">
        <input type="hidden" name="puntos_costo" id="inputPuntos">
    </form>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ALERTAS PHP
        <?php echo $mensaje_sweet; ?>
        
        // ALERTA ÉXITO (Viene por URL)
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('exito') === '1'){
            let txt = urlParams.get('txt');
            Swal.fire({
                icon: 'success',
                title: '¡Canje Exitoso!',
                text: txt,
                confirmButtonColor: '#198754'
            });
        }

        // CONFIRMACIÓN DE CANJE
        function confirmarCanje(id, nombre, costo) {
            Swal.fire({
                title: '¿Canjear ' + nombre + '?',
                text: "Se descontarán " + costo + " puntos al cliente.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, Canjear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('inputPremio').value = id;
                    document.getElementById('inputPuntos').value = costo;
                    document.getElementById('formCanje').submit();
                }
            })
        }
    </script>
</body>
</html>