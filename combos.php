<?php
// combos.php - GESTOR DE PACKS (CON AYUDA INTERACTIVA Y DETALLE DE CÁLCULOS)
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// --- LOGICA BACKEND ---

// 1. CREAR
if (isset($_POST['crear_combo'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $codigo = $_POST['codigo']; 
    $sql = "INSERT INTO combos (nombre, precio, codigo_barras, activo) VALUES (?, ?, ?, 1)";
    if($conexion->prepare($sql)->execute([$nombre, $precio, $codigo])) {
        header("Location: combos.php?msg=creado"); exit;
    }
}

// 2. EDITAR
if (isset($_POST['editar_combo'])) {
    $id = $_POST['id_combo'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $sql = "UPDATE combos SET nombre = ?, precio = ? WHERE id = ?";
    if($conexion->prepare($sql)->execute([$nombre, $precio, $id])) {
        header("Location: combos.php?msg=editado"); exit;
    }
}

// 3. AGREGAR ITEM
if (isset($_POST['agregar_item'])) {
    $id_combo = $_POST['id_combo'];
    $id_prod = $_POST['id_producto'];
    $cant = $_POST['cantidad'];
    
    // Validar duplicados
    $check = $conexion->prepare("SELECT id FROM combo_items WHERE id_combo = ? AND id_producto = ?");
    $check->execute([$id_combo, $id_prod]);
    
    if ($check->rowCount() > 0) {
        $conexion->prepare("UPDATE combo_items SET cantidad = cantidad + ? WHERE id_combo = ? AND id_producto = ?")->execute([$cant, $id_combo, $id_prod]);
    } else {
        $conexion->prepare("INSERT INTO combo_items (id_combo, id_producto, cantidad) VALUES (?, ?, ?)")->execute([$id_combo, $id_prod, $cant]);
    }
    header("Location: combos.php?msg=agregado"); exit;
}

// 4. ELIMINAR PACK
if (isset($_GET['eliminar_id'])) {
    $id = $_GET['eliminar_id'];
    $conexion->prepare("UPDATE combos SET activo = 0 WHERE id = ?")->execute([$id]);
    header("Location: combos.php?msg=eliminado"); exit;
}

// 5. BORRAR ITEM DE PACK
if (isset($_GET['borrar_item'])) {
    $id = $_GET['borrar_item'];
    $conexion->prepare("DELETE FROM combo_items WHERE id = ?")->execute([$id]);
    header("Location: combos.php?msg=item_borrado"); exit;
}

// --- CONSULTAS DE DATOS ---
$combos = $conexion->query("SELECT * FROM combos WHERE activo=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $conexion->query("SELECT id, descripcion, stock_actual FROM productos WHERE activo=1 AND tipo != 'combo' ORDER BY descripcion ASC")->fetchAll(PDO::FETCH_ASSOC);

// Preparamos datos completos y detallados para las explicaciones
$recetas_data = [];
foreach($combos as $c) {
    $items = $conexion->query("SELECT ci.id, ci.cantidad, p.descripcion, p.precio_costo, p.precio_venta 
                               FROM combo_items ci 
                               JOIN productos p ON ci.id_producto = p.id 
                               WHERE ci.id_combo = ".$c['id'])->fetchAll(PDO::FETCH_ASSOC);
    
    $costo_total = 0;
    $precio_regular_total = 0; 
    
    foreach($items as $i) { 
        $costo_total += ($i['precio_costo'] * $i['cantidad']); 
        $precio_regular_total += ($i['precio_venta'] * $i['cantidad']);
    }
    
    $recetas_data[$c['id']] = [
        'items' => $items,
        'costo' => $costo_total,
        'precio_regular' => $precio_regular_total,
        'ganancia' => $c['precio'] - $costo_total
    ];
}
$json_db = json_encode($recetas_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Mis Packs y Ofertas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; padding-bottom: 100px; }
        
        .card-combo { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s; position: relative; overflow: hidden; border-left: 5px solid #0d6efd; }
        .card-combo:active { transform: scale(0.98); }
        
        .combo-price { font-size: 1.6rem; font-weight: 800; color: #212529; }
        .combo-code { font-family: monospace; font-size: 0.85rem; background: #e9ecef; padding: 2px 6px; border-radius: 4px; color: #495057; }
        
        /* Botones de Acción */
        .action-bar { display: flex; justify-content: space-between; border-top: 1px solid #f0f0f0; padding-top: 15px; margin-top: 15px; }
        .btn-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; font-size: 1.1rem; transition: background 0.2s; }
        .btn-view { background: #e7f5ff; color: #0d6efd; }
        .btn-edit { background: #fff3cd; color: #ffc107; }
        .btn-build { background: #d1e7dd; color: #198754; }
        .btn-del { background: #f8d7da; color: #dc3545; }
        
        /* Data Rows */
        .data-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 8px; }
        .label-data { color: #6c757d; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .val-data { font-weight: 700; }
        .val-costo { color: #dc3545; }
        .val-regular { text-decoration: line-through; color: #adb5bd; }
        .val-ganancia { color: #198754; font-size: 0.95rem; }
        
        /* Botón de Ayuda (?) */
        .btn-help { cursor: pointer; color: #0d6efd; font-size: 0.9rem; transition: transform 0.2s; }
        .btn-help:hover { transform: scale(1.2); color: #0a58ca; }

        /* Modal Cantidad Gigante */
        .qty-wrapper { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0; }
        .btn-qty { width: 60px; height: 60px; border-radius: 50%; font-size: 1.5rem; font-weight: bold; border: none; display: flex; align-items: center; justify-content: center; }
        .btn-minus { background: #f8f9fa; color: #dc3545; border: 2px solid #f8d7da; }
        .btn-plus { background: #f8f9fa; color: #198754; border: 2px solid #d1e7dd; }
        .input-qty { width: 80px; height: 60px; font-size: 2rem; font-weight: bold; text-align: center; border: none; background: transparent; }
    </style>
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <div class="container pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0 text-dark">Mis Packs</h2>
                <small class="text-muted">Gestiona tus ofertas</small>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg"></i> CREAR
            </button>
        </div>

        <div class="row g-3">
            <?php if(empty($combos)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <h4>No hay combos creados</h4>
                    <p>Toca el botón "CREAR" para empezar.</p>
                </div>
            <?php endif; ?>

            <?php foreach($combos as $c): 
                $data = $recetas_data[$c['id']] ?? ['items'=>[], 'ganancia'=>0, 'costo'=>0, 'precio_regular'=>0];
                $cant_prod = count($data['items']);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-combo p-3 h-100 d-flex flex-column justify-content-between">
                    
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div style="overflow: hidden;">
                                <h5 class="fw-bold mb-1 text-dark text-truncate"><?php echo $c['nombre']; ?></h5>
                                <span class="combo-code"><?php echo $c['codigo_barras'] ?: 'S/C'; ?></span>
                            </div>
                            <div class="text-end">
                                <div class="combo-price">$<?php echo number_format($c['precio'],0,',','.'); ?></div>
                            </div>
                        </div>

                        <div class="bg-light rounded p-2 mb-2">
                            
                            <div class="data-row">
                                <span class="label-data">
                                    Costo Insumos 
                                    <i class="bi bi-question-circle-fill btn-help" onclick='explicarCosto(<?php echo $c['id']; ?>, "<?php echo htmlspecialchars($c['nombre']); ?>")'></i>
                                </span>
                                <span class="val-data val-costo">$<?php echo number_format($data['costo'], 0, ',', '.'); ?></span>
                            </div>
                            
                            <div class="data-row">
                                <span class="label-data">
                                    Suma Unitarios 
                                    <i class="bi bi-question-circle-fill btn-help" onclick='explicarRegular(<?php echo $c['id']; ?>, "<?php echo htmlspecialchars($c['nombre']); ?>")'></i>
                                </span>
                                <span class="val-data val-regular">$<?php echo number_format($data['precio_regular'], 0, ',', '.'); ?></span>
                            </div>

                            <div class="data-row border-top pt-2 mt-1">
                                <span class="label-data text-dark">Ganancia Neta:</span>
                                <span class="val-data val-ganancia">$<?php echo number_format($data['ganancia'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="action-bar">
                        <button class="btn-icon btn-view" onclick='verDetalle(<?php echo $c['id']; ?>, "<?php echo htmlspecialchars($c['nombre']); ?>")' title="Ver Contenido">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        <button class="btn-icon btn-build" onclick='armarReceta(<?php echo $c['id']; ?>, "<?php echo htmlspecialchars($c['nombre']); ?>")' title="Agregar Productos">
                            <i class="bi bi-layers-fill"></i>
                        </button>
                        <button class="btn-icon btn-edit" onclick='editarPack(<?php echo $c['id']; ?>, "<?php echo htmlspecialchars($c['nombre']); ?>", "<?php echo $c['precio']; ?>")' title="Editar Nombre/Precio">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn-icon btn-del" onclick="borrarPack(<?php echo $c['id']; ?>)" title="Eliminar">
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
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nuevo Pack</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="crear_combo" value="1">
                        <label class="fw-bold small text-muted">Nombre del Pack</label>
                        <input type="text" name="nombre" class="form-control form-control-lg mb-3 fw-bold" placeholder="Ej: Fernet + Coca" required>
                        
                        <label class="fw-bold small text-muted">Precio Oferta ($)</label>
                        <input type="number" name="precio" class="form-control form-control-lg mb-3 text-success fw-bold" placeholder="0" required>
                        
                        <label class="fw-bold small text-muted">Código Barras (Opcional)</label>
                        <input type="text" name="codigo" class="form-control mb-4" placeholder="Escanear...">
                        
                        <button class="btn btn-primary w-100 fw-bold py-2">CREAR AHORA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark">Editar Pack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="editar_combo" value="1">
                        <input type="hidden" name="id_combo" id="edit_id">
                        
                        <label class="fw-bold small text-muted">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control mb-3 fw-bold" required>
                        
                        <label class="fw-bold small text-muted">Precio ($)</label>
                        <input type="number" name="precio" id="edit_precio" class="form-control mb-4 fw-bold text-success" required>
                        
                        <button class="btn btn-warning w-100 fw-bold py-2">GUARDAR CAMBIOS</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReceta" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Agregar Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h5 id="receta_titulo" class="text-success fw-bold mb-4"></h5>
                    <form method="POST">
                        <input type="hidden" name="agregar_item" value="1">
                        <input type="hidden" name="id_combo" id="receta_id">
                        
                        <div class="text-start mb-4">
                            <label class="fw-bold small text-muted mb-1">Producto a Incluir</label>
                            <select name="id_producto" class="form-select select2" id="sel_prod" required style="width: 100%;">
                                <option value="">Buscar...</option>
                                <?php foreach($productos as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo $p['descripcion']; ?> (Stock: <?php echo $p['stock_actual']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <label class="fw-bold small text-muted">CANTIDAD</label>
                        <div class="qty-wrapper">
                            <button type="button" class="btn-qty btn-minus" onclick="cambiarCant(-1)">-</button>
                            <input type="number" name="cantidad" id="qty_input" class="input-qty" value="1" readonly>
                            <button type="button" class="btn-qty btn-plus" onclick="cambiarCant(1)">+</button>
                        </div>

                        <button class="btn btn-success w-100 fw-bold py-3 mt-2">AGREGAR AL PACK</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="det_titulo">Detalle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="list-group list-group-flush" id="det_lista"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExplicacion" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold" id="expl_titulo"><i class="bi bi-info-circle-fill"></i> ¿Cómo se calcula?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="alert alert-light m-0 border-bottom text-center small text-muted">
                        <span id="expl_subtitulo"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Valor Unit.</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="expl_tabla">
                                </tbody>
                            <tfoot class="table-group-divider">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                    <td class="text-end fw-bold fs-6" id="expl_total"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // BASE DE DATOS LOCAL JS
        const DB = <?php echo $json_db; ?>;

        // INICIALIZAR
        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#modalReceta'),
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Escribe para buscar...',
                language: { noResults: () => "No encontrado" }
            });
        });

        // SWEET ALERT
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('msg')){
            Swal.fire({
                icon: 'success',
                title: 'Operación Exitosa',
                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // --- FUNCIONES DE BOTONES ---

        function cambiarCant(val) {
            let el = document.getElementById('qty_input');
            let actual = parseInt(el.value);
            if(actual + val >= 1) el.value = actual + val;
        }

        function editarPack(id, nombre, precio) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_precio').value = precio;
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function armarReceta(id, nombre) {
            document.getElementById('receta_id').value = id;
            document.getElementById('receta_titulo').innerText = nombre;
            document.getElementById('qty_input').value = 1;
            $('#sel_prod').val(null).trigger('change');
            new bootstrap.Modal(document.getElementById('modalReceta')).show();
        }

        function verDetalle(id, nombre) {
            document.getElementById('det_titulo').innerText = nombre;
            let data = DB[id];
            let lista = document.getElementById('det_lista');
            lista.innerHTML = '';
            
            if(data.items.length === 0) {
                lista.innerHTML = '<li class="list-group-item text-center text-muted py-4">Sin productos.<br>Usa el botón verde para agregar.</li>';
            } else {
                data.items.forEach(i => {
                    let li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center py-3';
                    li.innerHTML = `
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-secondary rounded-pill px-3 py-2 fs-6">${i.cantidad}</span>
                            <div>
                                <div class="fw-bold text-dark">${i.descripcion}</div>
                            </div>
                        </div>
                        <a href="combos.php?borrar_item=${i.id}" class="btn btn-outline-danger btn-sm border-0 p-0 fs-5"><i class="bi bi-x-lg"></i></a>
                    `;
                    lista.appendChild(li);
                });
            }
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        }

        // --- FUNCIONES DE EXPLICACIÓN (AYUDA) ---
        
        function explicarCosto(id, nombre) {
            let data = DB[id];
            document.getElementById('expl_titulo').innerHTML = '<i class="bi bi-graph-down"></i> Detalle Costos: ' + nombre;
            document.getElementById('expl_subtitulo').innerText = 'Costo que pagas al proveedor por cada ingrediente';
            
            let tbody = document.getElementById('expl_tabla');
            tbody.innerHTML = '';
            
            if(data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Sin ingredientes</td></tr>';
            } else {
                data.items.forEach(i => {
                    let sub = i.precio_costo * i.cantidad;
                    tbody.innerHTML += `
                        <tr>
                            <td>${i.descripcion}</td>
                            <td class="text-end">$${new Intl.NumberFormat('es-AR').format(i.precio_costo)}</td>
                            <td class="text-center">${i.cantidad}</td>
                            <td class="text-end fw-bold">$${new Intl.NumberFormat('es-AR').format(sub)}</td>
                        </tr>
                    `;
                });
            }
            document.getElementById('expl_total').innerText = '$' + new Intl.NumberFormat('es-AR').format(data.costo);
            new bootstrap.Modal(document.getElementById('modalExplicacion')).show();
        }

        function explicarRegular(id, nombre) {
            let data = DB[id];
            document.getElementById('expl_titulo').innerHTML = '<i class="bi bi-shop"></i> Suma Precios Unitarios';
            document.getElementById('expl_subtitulo').innerText = 'Lo que pagaría el cliente si comprara todo suelto';
            
            let tbody = document.getElementById('expl_tabla');
            tbody.innerHTML = '';
            
            if(data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Sin ingredientes</td></tr>';
            } else {
                data.items.forEach(i => {
                    let sub = i.precio_venta * i.cantidad;
                    tbody.innerHTML += `
                        <tr>
                            <td>${i.descripcion}</td>
                            <td class="text-end">$${new Intl.NumberFormat('es-AR').format(i.precio_venta)}</td>
                            <td class="text-center">${i.cantidad}</td>
                            <td class="text-end fw-bold">$${new Intl.NumberFormat('es-AR').format(sub)}</td>
                        </tr>
                    `;
                });
            }
            document.getElementById('expl_total').innerText = '$' + new Intl.NumberFormat('es-AR').format(data.precio_regular);
            new bootstrap.Modal(document.getElementById('modalExplicacion')).show();
        }

        function borrarPack(id) {
            Swal.fire({
                title: '¿Eliminar Pack?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'combos.php?eliminar_id=' + id;
                }
            })
        }
    </script>
</body>
</html>
