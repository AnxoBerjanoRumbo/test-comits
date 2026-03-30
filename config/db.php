<?php
$host = "localhost";
$db_name = "ark_hub";
$username = "root";
$password = "";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error interno del servidor. Por favor, inténtelo de nuevo más tarde.");
}

if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>