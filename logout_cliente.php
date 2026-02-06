<?php
session_start();
// Borramos solo variables de cliente
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_nombre']);
unset($_SESSION['cliente_puntos']);
unset($_SESSION['cliente_foto']);

// Redirigir
header("Location: tienda.php");
exit;
?>