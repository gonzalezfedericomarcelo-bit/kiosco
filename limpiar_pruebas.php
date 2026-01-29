<?php
// limpiar_pruebas.php - PANEL DE LIMPIEZA SELECTIVA
session_start();
require_once 'includes/db.php';

// SEGURIDAD: Solo Admin (1) o Dueño (2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] > 2) {
    header("Location: dashboard.php"); exit;
}

$mensaje = "";
$tipo_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_borrado'])) {
    if ($_POST['password_borrado'] === 'admin123') { // CAMBIAR POR TU CONTRASEÑA
        try {
            // DESACTIVAR CONTROLES DE CLAVES FORÁNEAS TEMPORALMENTE
            // Esto permite borrar categorías aunque tengan productos, o clientes aunque tengan ventas, sin error.
            $conexion->query("SET FOREIGN_KEY_CHECKS = 0");

            // 1. VENTAS Y CAJA (El corazón del movimiento)
            if (isset($_POST['chk_ventas'])) {
                $conexion->query("TRUNCATE TABLE detalle_ventas");
                $conexion->query("TRUNCATE TABLE pagos_ventas");
                $conexion->query("TRUNCATE TABLE ventas");
                $conexion->query("DELETE FROM movimientos_cc WHERE id_venta IS NOT NULL"); // Borrar movimientos generados por ventas
            }

            if (isset($_POST['chk_caja'])) {
                $conexion->query("TRUNCATE TABLE cajas_sesion");
                // Si borramos cajas, los gastos vinculados quedan huérfanos o se borran si seleccionamos gastos
            }

            if (isset($_POST['chk_gastos'])) {
                $conexion->query("TRUNCATE TABLE gastos");
            }

            // 2. STOCK Y PRODUCTOS
            if (isset($_POST['chk_productos'])) {
                $conexion->query("TRUNCATE TABLE productos_combo");
                $conexion->query("TRUNCATE TABLE productos");
                // No borramos categorías ni proveedores acá, tienen su propio check
            }

            if (isset($_POST['chk_categorias'])) {
                $conexion->query("TRUNCATE TABLE categorias");
                // Si quedaron productos, quedarán sin categoría (NULL) o hidden
            }

            if (isset($_POST['chk_mermas'])) {
                $conexion->query("TRUNCATE TABLE mermas");
            }

            // 3. PERSONAS (CON PROTECCIÓN)
            if (isset($_POST['chk_clientes'])) {
                // Borramos todos MENOS el ID 1 (Consumidor Final)
                $conexion->query("DELETE FROM clientes WHERE id > 1");
                $conexion->query("ALTER TABLE clientes AUTO_INCREMENT = 2");
                // Limpiamos cuentas corrientes huérfanas
                $conexion->query("DELETE FROM movimientos_cc WHERE id_cliente > 1");
            }

            if (isset($_POST['chk_proveedores'])) {
                $conexion->query("TRUNCATE TABLE movimientos_proveedores");
                $conexion->query("TRUNCATE TABLE proveedores");
            }

            if (isset($_POST['chk_usuarios'])) {
                // Borramos todos MENOS el ID 1 (SuperAdmin) y el usuario actual
                $mi_id = $_SESSION['usuario_id'];
                $conexion->query("DELETE FROM usuarios WHERE id > 1 AND id != $mi_id");
                // No reseteamos auto_increment para evitar conflictos con IDs viejos en logs
            }

            // 4. EXTRAS
            if (isset($_POST['chk_devoluciones'])) {
                $conexion->query("TRUNCATE TABLE devoluciones");
            }

            if (isset($_POST['chk_encuestas'])) {
                $conexion->query("TRUNCATE TABLE encuestas");
            }

            if (isset($_POST['chk_premios'])) {
                $conexion->query("TRUNCATE TABLE premios"); // Borra el catálogo de premios
            }

            if (isset($_POST['chk_cupones'])) {
                $conexion->query("TRUNCATE TABLE cupones");
            }

            if (isset($_POST['chk_activos'])) {
                $conexion->query("TRUNCATE TABLE bienes_uso");
            }

            if (isset($_POST['chk_auditoria'])) {
                $conexion->query("TRUNCATE TABLE auditoria");
            }

            // REACTIVAR CONTROLES
            $conexion->query("SET FOREIGN_KEY_CHECKS = 1");

            $mensaje = "Limpieza ejecutada correctamente. Los datos seleccionados han sido eliminados.";
            $tipo_msg = "success";

        } catch (Exception $e) {
            $conexion->query("SET FOREIGN_KEY_CHECKS = 1"); // Asegurar reactivación
            $mensaje = "Error en la base de datos: " . $e->getMessage();
            $tipo_msg = "danger";
        }
    } else {
        $mensaje = "Contraseña incorrecta. No se realizaron cambios.";
        $tipo_msg = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Limpieza Selectiva de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    
    <?php include 'includes/menu.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_msg; ?> alert-dismissible fade show mb-4" role="alert">
                        <?php if($tipo_msg=='success'): ?><i class="bi bi-check-circle-fill me-2"></i><?php else: ?><i class="bi bi-exclamation-triangle-fill me-2"></i><?php endif; ?>
                        <strong><?php echo $mensaje; ?></strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow border-danger">
                    <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-trash3-fill"></i> Panel de Limpieza de Datos</span>
                        <span class="badge bg-white text-danger">ZONA PELIGROSA</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 mb-4">
                            <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle"></i> ¡Atención!</h5>
                            <p class="mb-0 small">Esta herramienta elimina datos de forma <strong>permanente</strong>. Úsala para limpiar datos de prueba antes de poner el sistema en producción. <br><strong>Los usuarios "Dueño" y el cliente "Consumidor Final" están protegidos y NO se borrarán.</strong></p>
                        </div>

                        <form method="POST" id="formLimpieza">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100 border-secondary">
                                        <div class="card-header bg-secondary text-white fw-bold small">OPERACIONES DIARIAS</div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_ventas" id="chk_ventas" checked>
                                                <label class="form-check-label" for="chk_ventas">Ventas y Detalles (Historial)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_caja" id="chk_caja" checked>
                                                <label class="form-check-label" for="chk_caja">Sesiones de Caja (Aperturas/Cierres)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_gastos" id="chk_gastos" checked>
                                                <label class="form-check-label" for="chk_gastos">Gastos y Retiros</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_devoluciones" id="chk_devoluciones" checked>
                                                <label class="form-check-label" for="chk_devoluciones">Devoluciones</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_mermas" id="chk_mermas">
                                                <label class="form-check-label" for="chk_mermas">Mermas y Roturas</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-primary text-white fw-bold small">CATÁLOGOS Y ENTIDADES</div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_productos" id="chk_productos">
                                                <label class="form-check-label" for="chk_productos">Productos y Stock</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_clientes" id="chk_clientes">
                                                <label class="form-check-label" for="chk_clientes">Clientes y Ctas. Corrientes</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_proveedores" id="chk_proveedores">
                                                <label class="form-check-label" for="chk_proveedores">Proveedores y Deudas</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input chk-item" type="checkbox" name="chk_usuarios" id="chk_usuarios">
                                                <label class="form-check-label fw-bold text-danger" for="chk_usuarios">Usuarios / Empleados (Extra)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card border-light bg-white shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_categorias" id="chk_categorias">
                                                        <label class="form-check-label small" for="chk_categorias">Categorías</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_premios" id="chk_premios">
                                                        <label class="form-check-label small" for="chk_premios">Premios</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_cupones" id="chk_cupones">
                                                        <label class="form-check-label small" for="chk_cupones">Cupones</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_activos" id="chk_activos">
                                                        <label class="form-check-label small" for="chk_activos">Activos (Bienes)</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_encuestas" id="chk_encuestas">
                                                        <label class="form-check-label small" for="chk_encuestas">Encuestas</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input chk-item" type="checkbox" name="chk_auditoria" id="chk_auditoria">
                                                        <label class="form-check-label small" for="chk_auditoria">Logs Auditoría</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-end border-top pt-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodo(true)">Marcar Todo</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="seleccionarTodo(false)">Desmarcar Todo</button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Contraseña de Seguridad</label>
                                    <div class="input-group">
                                        <input type="password" name="password_borrado" class="form-control" placeholder="Ingresa contraseña..." required>
                                        <button type="submit" name="confirmar_borrado" class="btn btn-danger fw-bold px-4" onclick="return confirm('¿ESTÁS 100% SEGURO? Esta acción no se puede deshacer.')">
                                            <i class="bi bi-trash3-fill"></i> EJECUTAR LIMPIEZA
                                        </button>
                                    </div>
                                    <div class="form-text text-end">Por defecto: admin123</div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function seleccionarTodo(estado) {
            const checkboxes = document.querySelectorAll('.chk-item');
            checkboxes.forEach(chk => chk.checked = estado);
        }
    </script>
</body>
</html>