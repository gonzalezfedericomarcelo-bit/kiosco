<?php
// generar_backup.php - SISTEMA DE RESPALDO DE BASE DE DATOS
session_start();

// 1. VERIFICACIÓN DE SEGURIDAD
if (!isset($_SESSION['usuario_id'])) { 
    die("Acceso denegado."); 
}

// Buscador de conexión estándar (usamos el mismo que en tus otros archivos)
$rutas_db = [__DIR__ . '/db.php', __DIR__ . '/includes/db.php', 'db.php', 'includes/db.php'];
$db_encontrada = false;
foreach ($rutas_db as $ruta) { 
    if (file_exists($ruta)) { 
        require_once $ruta; 
        $db_encontrada = true;
        break; 
    } 
}

if (!$db_encontrada) {
    die("Error: No se pudo encontrar el archivo de conexión db.php");
}

try {
    // 2. OBTENER TODAS LAS TABLAS
    $tables = [];
    $result = $conexion->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $return = "-- Backup generado el: " . date('Y-m-d H:i:s') . "\n";
    $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // 3. RECORRER CADA TABLA PARA EXTRAER ESTRUCTURA Y DATOS
    foreach ($tables as $table) {
        // Estructura de la tabla (CREATE TABLE)
        $result = $conexion->query("SHOW CREATE TABLE $table");
        $row = $result->fetch(PDO::FETCH_NUM);
        $return .= "\n\n" . $row[1] . ";\n\n";

        // Datos de la tabla
        $result = $conexion->query("SELECT * FROM $table");
        $num_fields = $result->columnCount();

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $return .= '"' . $row[$j] . '"';
                } else {
                    $return .= 'NULL';
                }
                if ($j < ($num_fields - 1)) {
                    $return .= ',';
                }
            }
            $return .= ");\n";
        }
    }

    $return .= "\nSET FOREIGN_KEY_CHECKS=1;";

    // 4. CONFIGURAR CABECERAS PARA DESCARGA AUTOMÁTICA
    $fecha = date("Y-m-d_H-i-s");
    $nombre_archivo = "backup_sistema_" . $fecha . ".sql";

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $nombre_archivo . "\"");
    
    echo $return;
    exit;

} catch (Exception $e) {
    die("Error al generar el backup: " . $e->getMessage());
}
?>