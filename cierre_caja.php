<?php

// cierre_caja.php - CON SWEETALERT Y CÁLCULO EXPLICADO
session_start();
require_once 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$id_sesion = 1; // ID fijo temporal
$fecha_actual = date('Y-m-d H:i:s');
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$usuario_id]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$caja) { header("Location: apertura_caja.php"); exit; } // Si no hay caja, mandar a abrir
$id_sesion = $caja['id']; // USAMOS EL ID REAL
// 1. OBTENER TOTALES DEL SISTEMA
// A. Caja Inicial
$stmt = $conexion->prepare("SELECT monto_inicial FROM cajas_sesion WHERE id = ?");
$stmt->execute([$id_sesion]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);
$monto_inicial = $caja['monto_inicial'] ?? 0;

// B. Ventas Puras en Efectivo
$sqlVentas = "SELECT SUM(total) FROM ventas WHERE id_caja_sesion = ? AND metodo_pago = 'Efectivo' AND estado = 'completada'";
$stmt = $conexion->prepare($sqlVentas);
$stmt->execute([$id_sesion]);
$ventas_efectivo_puro = $stmt->fetchColumn() ?? 0;

// C. Cobros de Deuda en Efectivo
$sqlDeudas = "SELECT SUM(m.monto) FROM movimientos_cc m 
              JOIN ventas v ON m.id_venta = v.id 
              WHERE v.id_caja_sesion = ? AND v.metodo_pago = 'Efectivo' AND m.tipo = 'haber'";
$stmt = $conexion->prepare($sqlDeudas);
$stmt->execute([$id_sesion]);
$cobros_deuda_efectivo = $stmt->fetchColumn() ?? 0;

// D. Parte en Efectivo de Pagos Mixtos
$sqlMixtos = "SELECT SUM(monto) FROM pagos_ventas pv 
              JOIN ventas v ON pv.id_venta = v.id 
              WHERE v.id_caja_sesion = ? AND pv.metodo_pago = 'Efectivo'";
$stmt = $conexion->prepare($sqlMixtos);
$stmt->execute([$id_sesion]);
$mixtos_efectivo = $stmt->fetchColumn() ?? 0;

// E. GASTOS Y RETIROS
$sqlGastos = "SELECT SUM(monto) FROM gastos WHERE id_caja_sesion = ?";
$stmt = $conexion->prepare($sqlGastos);
$stmt->execute([$id_sesion]);
$gastos_totales = $stmt->fetchColumn() ?? 0;

// TOTALES FINALES
$total_entradas = $ventas_efectivo_puro + $cobros_deuda_efectivo + $mixtos_efectivo;
$total_esperado = ($monto_inicial + $total_entradas) - $gastos_totales;

$mensaje = '';
$cierre_exitoso = false;

