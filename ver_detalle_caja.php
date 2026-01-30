<?php
// acciones/ver_detalle_caja.php - VERSIÓN CORREGIDA Y ROBUSTA
session_start();

// 1. INTENTO DE CONEXIÓN (Busca el archivo db.php en varios lugares para no fallar)
$rutas_posibles = [
    '../includes/db.php', 
    '../db.php', 
    '../../includes/db.php'
];

$conectado = false;
foreach ($rutas_posibles as $ruta) {
    if (file_exists($ruta)) {
        require_once $ruta;
        $conectado = true;
        break;
    }
}

if (!$conectado) {
    die("<div class='alert alert-danger'>Error Crítico: No se encuentra el archivo de conexión (db.php).</div>");
}

if (!isset($_SESSION['usuario_id'])) exit("<div class='alert alert-danger'>Sesión expirada.</div>");

$id = $_GET['id'] ?? 0;

try {
    // 2. BUSCAR LA CAJA (Usando nombres de columnas correctos según tu SQL)
    // Nota: Tu base de datos usa 'monto_final_declarado', pero a veces lo llamamos 'monto_final'.
    // Esta consulta arregla eso usando un alias.
    $sql = "SELECT c.id, c.monto_inicial, c.total_ventas, c.diferencia, c.fecha_cierre,
                   c.monto_final_declarado as monto_final,
                   u.usuario, u.nombre_completo 
            FROM cajas_sesion c 
            JOIN usuarios u ON c.id_usuario = u.id 
            WHERE c.id = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$caja) {
        exit("<div class='alert alert-warning'>No se encontró la información de la caja #$id.</div>");
    }

    // 3. CALCULAR GASTOS
    $stmtGastos = $conexion->prepare("SELECT SUM(monto) FROM gastos WHERE id_caja_sesion = ?");
    $stmtGastos->execute([$id]);
    $total_gastos = $stmtGastos->fetchColumn() ?? 0;

    // 4. CÁLCULO DE AUDITORÍA
    // Esperado = Inicio + Ventas - Gastos
    $esperado = $caja['monto_inicial'] + $caja['total_ventas'] - $total_gastos;
    
    // Si el monto final es NULL (caja mal cerrada), asumimos 0 para que no rompa visualmente
    $declarado = $caja['monto_final'] ?? 0;
    
    // La diferencia ya viene calculada de la base, pero podemos recalcularla visualmente
    $diferencia = $caja['diferencia'];

    // 5. DETALLE DE GASTOS
    $stmtLista = $conexion->prepare("SELECT descripcion, monto, categoria FROM gastos WHERE id_caja_sesion = ?");
    $stmtLista->execute([$id]);
    $lista_gastos = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    exit("<div class='alert alert-danger'>Error de Base de Datos: " . $e->getMessage() . "</div>");
}
?>

<div class="row">
    <div class="col-md-6 border-end">
        <h6 class="text-uppercase text-muted fw-bold mb-3">Balance Matemático</h6>
        
        <ul class="list-group list-group-flush mb-3 small">
            <li class="list-group-item d-flex justify-content-between px-0">
                <span>(+) Caja Inicial:</span>
                <span class="fw-bold text-primary">$ <?php echo number_format($caja['monto_inicial'], 2); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
                <span>(+) Ventas Efectivo:</span>
                <span class="fw-bold text-success">$ <?php echo number_format($caja['total_ventas'], 2); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
                <span>(-) Gastos/Retiros:</span>
                <span class="fw-bold text-danger">$ <?php echo number_format($total_gastos, 2); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between bg-light px-2 rounded mt-1">
                <span class="fw-bold text-dark">DEBERÍA HABER:</span>
                <span class="fw-bold text-dark">$ <?php echo number_format($esperado, 2); ?></span>
            </li>
        </ul>

        <div class="text-center p-2 border rounded bg-white">
            <small class="text-muted d-block">El Cajero declaró:</small>
            <span class="fs-4 fw-bold text-dark">$ <?php echo number_format($declarado, 2); ?></span>
        </div>
    </div>

    <div class="col-md-6">
        <h6 class="text-uppercase text-muted fw-bold mb-3">Resultado Auditoría</h6>

        <?php if(abs($diferencia) < 1): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill fs-1"></i><br>
                <strong class="d-block mt-2">PERFECTO</strong>
                <small>Sin diferencias</small>
            </div>
        <?php elseif($diferencia < 0): ?>
            <div class="alert alert-danger text-center">
                <i class="bi bi-exclamation-triangle-fill fs-1"></i><br>
                <strong class="d-block mt-2">FALTANTE</strong>
                <h3 class="fw-bold mb-0">$ <?php echo number_format($diferencia, 2); ?></h3>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-arrow-up-circle-fill fs-1"></i><br>
                <strong class="d-block mt-2">SOBRANTE</strong>
                <h3 class="fw-bold mb-0 text-success">+$ <?php echo number_format($diferencia, 2); ?></h3>
            </div>
        <?php endif; ?>

        <?php if(count($lista_gastos) > 0): ?>
            <div class="mt-3">
                <strong class="small text-danger"><i class="bi bi-list"></i> Gastos del Turno:</strong>
                <div class="border rounded p-2 bg-white mt-1" style="max-height: 100px; overflow-y: auto;">
                    <?php foreach($lista_gastos as $g): ?>
                        <div class="d-flex justify-content-between small border-bottom pb-1 mb-1">
                            <span><?php echo substr($g['descripcion'], 0, 15); ?></span>
                            <span class="text-danger fw-bold">$<?php echo number_format($g['monto'], 0); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
