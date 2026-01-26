<?php
// tienda.php - VERSIÓN FINAL CON CUPONES Y BACKEND
require_once 'includes/db.php';

// DATOS DE CONFIGURACIÓN
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch();

// PRODUCTOS (IGUAL QUE ANTES)
$categorias = $conexion->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();
$filtro = $_GET['q'] ?? '';
$cat_filtro = $_GET['cat'] ?? '';
$sql = "SELECT p.*, c.nombre as categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id WHERE p.activo = 1 AND p.stock_actual > 0";
if($filtro) { $sql .= " AND p.descripcion LIKE '%$filtro%'"; }
if($cat_filtro) { $sql .= " AND p.id_categoria = $cat_filtro"; }
$sql .= " ORDER BY p.es_destacado_web DESC, p.descripcion ASC";
$productos = $conexion->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($conf->nombre_negocio); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; padding-bottom: 80px; }
        .hero-banner { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; padding: 40px 20px; border-radius: 0 0 25px 25px; margin-bottom: 20px; text-align: center; }
        .card-producto { border: none; border-radius: 15px; overflow: hidden; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.2s; height: 100%; }
        .img-container { height: 180px; width: 100%; background-color: #fff; display: flex; align-items: center; justify-content: center; position: relative; }
        .img-producto { max-height: 150px; max-width: 90%; object-fit: contain; }
        .btn-float-cart { position: fixed; bottom: 20px; right: 20px; background-color: #25D366; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); z-index: 1050; border: none; }
        .cart-counter { position: absolute; top: -5px; right: -5px; background: red; color: white; font-size: 12px; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .offcanvas-bottom { height: 85vh !important; border-radius: 20px 20px 0 0; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand bg-white shadow-sm sticky-top">
        <div class="container">
            <span class="navbar-brand fw-bold text-primary"><i class="bi bi-shop"></i> <?php echo htmlspecialchars($conf->nombre_negocio); ?></span>
            <a href="dashboard.php" class="btn btn-outline-dark btn-sm rounded-pill px-3"><i class="bi bi-person-fill"></i> Soy Dueño</a>
        </div>
    </nav>

    <div class="hero-banner shadow">
        <h2 class="fw-bold">¡Pedí Online!</h2>
        <p class="mb-3 opacity-75">Tus productos favoritos directo a WhatsApp</p>
        <form action="" method="GET" class="d-flex justify-content-center">
            <div class="input-group" style="max-width: 500px;">
                <input type="text" name="q" class="form-control border-0 rounded-start-pill py-2 ps-4" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtro); ?>">
                <button class="btn btn-light border-0 rounded-end-pill text-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <div class="container mb-4">
        <div class="d-flex gap-2 overflow-auto" style="white-space: nowrap;">
            <a href="tienda.php" class="btn btn-sm rounded-pill shadow-sm <?php echo $cat_filtro == '' ? 'btn-primary' : 'btn-light'; ?>">Todo</a>
            <?php foreach($categorias as $c): ?>
                <a href="tienda.php?cat=<?php echo $c->id; ?>" class="btn btn-sm rounded-pill shadow-sm <?php echo $cat_filtro == $c->id ? 'btn-primary' : 'btn-light'; ?>">
                    <?php echo $c->nombre; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container">
        <div class="row g-3">
            <?php foreach($productos as $p): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card card-producto h-100">
                    <div class="img-container">
                        <img src="<?php echo $p->imagen_url ?: 'img/no-image.png'; ?>" class="img-producto">
                        <?php if($p->stock_actual < $p->stock_minimo): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2">Poco Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <h6 class="card-title fw-bold mb-1 text-dark text-truncate"><?php echo $p->descripcion; ?></h6>
                        <small class="text-muted mb-2"><?php echo $p->categoria; ?></small>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fs-5 fw-bold text-primary">$<?php echo number_format($p->precio_venta, 0); ?></span>
                            <button class="btn btn-primary btn-sm rounded-circle" style="width: 32px; height: 32px;" onclick='agregarAlCarrito(<?php echo json_encode($p); ?>)'>
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="btn-float-cart" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito">
        <i class="bi bi-cart-fill"></i>
        <div class="cart-counter" id="cart-count">0</div>
    </button>

    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasCarrito">
        <div class="offcanvas-header bg-light border-bottom">
            <h5 class="offcanvas-title fw-bold">Finalizar Compra</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div id="lista-carrito" class="list-group list-group-flush mb-3"></div>
            
            <div class="p-3 bg-light border-top">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-lines-fill"></i> Tus Datos (Para puntos)</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <input type="text" id="cliente-nombre" class="form-control form-control-sm" placeholder="Tu Nombre">
                    </div>
                    <div class="col-6">
                        <input type="tel" id="cliente-dni" class="form-control form-control-sm" placeholder="DNI (Opcional)">
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-ticket-perforated"></i> ¿Tenés Cupón?</h6>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="input-cupon" class="form-control text-uppercase" placeholder="Código">
                    <button class="btn btn-outline-secondary" type="button" onclick="aplicarCupon()">Aplicar</button>
                </div>
                <div id="msg-cupon" class="small text-success fw-bold mb-2" style="display:none;"></div>
            </div>
        </div>

        <div class="p-3 bg-white border-top shadow-lg">
            <div class="d-flex justify-content-between mb-2">
                <span class="h5">Total:</span>
                <span class="h4 fw-bold text-primary" id="total-carrito">$0</span>
            </div>
            <button id="btn-pedir" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow" onclick="procesarPedido()">
                CONFIRMAR PEDIDO <i class="bi bi-whatsapp"></i>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let carrito = [];
        let cuponAplicado = null; // Guardará el objeto cupón si es válido

        function agregarAlCarrito(p) {
            let item = carrito.find(i => i.id === p.id);
            if(item) item.cantidad++; else carrito.push({id:p.id, nombre:p.descripcion, precio:parseFloat(p.precio_venta), cantidad:1});
            actualizarUI();
            const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 800});
            Toast.fire({icon: 'success', title: 'Agregado'});
        }

        function actualizarUI() {
            let totalItems = carrito.reduce((s, i) => s + i.cantidad, 0);
            document.getElementById('cart-count').innerText = totalItems;
            
            let lista = document.getElementById('lista-carrito');
            let totalPlata = 0; lista.innerHTML = '';

            carrito.forEach((it, idx) => {
                totalPlata += it.precio * it.cantidad;
                lista.innerHTML += `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div><div class="fw-bold">${it.nombre}</div><small>$${it.precio} x ${it.cantidad}</small></div>
                    <div><span class="fw-bold me-2">$${it.precio*it.cantidad}</span><button class="btn btn-sm btn-outline-danger border-0" onclick="borrar(${idx})"><i class="bi bi-trash"></i></button></div></div>`;
            });
            
            // Calculo Descuento Visual
            // Nota: La validación real se hace en el servidor, esto es visual.
            document.getElementById('total-carrito').innerText = '$' + totalPlata;
        }

        function borrar(i){ carrito.splice(i,1); actualizarUI(); }

        function aplicarCupon() {
            let codigo = document.getElementById('input-cupon').value;
            // Solo visual: El descuento real se calcula en el backend al enviar
            // Para hacerlo simple y no hacer 2 llamadas AJAX, lo mandamos al final.
            if(codigo.length > 3) {
                document.getElementById('msg-cupon').style.display = 'block';
                document.getElementById('msg-cupon').innerText = 'Cupón "' + codigo + '" se validará al confirmar.';
            }
        }

        function procesarPedido() {
            if(carrito.length===0) return Swal.fire('Carrito Vacío', 'Agrega productos.', 'warning');
            
            let nombre = document.getElementById('cliente-nombre').value;
            if(nombre.length < 3) return Swal.fire('Faltan Datos', 'Por favor escribe tu nombre.', 'warning');

            // Feedback de carga
            let btn = document.getElementById('btn-pedir');
            btn.innerHTML = 'Procesando...'; btn.disabled = true;

            // Datos a enviar
            let data = {
                carrito: carrito,
                cupon: document.getElementById('input-cupon').value,
                nombre: nombre,
                dni: document.getElementById('cliente-dni').value
            };

            fetch('acciones/finalizar_pedido.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(resp => {
                if(resp.status === 'success') {
                    // Abrir WhatsApp
                    window.location.href = resp.url;
                } else {
                    Swal.fire('Error', resp.msg, 'error');
                    btn.innerHTML = 'CONFIRMAR PEDIDO'; btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error de conexión', 'Intenta de nuevo', 'error');
                btn.innerHTML = 'CONFIRMAR PEDIDO'; btn.disabled = false;
            });
        }
    </script>
</body>
</html>