// PROCESAR CIERRE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total_fisico = $_POST['total_declarado']; 
    $diferencia = $total_fisico - $total_esperado;
    
    // NOTA: Usamos las columnas correctas según tu base de datos arreglada
    $sqlCierre = "UPDATE cajas_sesion SET 
                  monto_final = ?, 
                  total_ventas = ?, 
                  diferencia = ?, 
                  fecha_cierre = ?, 
                  estado = 'cerrada' 
                  WHERE id = ?";
    $stmt = $conexion->prepare($sqlCierre);
    $stmt->execute([$total_fisico, $total_entradas, $diferencia, $fecha_actual, $id_sesion]);
    
    $cierre_exitoso = true;
    
    $clase_dif = ($diferencia < 0) ? 'text-danger' : 'text-success';
    $txt_dif = ($diferencia < 0) ? 'FALTANTE' : 'SOBRANTE';
    if(abs($diferencia) < 1) { $clase_dif = 'text-primary'; $txt_dif = 'PERFECTO'; }
    
    $mensaje = "
    <div class='alert alert-light border shadow-sm text-center'>
        <h3 class='fw-bold'>¡Caja Cerrada!</h3>
        <hr>
        <div class='row'>
            <div class='col-6 text-end text-muted'>Caja Inicial + Ventas:</div>
            <div class='col-6 text-start fw-bold text-success'>$ " . number_format($monto_inicial + $total_entradas, 2) . "</div>
            
            <div class='col-6 text-end text-muted'>Gastos/Retiros:</div>
            <div class='col-6 text-start fw-bold text-danger'>- $ " . number_format($gastos_totales, 2) . "</div>

            <div class='col-12'><hr></div>

            <div class='col-6 text-end text-muted'>Sistema Esperaba:</div>
            <div class='col-6 text-start fw-bold fs-5'>$ " . number_format($total_esperado, 2) . "</div>
            
            <div class='col-6 text-end text-muted'>Vos contaste:</div>
            <div class='col-6 text-start fw-bold fs-5'>$ " . number_format($total_fisico, 2) . "</div>
            
            <div class='col-12 mt-3'>
                <h2 class='$clase_dif fw-bold'>$txt_dif: $ " . number_format($diferencia, 2) . "</h2>
            </div>
        </div>
        <div class='mt-4'>
            <a href='index.php' class='btn btn-primary'>Volver al Inicio</a>
            <a href='reportes.php' class='btn btn-outline-secondary'>Ver Reportes</a>
        </div>
    </div>";
}
?>
<?php require_once 'includes/layout_header.php'; ?>
<style>
        .billete-row { display: flex; align-items: center; margin-bottom: 8px; }
        .billete-label { width: 80px; font-weight: bold; }
        .billete-input { width: 100px; text-align: center; font-weight: bold; }
        .billete-total { margin-left: auto; color: #198754; font-weight: bold; }
        .total-display { background: #212529; color: #0dfd05; font-family: monospace; font-size: 2rem; padding: 10px; border-radius: 8px; text-align: center; }
        .card-header-custom { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }
    </style>

    <div class="pb-5">
        <?php if($cierre_exitoso): ?>
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php else: ?>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0">
                    <div class="card-header card-header-custom fw-bold text-center py-3">
                        <i class="bi bi-calculator"></i> ARQUEO DE CAJA (Calculadora de Billetes)
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info text-center small mb-4">
                            <i class="bi bi-info-circle"></i> Ingresá la cantidad de billetes. El sistema restará automáticamente los gastos registrados ($<?php echo number_format($gastos_totales,2); ?>).
                        </div>

                        <form method="POST" id="formCierre">
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="text-muted mb-3 text-center">Alta Denominación</h6>
                                    
                                    <div class="billete-row">
                                        <div class="billete-label">$ 20.000</div>
                                        <input type="number" class="form-control billete-input" data-valor="20000" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 10.000</div>
                                        <input type="number" class="form-control billete-input" data-valor="10000" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 2.000</div>
                                        <input type="number" class="form-control billete-input" data-valor="2000" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 1.000</div>
                                        <input type="number" class="form-control billete-input" data-valor="1000" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 500</div>
                                        <input type="number" class="form-control billete-input" data-valor="500" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3 text-center">Baja Denominación</h6>
                                    
                                    <div class="billete-row">
                                        <div class="billete-label">$ 200</div>
                                        <input type="number" class="form-control billete-input" data-valor="200" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 100</div>
                                        <input type="number" class="form-control billete-input" data-valor="100" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 50</div>
                                        <input type="number" class="form-control billete-input" data-valor="50" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 20</div>
                                        <input type="number" class="form-control billete-input" data-valor="20" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    <div class="billete-row">
                                        <div class="billete-label">$ 10</div>
                                        <input type="number" class="form-control billete-input" data-valor="10" placeholder="0">
                                        <div class="billete-total">$ 0</div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label class="small fw-bold text-muted">Monedas / Suelto ($)</label>
                                        <input type="number" step="0.01" class="form-control fw-bold text-center" id="inputMonedas" placeholder="Ej: 0.50">
                                        <div class="form-text text-center small" style="font-size: 0.75rem;">Para 50 centavos poné: 0.50</div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="text-center p-3 mb-4">
                                <small class="text-uppercase text-muted fw-bold">Total Efectivo en Caja</small>
                                <div class="total-display" id="totalDisplay">$ 0.00</div>
                                <input type="hidden" name="total_declarado" id="inputTotalDeclarado" value="0">
                            </div>

                            <button type="button" id="btn-cerrar-caja" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                                <i class="bi bi-lock-fill"></i> CERRAR CAJA DEFINITIVAMENTE
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        const inputs = document.querySelectorAll('.billete-input');
        const inputMonedas = document.getElementById('inputMonedas');
        const display = document.getElementById('totalDisplay');
        const inputHidden = document.getElementById('inputTotalDeclarado');

        function calcularTotal() {
            let total = 0;
            inputs.forEach(i => {
                let cant = parseInt(i.value) || 0;
                let valor = parseInt(i.dataset.valor);
                let subtotal = cant * valor;
                i.parentElement.querySelector('.billete-total').innerText = '$ ' + subtotal.toLocaleString('es-AR');
                total += subtotal;
            });
            let monedas = parseFloat(inputMonedas.value) || 0;
            total += monedas;
            display.innerText = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2});
            inputHidden.value = total;
        }

        inputs.forEach(input => input.addEventListener('input', calcularTotal));
        inputMonedas.addEventListener('input', calcularTotal);

        // SWEETALERT PARA CONFIRMAR
        $('#btn-cerrar-caja').click(function(e){
            e.preventDefault(); // Evita que el botón mande el formulario directo
            
            let total = $('#inputTotalDeclarado').val();
            
            Swal.fire({
                title: '¿Cerrar Caja Definitivamente?',
                text: "Estás declarando un total de $" + parseFloat(total).toLocaleString('es-AR') + ". Se generará el reporte y no podrás editarlo.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar caja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#formCierre').submit(); // Ahora sí mandamos el formulario
                }
            })
        });
    </script>
<?php require_once 'includes/layout_footer.php'; ?>