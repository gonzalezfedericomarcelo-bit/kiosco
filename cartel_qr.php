<?php
// cartel_qr.php - DISEÑO MOCKUP CELULAR
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$url_registro = $protocolo . "://" . $_SERVER['HTTP_HOST'] . str_replace('/cartel_qr.php', '/registro_cliente.php', $_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cartel QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #333; font-family: 'Segoe UI', system-ui, sans-serif; }
        .hoja-a4 {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            width: 210mm; height: 297mm;
            margin: 20px auto; padding: 0;
            position: relative; overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        
        /* Círculos decorativos de fondo */
        .deco-circle { position: absolute; border-radius: 50%; opacity: 0.1; }
        .c1 { width: 300px; height: 300px; background: #0d6efd; top: -50px; left: -50px; }
        .c2 { width: 500px; height: 500px; background: #198754; bottom: -100px; right: -100px; }

        .contenido { position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }

        .phone-frame {
            border: 12px solid #333; border-radius: 40px;
            padding: 20px; background: white;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative; margin: 30px 0;
        }
        .phone-notch {
            width: 120px; height: 20px; background: #333;
            border-radius: 0 0 15px 15px; position: absolute;
            top: -12px; left: 50%; transform: translateX(-50%);
        }
        
        .titulo { font-size: 4rem; font-weight: 900; color: #212529; text-transform: uppercase; line-height: 0.9; }
        .destacado { color: #0d6efd; }
        .bajada { font-size: 1.5rem; color: #6c757d; margin-top: 10px; font-weight: 500; }
        
        .qr-img { width: 350px; height: 350px; mix-blend-mode: multiply; }
        
        .call-to-action {
            background: #212529; color: white;
            padding: 15px 50px; border-radius: 50px;
            font-size: 1.8rem; font-weight: bold;
            margin-top: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        @media print {
            body { background: white; margin: 0; }
            .hoja-a4 { margin: 0; box-shadow: none; width: 100%; height: 100vh; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="text-center mt-3 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-warning fw-bold btn-lg shadow">🖨️ IMPRIMIR</button>
        <a href="clientes.php" class="btn btn-outline-light ms-2">Volver</a>
    </div>

    <div class="hoja-a4">
        <div class="deco-circle c1"></div>
        <div class="deco-circle c2"></div>
        
        <div class="contenido">
            <div class="titulo">Sumate al<br><span class="destacado">CLUB</span></div>
            <p class="bajada">Acumulá puntos y canjealos por premios</p>

            <div class="phone-frame">
                <div class="phone-notch"></div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?php echo urlencode($url_registro); ?>" class="qr-img" alt="QR">
                <div class="mt-2 fw-bold text-muted small">kiosco-app.com</div>
            </div>

            <div class="call-to-action">
                <i class="bi bi-camera-fill me-2"></i> ESCANEÁ EL QR
            </div>
            
            <div class="mt-4 text-muted fw-bold">
                <i class="bi bi-phone"></i> No necesitás descargar nada
            </div>
        </div>
    </div>

</body>
</html>