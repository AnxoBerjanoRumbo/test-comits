<?php
session_start();
include '../config/db.php';

// Borrar todas las notificaciones del usuario al cerrar sesión
if (isset($_SESSION['usuario_id'])) {
    try {
        $stmt = $conexion->prepare("DELETE FROM notificaciones WHERE id_usuario = :u");
        $stmt->execute([':u' => $_SESSION['usuario_id']]);
    } catch (PDOException $e) {
        error_log("Error al limpiar notificaciones: " . $e->getMessage());
    }
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
header("Location: ../index.php");
exit();
