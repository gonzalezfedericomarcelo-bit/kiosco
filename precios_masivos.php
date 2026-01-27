<?php
// precios_masivos.php - VERSIÓN FINAL: BUSCADOR INTELIGENTE EN LISTA + GESTIÓN DE ERRORES
session_start();
require_once 'includes/db.php';

// SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$proveedores = $conexion->query("SELECT * FROM proveedores ORDER BY empresa ASC")->fetchAll(); // Ordenado alfabéticamente
$mensaje = '';

// VARIABLES DE FILTRO
$tipo_filtro = $_GET['tipo'] ?? 'proveedor';
$id_filtro = $_GET['id'] ?? '';
$productos_filtrados = [];

// 1. CARGAR PRODUCTOS AUTOMÁTICAMENTE
if ($id_filtro) {
    $where = ($tipo_filtro == 'proveedor') ? "id_proveedor = ?" : "id_categoria = ?";
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE $where AND activo = 1 ORDER BY descripcion ASC");
    $stmt->execute([$id_filtro]);
    $productos_filtrados = $stmt->fetchAll();
}

// 2. PROCESAR EL AUMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_aumento'])) {
    $ids = $_POST['productos_seleccionados'] ?? [];
    $accion = $_POST['accion']; 
    $porcentaje = floatval($_POST['porcentaje']);
    $tipo_filtro = $_POST['tipo_hidden'];
    $id_filtro = $_POST['id_hidden'];

    if(count($ids) > 0 && $porcentaje > 0) {
        try {
            $conexion->beginTransaction();
            $ids_str = implode(',', array_map('intval', $ids));
            
            // AUDITORÍA
            $detalles = "Aumento Masivo del $porcentaje% en $accion a " . count($ids) . " productos.";
            $stmtAud = $conexion->prepare("INSERT INTO auditoria (id_usuario, accion, detalles, fecha) VALUES (?, 'INFLACION', ?, NOW())");
            $stmtAud->execute([$_SESSION['usuario_id'], $detalles]);

            if ($accion == 'costo') {
                $factor = 1 + ($porcentaje / 100);
                $sql = "UPDATE productos SET precio_costo = precio_costo * $factor, precio_venta = precio_venta * $factor WHERE id IN ($ids_str)";
            } else {
                $factor = 1 + ($porcentaje / 100);
                $sql = "UPDATE productos SET precio_venta = precio_venta * $factor WHERE id IN ($ids_str)";
            }
            
            $conexion->exec($sql);
            $conexion->commit();
            $mensaje = "✅ ¡Listo! Se actualizaron " . count($ids) . " productos.";
            
            // Recargar lista actualizada
            $stmt = $conexion->prepare("SELECT * FROM productos WHERE $where AND activo = 1 ORDER BY descripcion ASC");
            $stmt->execute([$id_filtro]);
            $productos_filtrados = $stmt->fetchAll();

        } catch (Exception $e) {
            $conexion->rollBack();
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    } else {
        $mensaje = "⚠️ Error: Verifica que haya productos seleccionados y el porcentaje sea mayor a 0.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inflación y Precios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-danger"><i class="bi bi-graph-up-arrow"></i> Actualización Masiva de Precios</h4>
            <div class="d-flex gap-2">
                <a href="proveedores.php" class="btn btn-outline-dark btn-sm" target="_blank"><i class="bi bi-truck"></i> Gestionar Proveedores</a>
                <a href="productos.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
        </div>

        <?php if($mensaje): ?>
            <div class="alert alert-info fw-bold text-center mb-4 shadow-sm"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="card-header bg-danger text-white fw-bold">
                ⚠️ Herramienta de Ajuste por Inflación
            </div>
            <div class="card-body p-4">
                
                <div class="row g-3 mb-4 bg-light p-3 rounded border align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">1. Filtrar por:</label>
                        <select id="select_tipo" class="form-select form-select-lg" onchange="actualizarVista()">
                            <option value="proveedor" <?php echo $tipo_filtro == 'proveedor' ? 'selected' : ''; ?>>Proveedor</option>
                            <option value="categoria" <?php echo $tipo_filtro == 'categoria' ? 'selected' : ''; ?>>Categoría</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">2. Seleccionar Grupo:</label>
                        
                        <select id="combo_proveedor" class="form-select form-select-lg" 
                                style="display: <?php echo $tipo_filtro == 'proveedor' ? 'block' : 'none'; ?>"
                                onchange="cargarProductos('proveedor', this.value)">
                            <option value="">-- Elige un Proveedor --</option>
                            <?php foreach($proveedores as $p): ?>
                                <option value="<?php echo $p->id; ?>" <?php echo $id_filtro == $p->id ? 'selected' : ''; ?>>
                                    <?php echo $p->empresa; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="combo_categoria" class="form-select form-select-lg"
                                style="display: <?php echo $tipo_filtro == 'categoria' ? 'block' : 'none'; ?>"
                                onchange="cargarProductos('categoria', this.value)">
                            <option value="">-- Elige una Categoría --</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?php echo $c->id; ?>" <?php echo $id_filtro == $c->id ? 'selected' : ''; ?>>
                                    <?php echo $c->nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if($id_filtro): ?>
                <form method="POST">
                    <input type="hidden" name="confirmar_aumento" value="1">
                    <input type="hidden" name="tipo_hidden" value="<?php echo $tipo_filtro; ?>">
                    <input type="hidden" name="id_hidden" value="<?php echo $id_filtro; ?>">

                    <div class="row">
                        <div class="col-lg-5 mb-4 mb-lg-0">
                            <div class="card h-100 border-secondary">
                                <div class="card-header bg-secondary text-white p-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text border-0 bg-transparent text-white"><i class="bi bi-search"></i></span>
                                        <input type="text" id="buscadorTabla" class="form-control form-control-sm rounded" placeholder="Buscar producto en la lista..." onkeyup="filtrarTabla()">
                                    </div>
                                </div>
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-1">
                                    <small class="fw-bold text-muted">Afectados (<span id="contadorVisible"><?php echo count($productos_filtrados); ?></span>)</small>
                                    <div class="form-check form-switch min-h-0 m-0">
                                        <input class="form-check-input" type="checkbox" id="checkAll" checked onchange="toggleAll(this)">
                                        <label class="form-check-label small" for="checkAll">Todos</label>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <?php if(empty($productos_filtrados)): ?>
                                            <div class="p-3 text-center text-muted">No hay productos.</div>
                                        <?php else: ?>
                                            <table class="table table-sm table-striped mb-0 small" id="tablaProductos">
                                                <tbody>
                                                    <?php foreach($productos_filtrados as $p): ?>
                                                    <tr class="fila-producto">
                                                        <td class="text-center" width="30">
                                                            <input type="checkbox" name="productos_seleccionados[]" value="<?php echo $p->id; ?>" class="form-check-input item-check" checked>
                                                        </td>
                                                        <td>
                                                            <span class="nombre-prod"><?php echo $p->descripcion; ?></span>
                                                            <div class="text-muted" style="font-size:0.8em;"><?php echo $p->codigo_barras; ?></div>
                                                        </td>
                                                        <td class="text-end fw-bold text-primary">$<?php echo number_format($p->precio_venta, 0); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <h5 class="fw-bold mb-3 text-uppercase text-muted">Configuración del Aumento</h5>
                            
                            <div class="input-group mb-4">
                                <span class="input-group-text bg-danger text-white fw-bold">% PORCENTAJE</span>
                                <input type="number" name="porcentaje" class="form-control form-control-lg fw-bold" placeholder="Ej: 15" step="0.01" required autofocus>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100 border-primary shadow-sm position-relative" style="cursor: pointer;" onclick="document.getElementById('a_costo').checked=true">
                                        <div class="card-body text-center p-4">
                                            <input type="radio" class="btn-check" name="accion" id="a_costo" value="costo" checked>
                                            <i class="bi bi-arrow-up-circle-fill text-primary fs-2 mb-2"></i>
                                            <h6 class="fw-bold text-primary">Subir COSTO + VENTA</h6>
                                            <small class="text-muted lh-1 d-block">Mantiene tu ganancia. (Recomendado)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-success shadow-sm position-relative" style="cursor: pointer;" onclick="document.getElementById('a_venta').checked=true">
                                        <div class="card-body text-center p-4">
                                            <input type="radio" class="btn-check" name="accion" id="a_venta" value="venta">
                                            <i class="bi bi-cash-coin text-success fs-2 mb-2"></i>
                                            <h6 class="fw-bold text-success">Solo PRECIO VENTA</h6>
                                            <small class="text-muted lh-1 d-block">Aumenta tu ganancia pura.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg py-3 fw-bold shadow">
                                    <i class="bi bi-rocket-takeoff"></i> CONFIRMAR CAMBIOS
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-cursor-fill display-1 opacity-25"></i>
                        <h4 class="mt-3 fw-light">Selecciona una opción arriba para empezar</h4>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function actualizarVista() {
            const tipo = document.getElementById('select_tipo').value;
            window.location.href = "precios_masivos.php?tipo=" + tipo;
        }

        function cargarProductos(tipo, id) {
            if(id) window.location.href = "precios_masivos.php?tipo=" + tipo + "&id=" + id;
        }

        function toggleAll(source) {
            // Solo marca los que están visibles en la búsqueda
            const filas = document.querySelectorAll('.fila-producto');
            filas.forEach(fila => {
                if (fila.style.display !== 'none') {
                    const checkbox = fila.querySelector('.item-check');
                    if (checkbox) checkbox.checked = source.checked;
                }
            });
        }

        // BUSCADOR EN TIEMPO REAL (FILTRO JS)
        function filtrarTabla() {
            const busqueda = document.getElementById('buscadorTabla').value.toLowerCase();
            const filas = document.querySelectorAll('.fila-producto');
            let visibles = 0;

            filas.forEach(fila => {
                const nombre = fila.querySelector('.nombre-prod').textContent.toLowerCase();
                if (nombre.includes(busqueda)) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });
            document.getElementById('contadorVisible').textContent = visibles;
        }
    </script>
</body>
</html>