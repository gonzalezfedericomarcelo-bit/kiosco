<?php
// combos.php - CORREGIDO: ENLACE DE IDS Y STOCK 0
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// --- FUNCIONES AUXILIARES ---

// Función nueva: Busca el ID en la tabla COMBOS (no en productos)
function obtenerIdComboPorCodigo($conexion, $codigo) {
    $stmt = $conexion->prepare("SELECT id FROM combos WHERE codigo_barras = ? LIMIT 1");
    $stmt->execute([$codigo]);
    return $stmt->fetchColumn();
}

// --- LOGICA BACKEND ---

// 1. CREAR OFERTA (COMBO)
if (isset($_POST['crear_combo'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $codigo = $_POST['codigo']; 

    if(empty($codigo)) {
        $codigo = 'COMBO-' . time();
    }

    try {
        $conexion->beginTransaction();

        // A. Insertar en tabla UI (COMBOS)
        $stmtC = $conexion->prepare("INSERT INTO combos (nombre, precio, codigo_barras, activo) VALUES (?, ?, ?, 1)");
        $stmtC->execute([$nombre, $precio, $codigo]);
        
        // B. Insertar en tabla STOCK (PRODUCTOS)
        // CAMBIO: Ponemos stock_actual en 0. La caja ignorará esto y mirará los ingredientes.
        $check = $conexion->prepare("SELECT id FROM productos WHERE codigo_barras = ?");
        $check->execute([$codigo]);
        
        if($check->rowCount() == 0) {
            $sql_tienda = "INSERT INTO productos (descripcion, precio_venta, codigo_barras, tipo, id_categoria, id_proveedor, stock_actual, activo, es_destacado_web) 
                           VALUES (?, ?, ?, 'combo', 2, 1, 0, 1, 1)";
            $conexion->prepare($sql_tienda)->execute([$nombre, $precio, $codigo]);
        } else {
            $sql_upd = "UPDATE productos SET precio_venta = ?, tipo = 'combo', descripcion = ? WHERE codigo_barras = ?";
            $conexion->prepare($sql_upd)->execute([$precio, $nombre, $codigo]);
        }

        $conexion->commit();
        header("Location: combos.php?msg=creado"); exit;

    } catch (Exception $e) {
        $conexion->rollBack();
        echo "Error: " . $e->getMessage(); exit;
    }
}

// 2. AGREGAR PRODUCTO A LA OFERTA
if (isset($_POST['agregar_item'])) {
    $combo_codigo = $_POST['combo_codigo']; // Código del combo (ej: PACK-FERNET)
    $id_prod_hijo = $_POST['id_producto'];  // ID del Fernet
    $cant = $_POST['cantidad'];
    
    // CORRECCIÓN CRÍTICA: Buscamos el ID en la tabla 'combos', NO en 'productos'
    $id_combo_real = obtenerIdComboPorCodigo($conexion, $combo_codigo);

    if ($id_combo_real) {
        // Ahora sí guardamos el ID correcto en la tabla combo_items
        
        $check = $conexion->prepare("SELECT id FROM combo_items WHERE id_combo = ? AND id_producto = ?");
        $check->execute([$id_combo_real, $id_prod_hijo]);
        
        if ($check->rowCount() > 0) {
            $conexion->prepare("UPDATE combo_items SET cantidad = cantidad + ? WHERE id_combo = ? AND id_producto = ?")->execute([$cant, $id_combo_real, $id_prod_hijo]);
        } else {
            $conexion->prepare("INSERT INTO combo_items (id_combo, id_producto, cantidad) VALUES (?, ?, ?)")->execute([$id_combo_real, $id_prod_hijo, $cant]);
        }
        header("Location: combos.php?msg=agregado"); exit;
    } else {
        echo "Error: No se encontró la oferta en la base de datos de combos."; exit;
    }
}

// 3. ELIMINAR ITEM
if (isset($_GET['borrar_item'])) {
    $id_item = $_GET['borrar_item'];
    $conexion->prepare("DELETE FROM combo_items WHERE id = ?")->execute([$id_item]);
    header("Location: combos.php?msg=item_borrado"); exit;
}

// 4. ELIMINAR OFERTA
if (isset($_GET['eliminar_id'])) {
    $id = $_GET['eliminar_id'];
    
    $stmt = $conexion->prepare("SELECT codigo_barras FROM combos WHERE id = ?");
    $stmt->execute([$id]);
    $cod = $stmt->fetchColumn();

    $conexion->prepare("DELETE FROM combos WHERE id = ?")->execute([$id]);
    if($cod) {
        $conexion->prepare("UPDATE productos SET activo = 0 WHERE codigo_barras = ?")->execute([$cod]);
    }
    header("Location: combos.php?msg=eliminado"); exit;
}

// --- CONSULTAS ---
$combos = $conexion->query("SELECT * FROM combos WHERE activo=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 AND tipo != 'combo' ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

// Datos visuales
$recetas_data = [];
foreach($combos as $c) {
    // Para mostrar la lista, usamos el ID del combo directo que es lo que guardamos ahora
    $items = [];
    $costo_total = 0;
    $precio_regular = 0;

    $sql_items = "SELECT ci.id, ci.cantidad, p.descripcion, p.precio_costo, p.precio_venta 
                  FROM combo_items ci 
                  JOIN productos p ON ci.id_producto = p.id 
                  WHERE ci.id_combo = ?"; // Ahora buscamos por ID de combo correctamente
    $stmtItems = $conexion->prepare($sql_items);
    $stmtItems->execute([$c['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($items as $i) { 
        $costo_total += ($i['precio_costo'] * $i['cantidad']); 
        $precio_regular += ($i['precio_venta'] * $i['cantidad']);
    }
    
    $recetas_data[$c['id']] = [
        'items' => $items,
        'costo' => $costo_total,
        'precio_regular' => $precio_regular,
        'ganancia' => $c['precio'] - $costo_total
    ];
}

$json_db = json_encode($recetas_data);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Ofertas y Combos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 80px; }
        .card-combo { border: 0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.2s; }
        .card-combo:hover { transform: translateY(-3px); }
        .combo-header { background: #fff; padding: 15px; border-bottom: 1px solid #eee; }
        .combo-body { padding: 15px; background: #fff; }
        .price-tag { font-size: 1.5rem; font-weight: 800; color: #198754; }
        .badge-code { background: #e9ecef; color: #495057; font-family: monospace; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .btn-action { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 0; transition: all 0.2s; }
        .btn-view { background: #e7f5ff; color: #0d6efd; }
        .btn-add { background: #d1e7dd; color: #198754; }
        .btn-del { background: #f8d7da; color: #dc3545; }
        .item-row { border-bottom: 1px solid #f0f0f0; padding: 8px 0; font-size: 0.9rem; }
        .item-row:last-child { border-bottom: 0; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0 text-dark"><i class="bi bi-tags-fill text-warning"></i> Mis Ofertas</h2>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg"></i> NUEVA OFERTA
            </button>
        </div>

        <div class="row g-3">
            <?php foreach($combos as $c): 
                $data = $recetas_data[$c['id']];
                $has_items = count($data['items']) > 0;
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-combo h-100 d-flex flex-column">
                    <div class="combo-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1 text-truncate" style="max-width: 200px;"><?php echo $c['nombre']; ?></h5>
                            <span class="badge-code"><?php echo $c['codigo_barras']; ?></span>
                        </div>
                        <div class="text-end">
                            <div class="price-tag">$<?php echo number_format($c['precio'], 0, ',', '.'); ?></div>
                            <small class="text-muted fw-bold">Precio Final</small>
                        </div>
                    </div>
                    
                    <div class="combo-body flex-grow-1">
                        <h6 class="text-muted small fw-bold mb-2">CONTENIDO:</h6>
                        <?php if(!$has_items): ?>
                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="bi bi-exclamation-circle"></i> Sin productos.<br>Agrega items para activar el descuento de stock.
                            </div>
                        <?php else: ?>
                            <div style="max-height: 120px; overflow-y: auto;">
                                <?php foreach($data['items'] as $i): ?>
                                    <div class="item-row d-flex justify-content-between">
                                        <span><b class="text-primary"><?php echo $i['cantidad']; ?>x</b> <?php echo $i['descripcion']; ?></span>
                                        <a href="combos.php?borrar_item=<?php echo $i['id']; ?>" class="text-danger"><i class="bi bi-x"></i></a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 pt-2 border-top d-flex justify-content-between small text-muted">
                                <span>Valor Real: <span class="text-decoration-line-through">$<?php echo number_format($data['precio_regular'],0,',','.'); ?></span></span>
                                <span class="text-success fw-bold">Ganancia: $<?php echo number_format($data['ganancia'],0,',','.'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3 bg-light d-flex justify-content-end gap-2">
                        <button class="btn-action btn-add" onclick='abrirAgregar(<?php echo $c['id']; ?>, "<?php echo $c['nombre']; ?>", "<?php echo $c['codigo_barras']; ?>")' title="Agregar Producto">
                            <i class="bi bi-plus-lg fw-bold"></i>
                        </button>
                        <button class="btn-action btn-del" onclick="borrarPack(<?php echo $c['id']; ?>)" title="Eliminar Oferta">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Crear Nueva Oferta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="crear_combo" value="1">
                        
                        <label class="form-label fw-bold">Nombre de la Oferta</label>
                        <input type="text" name="nombre" class="form-control form-control-lg mb-3" placeholder="Ej: Promo Fernet" required>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label fw-bold">Precio ($)</label>
                                <input type="number" name="precio" class="form-control form-control-lg text-success fw-bold" placeholder="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Código (Opcional)</label>
                                <input type="text" name="codigo" class="form-control form-control-lg" placeholder="Automático">
                            </div>
                        </div>
                        <div class="form-text mb-4">Si dejas el código vacío, se creará uno automáticamente.</div>
                        
                        <button class="btn btn-primary w-100 fw-bold py-3">GUARDAR OFERTA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Agregar a: <span id="titulo_agregar"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="agregar_item" value="1">
                        <input type="hidden" name="combo_codigo" id="combo_codigo_input">
                        
                        <label class="form-label fw-bold">Buscar Producto</label>
                        <select name="id_producto" class="form-select select2" required style="width:100%">
                            <option value="">Elegir producto...</option>
                            <?php foreach($productos as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo $p['descripcion']; ?> (Stock: <?php echo floatval($p['stock_actual']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label fw-bold mt-3">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control form-control-lg mb-4 text-center fw-bold" value="1" min="1">
                        
                        <button class="btn btn-success w-100 fw-bold py-3">AGREGAR ITEM</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#modalAgregar'),
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Buscar por nombre...'
            });
        });

        function abrirAgregar(id, nombre, codigo) {
            $('#titulo_agregar').text(nombre);
            $('#combo_codigo_input').val(codigo);
            new bootstrap.Modal(document.getElementById('modalAgregar')).show();
        }

        function borrarPack(id) {
            Swal.fire({
                title: '¿Eliminar Oferta?',
                text: "Se borrará de la lista y del stock.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'combos.php?eliminar_id=' + id;
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('msg')){
            Swal.fire({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                icon: 'success', title: 'Operación Exitosa'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>