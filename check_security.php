<?php
// includes/check_security.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php'; // Asegurate que la ruta a db.php sea correcta según donde lo incluyas

if (isset($_SESSION['usuario_id'])) {
    // Verificar si el usuario tiene orden de bloqueo
    $stmt = $conexion->prepare("SELECT forzar_logout FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $bloqueado = $stmt->fetchColumn();

    if ($bloqueado == 1) {
        // Ejecutar la orden de bloqueo
        session_destroy();
        header("Location: index.php?msg=bloqueado"); // Redirigir al login
        exit;
    }
}
?>
