<?php
// cartel_qr.php - GENERADOR DE CARTEL PARA MOSTRADOR
session_start();
// Seguridad: Solo admin/dueño
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

// 1. Detectar la URL del sistema automáticamente
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Asumimos que registro_cliente.php está en la misma carpeta que este archivo
$path = dirname($_SERVER['PHP_SELF']);
$url_registro = $protocolo . "://" . $host . $path . "/registro_cliente.php";

// Limpiamos barras dobles si aparecen
$url_registro = str_replace('//registro', '/registro', $url_registro);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cartel QR - Kiosco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #555; }
        .hoja-a4 {
            background: white;
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            padding: 40px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .titulo { font-size: 3.5rem; font-weight: 900; color: #000; line-height: 1.1; margin-bottom: 10px; }
        .subtitulo { font-size: 1.8rem; color: #555; margin-bottom: 40px; }
        .qr-box { border: 4px solid #000; padding: 20px; border-radius: 20px; display: inline-block; }
        .qr-img { width: 400px; height: 400px; }
        .instruccion { margin-top: 30px; font-size: 1.5rem; font-weight: bold; background: #000; color: #fff; padding: 10px 40px; border-radius: 50px; }
        
        @media print {
            body { background: white; margin: 0; }
            .hoja-a4 { width: 100%; height: 100vh; box-shadow: none; margin: 0; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="text-center mt-3 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-warning fw-bold btn-lg">🖨️ IMPRIMIR CARTEL</button>
        <a href="clientes.php" class="btn btn-secondary btn-lg">Volver</a>
        <div class="mt-2 text-white">El QR apunta a: <small><?php echo $url_registro; ?></small></div>
    </div>

    <div class="hoja-a4">
        <div class="titulo">¡REGISTRATE<br>EN EL CLUB!</div>
        <div class="subtitulo">Sumá puntos y accedé a descuentos exclusivos</div>
        
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?php echo urlencode($url_registro); ?>" class="qr-img" alt="QR Registro">
        </div>

        <div class="instruccion">ESCANEA CON TU CELULAR 📸</div>
        
        <div class="mt-5 text-muted fw-bold">NO HACE FALTA DESCARGAR NADA</div>
    </div>

</body>
</html>