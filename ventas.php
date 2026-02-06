<?php
// ventas.php - CONTROL DE APERTURA
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';
require_once 'check_security.php';

// VERIFICAR CAJA ABIERTA
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conexion->prepare("SELECT id FROM cajas_sesion WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$usuario_id]);
$caja = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$caja) {
    // Si no hay caja abierta, lo mandamos a abrirla
    header("Location: apertura_caja.php"); exit;
}
$id_caja_actual = $caja['id']; // Guardamos el ID real de hoy

// ... (El resto del código de carga de cupones sigue igual, no lo borres) ...
// CARGA DE CUPONES
try {
    $sqlCupones = "SELECT * FROM cupones WHERE activo = 1 AND (fecha_limite IS NULL OR fecha_limite >= CURDATE())";
    $cupones_db = $conexion->query($sqlCupones)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $cupones_db = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caja - KioscoManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .tabla-ventas { height: 450px; overflow-y: auto; background: white; border: 1px solid #dee2e6; }
        @media (max-width: 992px) { .tabla-ventas { height: 300px; } }
        .total-box { background: #212529; color: #0dfd05; padding: 15px; border-radius: 8px; font-family: monospace; letter-spacing: 1px; }
        #lista-resultados, #lista-clientes-modal { max-height: 250px; overflow-y: auto; }
        .item-resultado { padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; transition: 0.2s; background: white; }
        .item-resultado:hover { background-color: #e9ecef; padding-left: 15px; }
        @keyframes parpadeo { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
        .btn-pausada-activa { animation: parpadeo 1.5s infinite; background-color: #ffc107 !important; color: #000 !important; border: 2px solid #e0a800 !important; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5"> 
        <div class="row g-4">
            
            <div class="col-lg-8 col-12 order-2 order-lg-1">
                <div class="card shadow border-0 mb-3" style="position: relative; z-index: 100;">
                    <div class="card-body position-relative">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscar-producto" class="form-control border-start-0" placeholder="Buscar producto o escanear..." autocomplete="off">
                        </div>
                        <div class="d-flex justify-content-between mt-1 px-1">
                            <div class="small text-muted">
                                <i class="bi bi-keyboard"></i> Atajos: <b>F2</b> Buscar | <b>F4</b> Clientes | <b>F7</b> Pausar | <b>F8</b> Recuperar | <b>F9</b> Cobrar
                            </div>
                        </div>
                        <div id="lista-resultados" class="list-group position-absolute w-100 shadow mt-1" style="z-index: 2000; display:none;"></div>
                    </div>
                </div>

                <div class="card shadow border-0 mb-3">
                    <div class="card-header bg-white py-2">
                        <div class="d-flex gap-2 overflow-auto pb-1" id="filtros-rapidos">
                            <button class="btn btn-sm btn-dark fw-bold rounded-pill text-nowrap" onclick="cargarRapidos('')">Todos</button>
                            <?php foreach($conexion->query("SELECT * FROM categorias WHERE activo=1") as $c): ?>
                                <button class="btn btn-sm btn-outline-secondary rounded-pill text-nowrap" onclick="cargarRapidos(<?php echo $c->id; ?>)">
                                    <?php echo $c->nombre; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-body bg-light p-2" style="max-height: 200px; overflow-y: auto;">
                        <div class="row g-2" id="grid-rapidos">
                            <div class="text-center w-100 text-muted small py-3">Selecciona una categoría arriba...</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0" style="position: relative; z-index: 1;">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cart3"></i> Carrito de Compras</span>
                        
                    </div>
                    <div class="card-body p-0">
                        <div class="tabla-ventas table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-3">Descripción</th>
                                        <th width="100">Precio</th>
                                        <th width="80">Cant.</th>
                                        <th width="100">Subtotal</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="carrito-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12 order-1 order-lg-2">
                <div class="card shadow border-0 h-100">
                    <div class="card-body d-flex flex-column p-4">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded bg-white">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Cliente</small>
                                <div id="lbl-nombre-cliente" class="fw-bold text-dark text-truncate" style="max-width: 150px;">Consumidor Final</div>
                                <input type="hidden" id="id-cliente" value="1">
                                <input type="hidden" id="val-deuda" value="0">
                                <input type="hidden" id="pago-deuda-calculado" value="0">
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="abrirModalClientes()"><i class="bi bi-pencil-fill"></i> Cambiar</button>
                        </div>
                        
                        <div id="info-deuda" class="d-none mb-3 text-center">
                            <div class="alert alert-danger py-1 mb-0 fw-bold">Deuda: <span id="lbl-deuda"></span></div>
                        </div>
                        
                        <div id="info-saldo" class="d-none mb-3 text-center">
                            <div class="alert alert-success py-1 mb-0 d-flex justify-content-between align-items-center px-2">
                                <span class="fw-bold small">Saldo a favor: <span id="lbl-saldo">$0.00</span></span>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="usar-saldo" onchange="calc()">
                                    <label class="form-check-label small fw-bold" for="usar-saldo">USAR</label>
                                </div>
                                <input type="hidden" id="val-saldo" value="0">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted mb-1">Cupón %</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                                    <input type="text" id="input-cupon" class="form-control text-uppercase fw-bold" placeholder="CÓDIGO" autocomplete="off">
                                </div>
                                <div id="msg-cupon" class="small fw-bold mt-1" style="font-size: 0.75rem; display:none;"></div>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted mb-1">Desc. Manual $</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text text-danger fw-bold">- $</span>
                                    <input type="number" id="input-desc-manual" class="form-control fw-bold text-danger" placeholder="0" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="total-box text-center mb-4">
                            <small class="text-uppercase text-secondary">Total a Pagar</small>
                            <h1 id="total-venta" class="display-4 fw-bold mb-0">$ 0.00</h1>
                            <div id="info-subtotal" class="small text-muted text-decoration-line-through" style="display:none; font-size: 0.9rem;">$ 0.00</div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small mb-1">Forma de Pago</label>
                            <select id="metodo-pago" class="form-select form-select-lg">
                                <option value="Efectivo">💵 Efectivo</option>
                                <option value="MP">📱 MercadoPago</option>
                                <option value="Debito">💳 Débito</option>
                                <option value="Credito">💳 Crédito</option>
                                <option value="Mixto">💸 PAGO MIXTO</option> <option value="CtaCorriente" class="fw-bold text-danger">🗒️ FIADO / CC</option>
                            </select>
                        </div>

                        <div id="box-vuelto" class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">Paga con $</span>
                                <input type="number" id="paga-con" class="form-control form-control-lg fw-bold">
                            </div>
                            <div class="d-flex justify-content-between mt-2 px-1">
                                <span class="text-muted">Su vuelto:</span>
                                <span id="monto-vuelto" class="h5 fw-bold text-success">$ 0.00</span>
                                <div id="desglose-billetes" class="alert alert-info mt-2 small mb-0" style="display:none;"></div>
                            </div>
                        </div>
                        
                        <div id="box-mixto-info" class="alert alert-info d-none text-center">
                            <i class="bi bi-info-circle"></i> Pago Mixto Seleccionado<br>
                            <small>Detalle se confirmará al finalizar.</small>
                        </div>

<div class="d-flex gap-2 mb-2">
    <button type="button" class="btn btn-warning fw-bold flex-fill" onclick="suspenderVentaActual()">
        <i class="bi bi-pause-circle"></i> ESPERA
    </button>
    <button type="button" class="btn btn-info fw-bold flex-fill text-white" onclick="abrirModalSuspendidas()">
        <i class="bi bi-arrow-counterclockwise"></i> RECUPERAR
    </button>
</div>


                        <div class="d-grid gap-2 mt-auto">
                            <button id="btn-finalizar" class="btn btn-success btn-lg py-3 fw-bold shadow">
                                <i class="bi bi-check-lg"></i> CONFIRMAR VENTA
                            </button>
                            <button onclick="vaciarCarrito()" class="btn btn-outline-danger btn-sm">Cancelar Venta</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBuscarCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Seleccionar Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="input-search-modal" class="form-control form-control-lg mb-3" placeholder="Escribe nombre o DNI..." autocomplete="off">
                    <div id="lista-clientes-modal" class="list-group mb-3"></div>
                    <button class="btn btn-secondary w-100" onclick="seleccionarCliente(1, 'Consumidor Final', 0, 0)">Usar Consumidor Final</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPagoMixto" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">💸 Desglose Pago Mixto</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalMixto()"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <span class="text-muted">Total a Pagar:</span>
                        <h2 class="fw-bold" id="total-mixto-display">$0.00</h2>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">Efectivo</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control input-mixto" id="mix-efectivo" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">MercadoPago / QR</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control input-mixto" id="mix-mp" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">Tarjeta Débito</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control input-mixto" id="mix-debito" placeholder="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">Tarjeta Crédito</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control input-mixto" id="mix-credito" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="alert alert-secondary mt-3 text-center py-2">
                        <div class="d-flex justify-content-between">
                            <span>Suma Pagos:</span>
                            <span class="fw-bold" id="mix-suma">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between text-danger fw-bold mt-1" id="mix-restante-box">
                            <span>Faltan:</span>
                            <span id="mix-faltan">$0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalMixto()">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold" id="btn-confirmar-mixto" disabled onclick="confirmarMixto()">CONFIRMAR PAGO</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let carrito = []; 
        let pagosMixtosConfirmados = null; // Variable para guardar el desglose
        const cuponesDB = <?php echo json_encode($cupones_db); ?>;
        const modalCliente = new bootstrap.Modal(document.getElementById('modalBuscarCliente'));
        const modalMixto = new bootstrap.Modal(document.getElementById('modalPagoMixto'));

        $(document).ready(function() { verificarVentaPausada(); });

        document.addEventListener('keydown', function(e) {
            if(e.key === 'F2') { e.preventDefault(); $('#buscar-producto').focus(); }
            if(e.key === 'F4') { e.preventDefault(); abrirModalClientes(); }
            if(e.key === 'F7') { e.preventDefault(); pausarVenta(); }
            if(e.key === 'F8') { e.preventDefault(); recuperarVenta(); }
            if(e.key === 'F9') { e.preventDefault(); $('#btn-finalizar').click(); }
            if(Swal.isVisible()) {
                if(e.key === 'Enter') { 
                    const confirmBtn = Swal.getConfirmButton();
                    if(confirmBtn) confirmBtn.click();
                }
            }
        });

        // FUNCIONES DE PAUSA
        function pausarVenta() {
            if(carrito.length === 0) return Swal.fire('Error', 'No hay nada para pausar', 'info');
            let estado = { carrito: carrito, cliente_id: $('#id-cliente').val(), cliente_nombre: $('#lbl-nombre-cliente').text(), cupon: $('#input-cupon').val(), desc_manual: $('#input-desc-manual').val() };
            localStorage.setItem('venta_pausada', JSON.stringify(estado));
            vaciarCarrito(); verificarVentaPausada();
            const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
            Toast.fire({icon: 'info', title: 'Venta puesta en espera (F8)'});
        }
        function recuperarVenta() {
            let data = localStorage.getItem('venta_pausada'); if(!data) return;
            if(carrito.length > 0) { Swal.fire({title: '¿Sobreescribir?', text: "Tenés productos en pantalla.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, recuperar'}).then((r) => { if(r.isConfirmed) cargarVentaRecuperada(JSON.parse(data)); }); } 
            else { cargarVentaRecuperada(JSON.parse(data)); }
        }
        function cargarVentaRecuperada(estado) {
            carrito = estado.carrito;
            seleccionarCliente(estado.cliente_id, estado.cliente_nombre, 0, 0); 
            $('#input-cupon').val(estado.cupon); $('#input-desc-manual').val(estado.desc_manual);
            render(); validarCupon(); localStorage.removeItem('venta_pausada'); verificarVentaPausada();
            const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000}); Toast.fire({icon: 'success', title: 'Venta recuperada'});
        }
        function verificarVentaPausada() {
            if(localStorage.getItem('venta_pausada')) $('#btn-recuperar').removeClass('d-none').addClass('btn-pausada-activa');
            else $('#btn-recuperar').addClass('d-none').removeClass('btn-pausada-activa');
        }

        // BUSCADOR Y RENDERIZADO
        $('#buscar-producto').on('keyup', function(e) {
            if(e.key === 'Enter') {
                let term = $(this).val(); if(term.length < 1) return;
                $.getJSON('acciones/buscar_producto.php', { term: term }, function(res) {
                    if(res.status == 'success' && res.data.length > 0) {
                        let exacto = res.data.find(p => p.codigo_barras == term);
                        exacto ? seleccionarProducto(exacto) : (res.data.length === 1 ? seleccionarProducto(res.data[0]) : null);
                    }
                }); return;
            }
            let term = $(this).val(); if(term.length < 1) { $('#lista-resultados').hide(); return; }
            $.getJSON('acciones/buscar_producto.php', { term: term }, function(res) {
                if(res.status == 'success') {
                    if(res.data.length === 1 && res.data[0].codigo_barras == term) seleccionarProducto(res.data[0]);
                    else {
                        let html = ''; res.data.forEach(p => { html += `<div class="item-resultado" onclick='seleccionarProducto(${JSON.stringify(p)})'><strong>${p.descripcion}</strong> <span class="float-end">$${p.precio_venta}</span></div>`; });
                        $('#lista-resultados').html(html).show();
                    }
                } else $('#lista-resultados').hide();
            });
        });

        window.seleccionarProducto = function(p) {
            let ex = carrito.find(i => i.id === p.id); 
            if(ex) ex.cantidad++; else carrito.push({id:p.id, descripcion:p.descripcion, precio:parseFloat(p.precio_venta), cantidad:1});
            render(); $('#buscar-producto').val('').focus(); $('#lista-resultados').hide();
        };

        function render() {
            let h = '', subtotal = 0; 
            carrito.forEach((i, x) => { 
                subtotal += i.precio * i.cantidad; 
                h += `<tr><td class="ps-3">${i.descripcion}</td><td>$${i.precio}</td><td><input type="number" class="form-control form-control-sm text-center" value="${i.cantidad}" onchange="upd(${x},this.value)"></td><td>$${(i.precio*i.cantidad).toFixed(2)}</td><td><button class="btn btn-sm text-danger" onclick="del(${x})"><i class="bi bi-trash"></i></button></td></tr>`; 
            });
            $('#carrito-body').html(h); $('#total-venta').attr('data-subtotal', subtotal); calc();
        }

        function upd(i,v){ if(v<=0)del(i); else carrito[i].cantidad=v; render(); } 
        function del(i){ carrito.splice(i,1); render(); } 
        function vaciarCarrito(){ 
            carrito = []; 
            $('#input-cupon').val(''); 
            $('#input-desc-manual').val(''); 
            $('#paga-con').val(''); 
            $('#monto-vuelto').text('$ 0.00'); 
            $('#desglose-billetes').hide().html(''); 
            $('#usar-saldo').prop('checked', false); 
            $('#pago-deuda-calculado').val(0);
            pagosMixtosConfirmados = null;
            $('#metodo-pago').val('Efectivo');
            $('#box-vuelto').show();
            $('#box-mixto-info').addClass('d-none');
            render(); 
        }
        
        $('#paga-con').on('keyup', calc); $('#input-desc-manual').on('keyup change', calc); $('#input-cupon').on('keyup', validarCupon);

        function validarCupon() {
            let codigo = $('#input-cupon').val().toUpperCase(); let idCliente = parseInt($('#id-cliente').val()); let msg = $('#msg-cupon');
            if(codigo.length === 0) { msg.hide(); $('#total-venta').attr('data-porc-desc', 0); calc(); return; }
            let cupon = cuponesDB.find(c => c.codigo === codigo);
            if(cupon) {
                if(cupon.id_cliente && cupon.id_cliente != idCliente) { msg.text('Cupón no válido para este cliente').attr('class','small fw-bold mt-1 text-danger').show(); $('#total-venta').attr('data-porc-desc', 0); } 
                else { msg.text('Cupón ' + cupon.descuento_porcentaje + '% OK').attr('class','small fw-bold mt-1 text-success').show(); $('#total-venta').attr('data-porc-desc', cupon.descuento_porcentaje); }
            } else { msg.text('Cupón inexistente').attr('class','small fw-bold mt-1 text-danger').show(); $('#total-venta').attr('data-porc-desc', 0); }
            calc();
        }

        function calc(){ 
            let subtotal = parseFloat($('#total-venta').attr('data-subtotal')) || 0;
            let porcDesc = parseFloat($('#total-venta').attr('data-porc-desc')) || 0;
            let manualDesc = parseFloat($('#input-desc-manual').val()) || 0;
            
            let saldoUsado = 0;
            let aPagarTemp = subtotal - ((subtotal * porcDesc) / 100) - manualDesc;
            
            if($('#usar-saldo').is(':checked') && aPagarTemp > 0){
                let disponible = parseFloat($('#val-saldo').val()) || 0;
                saldoUsado = (disponible >= aPagarTemp) ? aPagarTemp : disponible;
            }

            let descuentoCupon = (subtotal * porcDesc) / 100;
            let totalFinal = subtotal - descuentoCupon - manualDesc - saldoUsado; 
            
            if(totalFinal < 0) totalFinal = 0; 
            
            $('#total-venta').text('$ ' + totalFinal.toFixed(2));
            $('#total-venta').attr('data-total-final', totalFinal);
            
            let infoTxt = '';
            if(descuentoCupon > 0) infoTxt += 'Cupón: -$' + descuentoCupon.toFixed(2) + ' | ';
            if(manualDesc > 0) infoTxt += 'Manual: -$' + manualDesc.toFixed(2) + ' | ';
            if(saldoUsado > 0) infoTxt += 'Saldo Favor: -$' + saldoUsado.toFixed(2);
            
            if(infoTxt != '') $('#info-subtotal').text(infoTxt).show(); 
            else $('#info-subtotal').hide();

            // LOGICA DEUDA (SOLO SI NO ES MIXTO)
            if($('#metodo-pago').val() !== 'Mixto') {
                let paga = parseFloat($('#paga-con').val()) || 0; 
                let vueltoPre = paga - totalFinal; 
                
                let deudaCliente = parseFloat($('#val-deuda').val()) || 0;
                let montoDeudaCobrar = 0;

                if(vueltoPre > 0 && deudaCliente > 0) {
                    montoDeudaCobrar = (vueltoPre >= deudaCliente) ? deudaCliente : vueltoPre;
                }
                
                $('#pago-deuda-calculado').val(montoDeudaCobrar);

                let vueltoFinal = vueltoPre - montoDeudaCobrar;

                if(vueltoFinal >= 0 && paga > 0) { 
                    let textoVuelto = '$ ' + vueltoFinal.toFixed(2);
                    if(montoDeudaCobrar > 0) textoVuelto += ' <span class="badge bg-danger ms-2" style="font-size:0.6em">Se cobró deuda: $' + montoDeudaCobrar.toFixed(2) + '</span>';
                    $('#monto-vuelto').html(textoVuelto);
                    
                    let resto = vueltoFinal;
                    let textoBilletes = '<strong>Entregar:</strong><br>';
                    const billetes = [20000, 10000, 2000, 1000, 500, 200, 100, 50, 20, 10];
                    billetes.forEach(b => {
                        if(resto >= b) {
                            let cant = Math.floor(resto / b);
                            if(cant > 0) {
                                textoBilletes += `${cant} x $${b}<br>`;
                                resto -= (cant * b);
                            }
                        }
                    });
                    if(resto > 0) textoBilletes += `Monedas: $${resto.toFixed(2)}`;
                    $('#desglose-billetes').html(textoBilletes).show();
                } else {
                    $('#monto-vuelto').text('$ 0.00');
                    $('#desglose-billetes').hide();
                }
            }
        }
        
        // MANEJO DE METODO DE PAGO
        $('#metodo-pago').change(function(){ 
            let val = $(this).val();
            if(val == 'Mixto') {
                $('#box-vuelto').hide();
                $('#box-mixto-info').removeClass('d-none');
                abrirModalMixto();
            } else if(val == 'Efectivo') {
                $('#box-vuelto').slideDown();
                $('#box-mixto-info').addClass('d-none');
                pagosMixtosConfirmados = null; // Resetear mixto si cambia
            } else {
                $('#box-vuelto').slideUp();
                $('#box-mixto-info').addClass('d-none');
                pagosMixtosConfirmados = null;
            }
            calc();
        });

        // LOGICA MODAL MIXTO
        function abrirModalMixto() {
            if(carrito.length === 0) {
                Swal.fire('Error', 'Carrito vacío', 'error');
                $('#metodo-pago').val('Efectivo').trigger('change');
                return;
            }
            calc(); // Asegurar total actualizado
            let total = parseFloat($('#total-venta').attr('data-total-final')) || 0;
            $('#total-mixto-display').text('$' + total.toFixed(2));
            $('.input-mixto').val(''); // Limpiar inputs
            calcRestanteMixto();
            modalMixto.show();
        }

        $('.input-mixto').on('keyup change', calcRestanteMixto);

        function calcRestanteMixto() {
            let total = parseFloat($('#total-venta').attr('data-total-final')) || 0;
            let ef = parseFloat($('#mix-efectivo').val()) || 0;
            let mp = parseFloat($('#mix-mp').val()) || 0;
            let db = parseFloat($('#mix-debito').val()) || 0;
            let cr = parseFloat($('#mix-credito').val()) || 0;
            
            let suma = ef + mp + db + cr;
            let faltan = total - suma;
            
            $('#mix-suma').text('$' + suma.toFixed(2));
            
            if(Math.abs(faltan) < 0.1) {
                $('#mix-restante-box').html('<span class="text-success fw-bold">¡Completo!</span>');
                $('#btn-confirmar-mixto').prop('disabled', false);
            } else if(faltan > 0) {
                $('#mix-restante-box').html('<span>Faltan:</span> <span class="text-danger">$' + faltan.toFixed(2) + '</span>');
                $('#btn-confirmar-mixto').prop('disabled', true);
            } else {
                $('#mix-restante-box').html('<span>Excede por:</span> <span class="text-warning">$' + Math.abs(faltan).toFixed(2) + '</span>');
                $('#btn-confirmar-mixto').prop('disabled', true);
            }
        }

        function confirmarMixto() {
            pagosMixtosConfirmados = {
                'Efectivo': parseFloat($('#mix-efectivo').val()) || 0,
                'MP': parseFloat($('#mix-mp').val()) || 0,
                'Debito': parseFloat($('#mix-debito').val()) || 0,
                'Credito': parseFloat($('#mix-credito').val()) || 0
            };
            modalMixto.hide();
        }

        function cerrarModalMixto() {
            modalMixto.hide();
            // Si cancela y no tenía confirmado, volvemos a efectivo
            if(!pagosMixtosConfirmados) {
                $('#metodo-pago').val('Efectivo').trigger('change');
            }
        }

        window.abrirModalClientes = function() { $('#input-search-modal').val(''); $('#lista-clientes-modal').html(''); modalCliente.show(); setTimeout(()=>$('#input-search-modal').focus(),500); };
        
        $('#input-search-modal').on('keyup', function() {
            let term = $(this).val(); if(term.length < 2) return;
            $.getJSON('acciones/buscar_cliente_ajax.php', { term: term }, function(res) {
                let html = ''; if(res.length > 0) res.forEach(c => { 
                    let dni = c.dni ? c.dni : '--'; 
                    let saldoClass = c.saldo_actual > 0 ? 'text-danger fw-bold' : 'text-success'; 
                    let textoSaldo = c.saldo_actual > 0 ? 'Debe $' + c.saldo_actual : 'Al día'; 
                    html += `<button class="list-group-item list-group-item-action p-3" onclick="seleccionarCliente(${c.id}, '${c.nombre}', ${c.saldo_actual}, ${c.saldo_favor || 0})"><div class="d-flex w-100 justify-content-between align-items-center"><div><h6 class="mb-1 fw-bold">${c.nombre}</h6><div class="small text-muted">DNI: ${dni}</div></div><div class="text-end"><small class="${saldoClass}">${textoSaldo}</small></div></div></button>`; 
                }); else html = '<div class="p-3 text-center text-muted">No se encontraron clientes.</div>'; $('#lista-clientes-modal').html(html);
            });
        });

        window.seleccionarCliente=function(id,n,d,s){ 
            $('#id-cliente').val(id); 
            $('#lbl-nombre-cliente').text(n); 
            $('#val-deuda').val(d);
            
            if(d > 0) { $('#lbl-deuda').text('$'+d); $('#info-deuda').removeClass('d-none'); } 
            else { $('#info-deuda').addClass('d-none'); }
            
            let saldo = parseFloat(s || 0);
            if(saldo > 0) {
                $('#lbl-saldo').text('$' + saldo.toFixed(2));
                $('#val-saldo').val(saldo);
                $('#info-saldo').removeClass('d-none');
                $('#usar-saldo').prop('checked', true); 
            } else {
                $('#info-saldo').addClass('d-none');
                $('#val-saldo').val(0);
                $('#usar-saldo').prop('checked', false);
            }
            modalCliente.hide(); validarCupon(); $('#buscar-producto').focus(); calc(); 
        };

        $('#btn-finalizar').click(function(){
            if(carrito.length==0) return Swal.fire('Error','Carrito vacío','error');
            let m = $('#metodo-pago').val(); let c = $('#id-cliente').val(); let totalFinal = parseFloat($('#total-venta').text().replace('$ ',''));
            let cuponCode = $('#input-cupon').val().toUpperCase(); let porcDesc = parseFloat($('#total-venta').attr('data-porc-desc')) || 0; let subtotal = parseFloat($('#total-venta').attr('data-subtotal')) || 0; let descCuponPlata = (subtotal * porcDesc) / 100; let descManual = parseFloat($('#input-desc-manual').val()) || 0;

            if(m=='CtaCorriente'&&c==1) return Swal.fire('Error','No se fía a Consumidor Final','warning');
            
            // VALIDACIÓN PAGO MIXTO
            if(m == 'Mixto' && !pagosMixtosConfirmados) {
                return Swal.fire('Atención', 'Seleccionaste Pago Mixto pero no completaste los montos.', 'warning').then(() => abrirModalMixto());
            }

            let saldoUsadoEnvio = 0;
            if($('#usar-saldo').is(':checked')){
                let disponible = parseFloat($('#val-saldo').val()) || 0;
                let aPagarTemp = subtotal - descCuponPlata - descManual;
                saldoUsadoEnvio = (disponible >= aPagarTemp) ? aPagarTemp : disponible;
            }

            let deudaACobrar = parseFloat($('#pago-deuda-calculado').val()) || 0;
            // Si es mixto, por ahora no cobramos deuda automáticamente para no complicar el cálculo manual
            if(m == 'Mixto') deudaACobrar = 0; 

            $.post('acciones/procesar_venta.php', {
                items: carrito, total: totalFinal, metodo: m, id_cliente: c,
                cupon_codigo: cuponCode, desc_cupon_monto: descCuponPlata, desc_manual_monto: descManual,
                saldo_favor_usado: saldoUsadoEnvio,
                pago_deuda: deudaACobrar,
                pagos_mixtos: (m == 'Mixto') ? pagosMixtosConfirmados : null // Enviamos el desglose
            }, function(r){
                if(r.status=='success'){ 
                    Swal.fire({
                        title: '¡Venta Registrada!', text: "¿Deseas imprimir el ticket?", icon: 'success', showCancelButton: true, confirmButtonText: '<i class="bi bi-printer"></i> Imprimir Ticket', cancelButtonText: 'Nueva Venta'
                    }).then((result) => {
                        if (result.isConfirmed) { window.open('ticket.php?id=' + r.id_venta, 'Ticket', 'width=350,height=500'); }
                        vaciarCarrito(); seleccionarCliente(1,'Consumidor Final',0,0); $('#buscar-producto').focus();
                    });
                } else Swal.fire('Error',r.msg,'error');
            },'json');
        });

        $(document).ready(function() { cargarRapidos(''); });

        function cargarRapidos(categoria) {
            $('#grid-rapidos').html('<div class="text-center w-100"><div class="spinner-border spinner-border-sm"></div></div>');
            $('#filtros-rapidos button').removeClass('btn-dark fw-bold').addClass('btn-outline-secondary');
            $.getJSON('acciones/listar_rapidos.php', { cat: categoria }, function(data) {
                let html = '';
                if(data.length > 0) {
                    data.forEach(p => {
                        let nombre = p.descripcion.length > 15 ? p.descripcion.substring(0,15)+'..' : p.descripcion;
                        html += `<div class="col-4 col-md-3 col-lg-2"><div class="card h-100 shadow-sm border-0 producto-rapido" onclick='seleccionarProducto(${JSON.stringify(p)})' style="cursor:pointer;"><div class="card-body p-2 text-center"><div class="fw-bold small text-truncate" title="${p.descripcion}">${nombre}</div><div class="text-primary fw-bold small">$${p.precio_venta}</div></div></div></div>`;
                    });
                } else { html = '<div class="text-center w-100 text-muted small">No hay productos en esta categoría.</div>'; }
                $('#grid-rapidos').html(html);
            });
        }
    </script>
    <div class="modal fade" id="modalSuspendidas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pause-circle-fill"></i> Ventas en Espera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="listaSuspendidasBody"></div>
        </div>
    </div>
</div>

<script>
// --- NUEVAS FUNCIONES DE SUSPENDER (Conectadas a tu sistema real) ---

function suspenderVentaActual() {
    // Usamos TU variable 'carrito' que ya existe en el script principal
    if (carrito.length === 0) {
        Swal.fire('Atención', 'El carrito está vacío, no hay nada que guardar.', 'warning'); 
        return;
    }

    // Calculamos el total recorriendo tu carrito
    let total = 0;
    carrito.forEach(p => total += (parseFloat(p.precio) * parseFloat(p.cantidad)));

    Swal.fire({
        title: 'Dejar venta en Espera',
        input: 'text',
        inputPlaceholder: 'Referencia (Ej: "Señora Rubia")',
        inputAttributes: { maxlength: 50 },
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: '<i class="bi bi-pause-circle"></i> Suspender',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let ref = result.value || 'Sin Referencia';
            
            // Llamamos a los archivos que moviste a la carpeta 'acciones'
            $.ajax({
                url: 'acciones/suspender_guardar.php',
                type: 'POST',
                data: JSON.stringify({ carrito: carrito, referencia: ref, total: total }),
                contentType: 'application/json',
                success: function(data) {
                    let res = (typeof data === 'string') ? JSON.parse(data) : data;
                    
                    if(res.status === 'success') {
                        vaciarCarrito(); // Usamos TU función vaciarCarrito() del script principal
                        Swal.fire({
                            title: '¡Suspendida!',
                            text: 'La venta quedó guardada en el servidor.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', 'No se pudo guardar: ' + (res.msg || 'Error desconocido'), 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión con acciones/suspender_guardar.php', 'error');
                }
            });
        }
    });
}

function abrirModalSuspendidas() {
    let modal = new bootstrap.Modal(document.getElementById('modalSuspendidas'));
    document.getElementById('listaSuspendidasBody').innerHTML = '<div class="p-4 text-center"><div class="spinner-border text-warning"></div><div class="mt-2">Cargando ventas...</div></div>';
    modal.show();

    $.get('acciones/suspender_listar.php', function(html) {
        document.getElementById('listaSuspendidasBody').innerHTML = html;
    });
}

function recuperarVentaId(id) {
    Swal.fire({
        title: '¿Retomar esta venta?',
        text: "Se sumará a lo que tengas en el carrito actual.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Retomar',
        confirmButtonColor: '#0dcaf0'
    }).then((r) => {
        if(r.isConfirmed) {
            $.getJSON('acciones/suspender_recuperar.php', { id: id }, function(data) {
                if(data.status === 'success') {
                    // Agregamos los items recuperados a TU variable 'carrito'
                    data.items.forEach(item => {
                        let existe = carrito.find(i => i.id === parseInt(item.id));
                        if(existe) {
                            existe.cantidad += parseInt(item.cantidad);
                        } else {
                            carrito.push({
                                id: parseInt(item.id),
                                descripcion: item.nombre, 
                                precio: parseFloat(item.precio),
                                cantidad: parseInt(item.cantidad)
                            });
                        }
                    });
                    
                    // Actualizamos la vista usando TU función render()
                    render();
                    
                    // Cerramos el modal
                    let modalEl = document.getElementById('modalSuspendidas');
                    let modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if(modalInstance) modalInstance.hide();
                    
                    const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
                    Toast.fire({icon: 'success', title: 'Venta recuperada exitosamente'});
                } else {
                    Swal.fire('Error', 'No se pudieron traer los datos', 'error');
                }
            });
        }
    });
}
</script>

</body>
</html>