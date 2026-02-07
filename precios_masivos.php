<?php
// precios_masivos.php - VERSIÓN FINAL: DISEÑO PREMIUM + MENÚ FUNCIONAL
session_start();
require_once 'includes/db.php';

// SEGURIDAD
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) { header("Location: dashboard.php"); exit; }

$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$proveedores = $conexion->query("SELECT * FROM proveedores ORDER BY empresa ASC")->fetchAll();
$mensaje = '';

$tipo_filtro = $_GET['tipo'] ?? 'proveedor';
$id_filtro = $_GET['id'] ?? '';
$productos_filtrados = [];

// 1. CARGAR PRODUCTOS
if ($id_filtro) {
    $where = ($tipo_filtro == 'proveedor') ? "id_proveedor = ?" : "id_categoria = ?";
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE $where AND activo = 1 ORDER BY descripcion ASC");
    $stmt->execute([$id_filtro]);
    $productos_filtrados = $stmt->fetchAll();
}

// 2. PROCESAR AUMENTO
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
            
            // Auditoría
            $detalles = "Aumento Masivo del $porcentaje% en $accion a " . count($ids) . " productos.";
            $conexion->prepare("INSERT INTO auditoria (id_usuario, accion, detalles, fecha) VALUES (?, 'INFLACION', ?, NOW())")
                     ->execute([$_SESSION['usuario_id'], $detalles]);

            $factor = 1 + ($porcentaje / 100);
            
            if ($accion == 'costo') {
                $sql = "UPDATE productos SET precio_costo = precio_costo * $factor, precio_venta = precio_venta * $factor WHERE id IN ($ids_str)";
            } else {
                $sql = "UPDATE productos SET precio_venta = precio_venta * $factor WHERE id IN ($ids_str)";
            }
            
            $conexion->exec($sql);
            $conexion->commit();
            $mensaje = "✅ Éxito: Se actualizaron " . count($ids) . " productos.";
            
            // Recargar lista
            $stmt = $conexion->prepare("SELECT * FROM productos WHERE $where AND activo = 1 ORDER BY descripcion ASC");
            $stmt->execute([$id_filtro]);
            $productos_filtrados = $stmt->fetchAll();

        } catch (Exception $e) { $conexion->rollBack(); $mensaje = "❌ Error: " . $e->getMessage(); }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        /* EL BANNER GRADIENTE QUE TE GUSTABA */
        .header-gradient {
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
            color: white; padding: 30px 0; border-radius: 0 0 30px 30px; margin-bottom: 30px; 
            box-shadow: 0 4px 15px rgba(255, 75, 43, 0.2);
        }
        
        .step-card { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .step-number { width: 30px; height: 30px; background: #FF4B2B; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>
    
    <?php 
    if(file_exists('menu.php')) include 'menu.php'; 
    elseif(file_exists('includes/menu.php')) include 'includes/menu.php';
    ?>

    <div class="header-gradient">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Ajuste de Precios</h2>
                <p class="opacity-75 mb-0">Herramienta de actualización masiva por inflación.</p>
            </div>
            <a href="productos.php" class="btn btn-dark bg-opacity-50 border-0 text-white shadow-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="container pb-5">
        <?php if($mensaje): ?>
            <div class="alert alert-light border-danger text-danger border shadow-sm fw-bold text-center mb-4 rounded-pill"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="card step-card mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-secondary"><span class="step-number">1</span> ¿Qué querés aumentar?</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <select id="select_tipo" class="form-select form-select-lg bg-light border-0 fw-bold" onchange="actualizarVista()">
                            <option value="proveedor" <?php echo $tipo_filtro == 'proveedor' ? 'selected' : ''; ?>>Por Proveedor</option>
                            <option value="categoria" <?php echo $tipo_filtro == 'categoria' ? 'selected' : ''; ?>>Por Categoría</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <select id="combo_proveedor" class="form-select form-select-lg border-danger" 
                                style="display: <?php echo $tipo_filtro == 'proveedor' ? 'block' : 'none'; ?>"
                                onchange="cargarProductos('proveedor', this.value)">
                            <option value="">-- Seleccionar Proveedor --</option>
                            <?php foreach($proveedores as $p): ?>
                                <option value="<?php echo $p->id; ?>" <?php echo $id_filtro == $p->id ? 'selected' : ''; ?>><?php echo $p->empresa; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="combo_categoria" class="form-select form-select-lg border-danger"
                                style="display: <?php echo $tipo_filtro == 'categoria' ? 'block' : 'none'; ?>"
                                onchange="cargarProductos('categoria', this.value)">
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?php echo $c->id; ?>" <?php echo $id_filtro == $c->id ? 'selected' : ''; ?>><?php echo $c->nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php if($id_filtro): ?>
        <form method="POST">
            <input type="hidden" name="confirmar_aumento" value="1">
            <input type="hidden" name="tipo_hidden" value="<?php echo $tipo_filtro; ?>">
            <input type="hidden" name="id_hidden" value="<?php echo $id_filtro; ?>">

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card step-card h-100">
                        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                            <span>Selección (<span id="contadorVisible"><?php echo count($productos_filtrados); ?></span>)</span>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="checkAll" checked onchange="toggleAll(this)">
                                <label class="form-check-label small" for="checkAll">Todos</label>
                            </div>
                        </div>
                        <div class="p-2 bg-light border-bottom">
                            <input type="text" id="buscadorTabla" class="form-control form-control-sm border-0" placeholder="🔍 Buscar en la lista..." onkeyup="filtrarTabla()">
                        </div>
                        <div class="card-body p-0" style="height: 400px; overflow-y: auto;">
                            <?php if(empty($productos_filtrados)): ?>
                                <div class="text-center p-5 text-muted">No hay productos en este grupo.</div>
                            <?php else: ?>
                                <table class="table table-hover mb-0 small">
                                    <tbody>
                                        <?php foreach($productos_filtrados as $p): ?>
                                        <tr class="fila-producto">
                                            <td class="text-center" width="40">
                                                <input type="checkbox" name="productos_seleccionados[]" value="<?php echo $p->id; ?>" class="form-check-input item-check" checked>
                                            </td>
                                            <td>
                                                <span class="nombre-prod fw-bold text-dark"><?php echo $p->descripcion; ?></span>
                                                <div class="text-muted" style="font-size:0.85em;"><?php echo $p->codigo_barras; ?></div>
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

                <div class="col-lg-6">
                    <div class="card step-card h-100 border-2 border-danger">
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <h5 class="fw-bold mb-4 text-secondary"><span class="step-number">2</span> Aplicar Porcentaje</h5>
                            
                            <label class="form-label fw-bold text-muted small text-uppercase">Porcentaje de Aumento</label>
                            <div class="input-group mb-4">
                                <span class="input-group-text bg-danger text-white border-danger"><i class="bi bi-percent"></i></span>
                                <input type="number" name="porcentaje" class="form-control form-control-lg fw-bold text-center fs-2 text-danger border-danger" placeholder="0" step="0.01" required>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="accion" id="a_costo" value="costo" checked>
                                    <label class="btn btn-outline-dark w-100 py-3 h-100 rounded-3 shadow-sm" for="a_costo">
                                        <i class="bi bi-arrow-up-circle-fill fs-3 d-block mb-1"></i>
                                        Costo + Venta<br>
                                        <small class="fw-normal opacity-75" style="font-size: 0.75rem;">Mantiene Margen</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="accion" id="a_venta" value="venta">
                                    <label class="btn btn-outline-success w-100 py-3 h-100 rounded-3 shadow-sm" for="a_venta">
                                        <i class="bi bi-cash-coin fs-3 d-block mb-1"></i>
                                        Solo Venta<br>
                                        <small class="fw-normal opacity-75" style="font-size: 0.75rem;">Sube Ganancia</small>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 btn-lg fw-bold shadow hover-zoom">
                                APLICAR CAMBIOS AHORA <i class="bi bi-check-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function actualizarVista() { window.location.href = "precios_masivos.php?tipo=" + document.getElementById('select_tipo').value; }
        function cargarProductos(tipo, id) { if(id) window.location.href = "precios_masivos.php?tipo=" + tipo + "&id=" + id; }
        
        function toggleAll(source) { 
            const visibles = document.querySelectorAll('.fila-producto:not([style*="display: none"]) .item-check');
            visibles.forEach(cb => cb.checked = source.checked);
        }
        
        function filtrarTabla() {
            const txt = document.getElementById('buscadorTabla').value.toLowerCase();
            let c = 0;
            document.querySelectorAll('.fila-producto').forEach(r => {
                const n = r.querySelector('.nombre-prod').textContent.toLowerCase();
                if(n.includes(txt)) { r.style.display = ''; c++; } 
                else { r.style.display = 'none'; }
            });
            document.getElementById('contadorVisible').innerText = c;
        }
    </script>
</body>
</html>