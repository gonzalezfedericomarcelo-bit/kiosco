<?php
// generar_pdf.php - VERSIÓN FINAL CORREGIDA (FIRMAS APOYADAS Y ESPACIADO SEGURO)
require('fpdf/fpdf.php');
require_once 'includes/db.php';

// --- 1. RECEPCIÓN DE DATOS ---
$inicio = $_GET['f_inicio'] ?? date('Y-m-01');
$fin = $_GET['f_fin'] . " 23:59:59";
$id_usuario = $_GET['id_usuario'] ?? '';
$metodo = $_GET['metodo'] ?? '';
$cliente = $_GET['cliente'] ?? '';

// --- 2. CONSULTA SQL ---
$sql = "SELECT v.*, u.usuario, c.nombre as cliente 
        FROM ventas v 
        JOIN usuarios u ON v.id_usuario = u.id 
        JOIN clientes c ON v.id_cliente = c.id
        WHERE v.fecha BETWEEN '$inicio' AND '$fin' AND v.estado = 'completada'";

if ($id_usuario) $sql .= " AND v.id_usuario = $id_usuario";
if ($metodo) $sql .= " AND v.metodo_pago = '$metodo'";
if ($cliente) $sql .= " AND c.nombre LIKE '%$cliente%'";

$sql .= " ORDER BY v.fecha DESC";
$ventas = $conexion->query($sql)->fetchAll();

// --- 3. CLASE PDF ---
class PDF extends FPDF {
    function Header() {
        // FONDO MEMBRETE
        $this->SetFillColor(245, 245, 245);
        $this->Rect(0, 0, 210, 50, 'F');

        // LOGO
        $ruta_logo = 'img/logo.png';
        if(file_exists($ruta_logo)) {
            $this->Image($ruta_logo, 10, 10, 30); 
        } else {
            $this->SetFillColor(220, 220, 220);
            $this->Rect(10, 10, 30, 30, 'F');
            $this->SetXY(10, 20);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(30, 5, 'SIN LOGO', 0, 0, 'C');
        }

        // DATOS EMPRESA
        $this->SetXY(110, 10);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(33, 37, 41);
        $this->Cell(90, 8, utf8_decode('KIOSCO MANAGER PRO'), 0, 1, 'R');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(190, 5, utf8_decode('CUIT: 30-12345678-9 | CM: 555-AG'), 0, 1, 'R');
        $this->Cell(190, 5, utf8_decode('Av. Siempre Viva 742, Buenos Aires'), 0, 1, 'R');

        // TÍTULO
        $this->SetY(40);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, utf8_decode('REPORTE DETALLADO DE VENTAS'), 0, 1, 'C');
        $this->Ln(5);

        // ENCABEZADOS
        $this->SetFillColor(33, 37, 41);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 8, 'ID', 0, 0, 'C', true);
        $this->Cell(35, 8, 'Fecha', 0, 0, 'C', true);
        $this->Cell(35, 8, 'Cajero', 0, 0, 'C', true);
        $this->Cell(50, 8, 'Cliente', 0, 0, 'L', true);
        $this->Cell(25, 8, 'Metodo', 0, 0, 'C', true);
        $this->Cell(25, 8, 'Total', 0, 1, 'R', true);
    }

    function Footer() {
        $this->SetY(-35);
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 260, 210, 40, 'F');
        // QR (API)
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=KioscoManager_Validado";
        $this->Image($qr_url, 10, 265, 25, 0, 'PNG');
        // LEGALES
        $this->SetXY(40, 265);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 5, utf8_decode('DOCUMENTO GENERADO AUTOMÁTICAMENTE'), 0, 1, 'L');
        $this->SetX(40);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->MultiCell(100, 3, utf8_decode("Comprobante interno de gestión.\nValidez fiscal sujeta a factura electrónica."), 0, 'L');
        // PÁGINA
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

// --- 4. GENERACIÓN ---
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$total = 0;
$fill = false;

foreach ($ventas as $v) {
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Cell(20, 7, str_pad($v->id, 5, '0', STR_PAD_LEFT), 0, 0, 'C', $fill);
    $pdf->Cell(35, 7, date('d/m/y H:i', strtotime($v->fecha)), 0, 0, 'C', $fill);
    $pdf->Cell(35, 7, utf8_decode(substr($v->usuario,0,15)), 0, 0, 'C', $fill);
    $pdf->Cell(50, 7, utf8_decode(substr($v->cliente,0,22)), 0, 0, 'L', $fill);
    $pdf->Cell(25, 7, $v->metodo_pago, 0, 0, 'C', $fill);
    $pdf->Cell(25, 7, '$ ' . number_format($v->total, 2), 0, 1, 'R', $fill);
    $total += $v->total;
    $fill = !$fill;
}

$pdf->Cell(190, 0, '', 'T');
$pdf->Ln(5);

// TOTALES
$pdf->SetX(130);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(35, 10, 'TOTAL FINAL:', 1, 0, 'R', true);
$pdf->Cell(25, 10, '$ ' . number_format($total, 2), 1, 1, 'R', true);

// --- 5. ZONA DE FIRMAS (LÓGICA CORREGIDA) ---
// 1. Forzamos un salto de línea grande para separarnos del Total
$pdf->Ln(30); 

// 2. Verificamos si queda espacio en la hoja (menos de 60mm). Si no, nueva página.
if($pdf->GetY() > 220) {
    $pdf->AddPage();
    $pdf->Ln(20); // Margen superior en nueva página
}

// 3. Guardamos la posición Y donde irá la LÍNEA de firma
$y_linea = $pdf->GetY() + 25; 

// A. FIRMA DUEÑO (DERECHA)
$ruta_firma_admin = 'img/firmas/firma_admin.png';
if (file_exists($ruta_firma_admin)) {
    // Calculamos Y para la imagen: Y_linea - Altura_Imagen + Ajuste visual
    // Usamos altura fija de 25mm para que no sea gigante
    $pdf->Image($ruta_firma_admin, 125, $y_linea - 25, 50, 25); 
}

// Dibujar línea (Siempre en y_linea)
$pdf->SetXY(120, $y_linea);
$pdf->Cell(60, 0, '', 'T'); 
$pdf->Ln(2);
$pdf->SetX(120);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(60, 5, utf8_decode('FIRMA RESPONSABLE / DUEÑO'), 0, 1, 'C');

// B. FIRMA EMPLEADO (IZQUIERDA)
if (!empty($id_usuario)) {
    $ruta_firma_user = "img/firmas/usuario_{$id_usuario}.png";
    if (file_exists($ruta_firma_user)) {
        // Misma lógica: Y_linea - 25
        $pdf->Image($ruta_firma_user, 35, $y_linea - 25, 50, 25);
    }
    
    // Volvemos a posicionarnos en la línea
    $pdf->SetXY(30, $y_linea);
    $pdf->Cell(60, 0, '', 'T');
    $pdf->Ln(2);
    $pdf->SetX(30);
    $pdf->Cell(60, 5, 'FIRMA CAJERO', 0, 1, 'C');
}

$pdf->Output('I', 'Reporte_Ventas_Pro.pdf');
?>