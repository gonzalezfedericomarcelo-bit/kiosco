<?php
// public_html/kioscos/includes/db.php

// DATOS DE CONEXIÓN (Cámbialos por los tuyos reales)
$host = "localhost"; 
$usuario = "u415354546_kiosco"; 
$password = "Brg13abr"; 
$base_datos = "u415354546_kiosco"; 

try {
    $conexion = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8mb4", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    // Si falla la conexión, mostrará el error en pantalla
    die("Error de Conexión a la Base de Datos: " . $e->getMessage());
}

// Configuración de hora Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>