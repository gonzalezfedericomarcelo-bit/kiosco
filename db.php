<?php
// public_html/kioscos/includes/db.php

// 1. Configurar la hora de Argentina para PHP (Esto no requiere base de datos)
date_default_timezone_set('America/Argentina/Buenos_Aires');

// DATOS DE CONEXIÓN
$host = "localhost"; 
$usuario = "u415354546_kiosco"; 
$password = "Brg13abr"; 
$base_datos = "u415354546_kiosco"; 

try {
    // 2. Crear la conexión primero
    $conexion = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8mb4", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    // 3. AHORA SÍ, configurar la hora en la Base de Datos (Ya que $conexion existe)
    $conexion->exec("SET time_zone = '-03:00'");

} catch (PDOException $e) {
    die("Error de Conexión a la Base de Datos: " . $e->getMessage());
}
?>