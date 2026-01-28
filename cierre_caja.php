<?php
// cierre_caja.php - CIERRE CIEGO (Actualizado con todos los billetes AR)
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id_sesion = 1; // ID fijo temporal (como acordamos antes)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total_fisico = $_POST['total_declarado'];
    
    // Calcular ventas sistema (solo Efectivo)
    $sqlVentas = "SELECT SUM(total) as total_ventas FROM ventas WHERE id_caja_sesion = ? AND metodo_pago = 'Efectivo' AND estado = 'completada'";
    $stmt = $conexion->prepare($sqlVentas);
    $stmt->execute([$id_sesion]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $ventas_sistema = $res['total_ventas'] ?? 0;
    
    // Caja inicial
    $stmtCaja = $conexion->prepare("SELECT monto_inicial FROM cajas_sesion WHERE id = ?");
    $stmtCaja->execute([$id_sesion]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    $inicio = $caja['monto_inicial'] ?? 0;
    
    $total_esperado = $inicio + $ventas_sistema;
    $diferencia = $total_fisico - $total_esperado;
    
    // Guardar
    $sqlCierre = "UPDATE cajas_sesion SET fecha_cierre = NOW(), monto_final_declarado = ?, diferencia = ?, estado = 'cerrada' WHERE id = ?";
    $stmtUpdate = $conexion->prepare($sqlCierre);
    $stmtUpdate->execute([$total_fisico, $diferencia, $id_sesion]);
    
    header("Location: dashboard.php?msg=caja_cerrada&dif=" . $diferencia);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .billete-input { font-weight: bold; font-size: 1.1rem; }
        .total-display { background: #212529; color: #0dfd05; font-family: monospace; font-size: 2rem; }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/menu.php'; ?>
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white fw-bold text-center py-3"><i class="bi bi-safe-fill fs-4"></i><br>ARQUEO DE CAJA</div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning text-center small mb-3">CIERRE CIEGO: Cuenta los billetes físicos.</div>
                        <form method="POST">
                            <h6 class="border-bottom pb-2 mb-3 text-muted fw-bold">BILLETES ARGENTINA</h6>
                            
                            <?php 
                            $billetes = [20000, 10000, 2000, 1000, 500, 200, 100, 50, 20, 10];
                            foreach($billetes as $b): ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-4 text-end fw-bold">$ <?php echo number_format($b,0,',','.'); ?></div>
                                <div class="col-4"><input type="number" class="form-control billete-input" data-valor="<?php echo $b; ?>" placeholder="Cant."></div>
                                <div class="col-4 text-muted small total-fila">$ 0</div>
                            </div>
                            <?php endforeach; ?>

                             <div class="row g-2 align-items-center mb-3">
                                <div class="col-4 text-end fw-bold">Monedas</div>
                                <div class="col-4"><input type="number" class="form-control billete-input" data-valor="1" placeholder="$ Total"></div>
                                <div class="col-4 text-muted small total-fila">$ 0</div>
                            </div>

                            <div class="card bg-dark text-white text-center p-3 mb-4">
                                <small class="text-uppercase text-muted">Total Declarado</small>
                                <div class="total-display" id="totalDisplay">$ 0</div>
                                <input type="hidden" name="total_declarado" id="inputTotalDeclarado" value="0">
                            </div>
                            <button type="submit" class="btn btn-danger w-100 py-3 fw-bold shadow" onclick="return confirm('¿Cerrar caja?')">CONFIRMAR CIERRE</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const inputs = document.querySelectorAll('.billete-input');
        const display = document.getElementById('totalDisplay');
        const inputHidden = document.getElementById('inputTotalDeclarado');
        inputs.forEach(input => input.addEventListener('input', () => {
            let total = 0;
            inputs.forEach(i => {
                let val = (parseInt(i.value) || 0) * parseInt(i.dataset.valor);
                i.parentElement.nextElementSibling.innerText = '$ ' + val.toLocaleString();
                total += val;
            });
            display.innerText = '$ ' + total.toLocaleString();
            inputHidden.value = total;
        }));
    </script>
</body>
</html>