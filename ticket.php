<?php
// ticket.php - TU VERSIÓN ORIGINAL MODIFICADA
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) { die("Acceso denegado"); }

$id_venta = $_GET['id'] ?? 0;

// 1. DATOS VENTA
$stmt = $conexion->prepare("SELECT v.*, u.usuario, c.nombre as cliente, c.dni 
                            FROM ventas v 
                            JOIN usuarios u ON v.id_usuario = u.id 
                            JOIN clientes c ON v.id_cliente = c.id 
                            WHERE v.id = ?");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venta) die("Venta no encontrada");

// 2. DETALLES
$stmtDet = $conexion->prepare("SELECT d.*, p.descripcion 
                              FROM detalle_ventas d 
                              JOIN productos p ON d.id_producto = p.id 
                              WHERE d.id_venta = ?");
$stmtDet->execute([$id_venta]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

// 3. CONFIGURACIÓN NEGOCIO
$conf = $conexion->query("SELECT * FROM configuracion WHERE id=1")->fetch(PDO::FETCH_ASSOC);

// --- LÓGICA AGREGADA PARA CALCULAR SALDO A FAVOR USADO ---
$subtotal_real_productos = 0;
foreach($detalles as $d) {
    $subtotal_real_productos += $d['subtotal'];
}
// El total pagado está en $venta['total']
// Los descuentos explícitos están en $venta['descuento_monto_cupon'] y $venta['descuento_manual']
// La diferencia matemática es lo que se pagó con saldo a favor
$saldo_favor_usado = $subtotal_real_productos - ($venta['descuento_monto_cupon'] ?? 0) - ($venta['descuento_manual'] ?? 0) - $venta['total'];

// Ajuste por decimales (flotantes)
if($saldo_favor_usado < 0.05) $saldo_favor_usado = 0;
// ---------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $id_venta; ?></title>
    <style>
        /* ESTILOS TÉRMICOS (RESET) - TUS ESTILOS ORIGINALES */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Courier New', Courier, monospace; }
        body { width: 100%; background: #fff; color: #000; font-size: 12px; }
        
        .ticket {
            width: 100%;
            max-width: 300px; /* Ancho máximo para 80mm, se adapta a menos */
            margin: 0 auto;
            padding: 5px;
        }
        
        .centrado { text-align: center; }
        .derecha { text-align: right; }
        .negrita { font-weight: bold; }
        
        .linea { border-top: 1px dashed #000; margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }
        
        .cantidad { width: 25px; }
        .producto { }
        .precio { text-align: right; width: 60px; }

        .totales { margin-top: 5px; font-size: 14px; }
        
        /* Ocultar botón en impresión */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">
        <div class="centrado">
            <h3 class="negrita" style="font-size: 16px;">Peca's Store</h3>
            <p><?php echo $conf['direccion_local']; ?></p>
            <p>Whatsapp: <?php echo $conf['telefono_whatsapp']; ?></p>
            <div class="linea"></div>
            <p>Ticket: #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></p>
            <p>Cajero: <?php echo strtoupper($venta['usuario']); ?></p>
        </div>
        
        <div class="linea"></div>
        
        <div>
            Cliente: <?php echo substr($venta['cliente'], 0, 20); ?><br>
            <?php if($venta['dni']) echo "DNI: " . $venta['dni']; ?>
        </div>
        
        <div class="linea"></div>
        
        <table>
            <thead>
                <tr>
                    <th class="cantidad">Can</th>
                    <th class="producto" style="text-align:left;">Prod</th>
                    <th class="precio">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detalles as $d): ?>
                <tr>
                    <td class="cantidad"><?php echo floatval($d['cantidad']); ?></td>
                    <td class="producto"><?php echo substr($d['descripcion'], 0, 20); ?></td>
                    <td class="precio">$<?php echo number_format($d['subtotal'], 2, ',', '.'); ?></td> </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="linea"></div>
        
        <div class="derecha totales">
            <p style="font-size: 12px;">Subtotal: $<?php echo number_format($subtotal_real_productos, 2, ',', '.'); ?></p>

            <?php if($venta['descuento_monto_cupon'] > 0): ?>
                <p>Desc. Cupón: -$<?php echo number_format($venta['descuento_monto_cupon'], 2, ',', '.'); ?></p>
            <?php endif; ?>
            
            <?php if($venta['descuento_manual'] > 0): ?>
                <p>Desc. Manual: -$<?php echo number_format($venta['descuento_manual'], 2, ',', '.'); ?></p>
            <?php endif; ?>

            <?php if($saldo_favor_usado > 0): ?>
                <p>Saldo Favor: -$<?php echo number_format($saldo_favor_usado, 2, ',', '.'); ?></p>
            <?php endif; ?>

            <p class="negrita" style="font-size: 16px; margin-top: 5px;">TOTAL: $<?php echo number_format($venta['total'], 2, ',', '.'); ?></p>
            <p style="font-size: 11px;">Pago: <?php echo $venta['metodo_pago']; ?></p>
        </div>
        
        <div class="linea"></div>
        <div class="centrado">
            <p>¡Gracias por su compra!</p>
            <p>********</p>
        </div>

        <button class="no-print" style="width:100%; padding:10px; margin-top:10px; cursor:pointer;" onclick="window.close()">CERRAR VENTANA</button>
    </div>
</body>
</html>