<?php
// ventas.php - MÁRGENES ARREGLADOS Y MENÚ NUEVO
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/db.php';
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
        .item-resultado { padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; transition: 0.2s; }
        .item-resultado:hover { background-color: #e9ecef; padding-left: 15px; }
    </style>
</head>
<body class="bg-light">

    <?php include 'includes/menu.php'; ?>

    <div class="container pb-5"> 
        <div class="row g-4">
            
            <div class="col-lg-8 col-12 order-2 order-lg-1">
                <div class="card shadow border-0 mb-3">
                    <div class="card-body position-relative">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscar-producto" class="form-control border-start-0" placeholder="Buscar producto o escanear..." autocomplete="off">
                        </div>
                        <div id="lista-resultados" class="list-group position-absolute w-100 shadow mt-1" style="z-index: 1000; display:none;"></div>
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-cart3"></i> Carrito de Compras</div>
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
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="abrirModalClientes()"><i class="bi bi-pencil-fill"></i> Cambiar</button>
                        </div>
                        
                        <div id="info-deuda" class="d-none mb-3 text-center">
                            <div class="alert alert-danger py-1 mb-0 fw-bold">Deuda: <span id="lbl-deuda"></span></div>
                        </div>

                        <div class="total-box text-center mb-4">
                            <small class="text-uppercase text-secondary">Total a Pagar</small>
                            <h1 id="total-venta" class="display-4 fw-bold mb-0">$ 0.00</h1>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small mb-1">Forma de Pago</label>
                            <select id="metodo-pago" class="form-select form-select-lg">
                                <option value="Efectivo">💵 Efectivo</option>
                                <option value="MP">📱 MercadoPago</option>
                                <option value="Debito">💳 Débito</option>
                                <option value="Credito">💳 Crédito</option>
                                <option value="CtaCorriente" class="fw-bold text-danger">🗒️ FIADO / CC</option>
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
                            </div>
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
                    <button class="btn btn-secondary w-100" onclick="seleccionarCliente(1, 'Consumidor Final', 0)">Usar Consumidor Final</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let carrito = []; const modalCliente = new bootstrap.Modal(document.getElementById('modalBuscarCliente'));
        $('#buscar-producto').on('keyup', function() {
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
            let ex = carrito.find(i => i.id === p.id); if(ex) ex.cantidad++; else carrito.push({id:p.id, descripcion:p.descripcion, precio:parseFloat(p.precio_venta), cantidad:1});
            render(); $('#buscar-producto').val('').focus(); $('#lista-resultados').hide();
        };
        function render() {
            let h = '', t = 0; carrito.forEach((i, x) => { t += i.precio * i.cantidad; h += `<tr><td class="ps-3">${i.descripcion}</td><td>$${i.precio}</td><td><input type="number" class="form-control form-control-sm text-center" value="${i.cantidad}" onchange="upd(${x},this.value)"></td><td>$${(i.precio*i.cantidad).toFixed(2)}</td><td><button class="btn btn-sm text-danger" onclick="del(${x})"><i class="bi bi-trash"></i></button></td></tr>`; });
            $('#carrito-body').html(h); $('#total-venta').text('$ '+t.toFixed(2)); calc();
        }
        function upd(i,v){ if(v<=0)del(i);else carrito[i].cantidad=v; render(); } function del(i){ carrito.splice(i,1); render(); } function vaciarCarrito(){ carrito=[]; render(); }
        $('#paga-con').on('keyup', calc); function calc(){ let t=parseFloat($('#total-venta').text().replace('$ ','')), p=parseFloat($('#paga-con').val())||0; $('#monto-vuelto').text('$ '+(p-t > 0?(p-t).toFixed(2):'0.00')); }
        $('#metodo-pago').change(function(){ $(this).val()=='Efectivo'?$('#box-vuelto').slideDown():$('#box-vuelto').slideUp(); });
        // CLIENTES
        window.abrirModalClientes = function() { $('#input-search-modal').val(''); $('#lista-clientes-modal').html(''); modalCliente.show(); setTimeout(()=>$('#input-search-modal').focus(),500); };
        $('#input-search-modal').on('keyup', function() {
            let term = $(this).val();
            if(term.length < 2) return;

            $.getJSON('acciones/buscar_cliente_ajax.php', { term: term }, function(res) {
                let html = '';
                if(res.length > 0) {
                    res.forEach(c => {
                        // Preparamos los datos para que no se rompa si están vacíos
                        let dni = c.dni ? c.dni : '--';
                        let tel = c.whatsapp ? '<i class="bi bi-whatsapp text-success"></i> ' + c.whatsapp : '';
                        let compras = c.cantidad_compras ? c.cantidad_compras : '0'; // Si es null pone 0
                        let saldoClass = c.saldo_actual > 0 ? 'text-danger fw-bold' : 'text-success';
                        let textoSaldo = c.saldo_actual > 0 ? 'Debe $' + c.saldo_actual : 'Al día';

                        html += `
                        <button class="list-group-item list-group-item-action p-3" onclick="seleccionarCliente(${c.id}, '${c.nombre}', ${c.saldo_actual})">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold">${c.nombre}</h6>
                                    <div class="small text-muted">
                                        DNI: ${dni} &nbsp;|&nbsp; ${tel}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary mb-1">${compras} Compras</span><br>
                                    <small class="${saldoClass}">${textoSaldo}</small>
                                </div>
                            </div>
                        </button>`;
                    });
                } else {
                    html = '<div class="p-3 text-center text-muted">No se encontraron clientes.</div>';
                }
                $('#lista-clientes-modal').html(html);
            });
        });
        window.seleccionarCliente=function(id,n,d){ $('#id-cliente').val(id); $('#lbl-nombre-cliente').text(n); if(d>0){$('#lbl-deuda').text('$'+d); $('#info-deuda').removeClass('d-none');}else{$('#info-deuda').addClass('d-none');} modalCliente.hide(); };
        $('#btn-finalizar').click(function(){
            if(carrito.length==0) return Swal.fire('Error','Carrito vacío','error');
            let m=$('#metodo-pago').val(), c=$('#id-cliente').val();
            if(m=='CtaCorriente'&&c==1) return Swal.fire('Error','No se fía a Consumidor Final','warning');
            $.post('acciones/procesar_venta.php', {items:carrito, total:parseFloat($('#total-venta').text().replace('$ ','')), metodo:m, id_cliente:c}, function(r){
                if(r.status=='success'){ Swal.fire({title:'¡Venta OK!',icon:'success',timer:1500,showConfirmButton:false}); vaciarCarrito(); seleccionarCliente(1,'Consumidor Final',0); } else Swal.fire('Error',r.msg,'error');
            },'json');
        });
    </script>
</body>
</html>