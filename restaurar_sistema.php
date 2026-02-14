<?php
// restaurar_sistema.php - IMPORTADOR DE BACKUPS
session_start();
require_once 'includes/db.php';

// SEGURIDAD EXTREMA: SOLO ADMIN
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 1) {
    header("Location: dashboard.php"); exit;
}

$mensaje = "";
$tipo_mensaje = "";

// PROCESAR RESTAURACIÓN
if (isset($_POST['restaurar_archivo'])) {
    $archivo = 'backups/' . basename($_POST['restaurar_archivo']);
    
    if (file_exists($archivo)) {
        // Aumentar tiempo de ejecución y memoria para archivos grandes
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        try {
            $conexion->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Leer archivo completo
            $sql = file_get_contents($archivo);
            
            // Ejecutar queries (Separando por punto y coma)
            // Nota: Este es un parseador simple. Para SQLs muy complejos se recomienda línea por línea.
            $queries = explode(";\n", $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $conexion->query($query);
                }
            }

            $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
            $mensaje = "¡Sistema Restaurado Exitosamente! La base de datos volvió al estado del archivo.";
            $tipo_mensaje = "success";

        } catch (Exception $e) {
            $mensaje = "Error al restaurar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "El archivo no existe.";
        $tipo_mensaje = "danger";
    }
}

// ELIMINAR BACKUP
if (isset($_POST['eliminar_archivo'])) {
    $archivo = 'backups/' . basename($_POST['eliminar_archivo']);
    if (file_exists($archivo)) {
        unlink($archivo);
        $mensaje = "Backup eliminado.";
        $tipo_mensaje = "warning";
    }
}

// LISTAR BACKUPS
$backups = [];
if (is_dir('backups')) {
    $archivos = scandir('backups', SCANDIR_SORT_DESCENDING);
    foreach ($archivos as $arch) {
        if (strpos($arch, '.sql') !== false) {
            $backups[] = [
                'nombre' => $arch,
                'fecha' => date("d/m/Y H:i:s", filemtime('backups/' . $arch)),
                'peso' => round(filesize('backups/' . $arch) / 1024, 2) . ' KB'
            ];
        }
    }
}
?>

<?php include 'includes/layout_header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary"><i class="bi bi-clock-history"></i> Máquina del Tiempo</h2>
            <p class="text-muted mb-0">Restaura el sistema a un punto anterior.</p>
        </div>
        <a href="reset_sistema.php" class="btn btn-outline-danger fw-bold rounded-pill">
            <i class="bi bi-trash3"></i> Ir a Resetear
        </a>
    </div>

    <?php if($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> fw-bold text-center rounded-4 mb-4">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-dark text-white fw-bold py-3 px-4">
            Copias de Seguridad Disponibles
        </div>
        <div class="card-body p-0">
            <?php if(empty($backups)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                    No hay backups creados todavía.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Archivo</th>
                                <th>Fecha Creación</th>
                                <th>Peso</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($backups as $b): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">
                                    <i class="bi bi-database-fill me-2"></i> <?php echo $b['nombre']; ?>
                                </td>
                                <td><?php echo $b['fecha']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $b['peso']; ?></span></td>
                                <td class="text-end pe-4">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿RESTAURAR ESTE ESTADO?\nSe perderá todo lo actual y volverá a como estaba en ese momento.');">
                                        <input type=\"hidden\" name="restaurar_archivo" value="<?php echo $b['nombre']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-bold px-3">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline ms-1" onsubmit="return confirm('¿Eliminar este archivo de respaldo?');">
                                        <input type=\"hidden\" name="eliminar_archivo" value="<?php echo $b['nombre']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Eliminar archivo">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>