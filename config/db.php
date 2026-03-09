<?php
$host = "localhost";
$db_name = "ark_hub";
$username = "root";
$password = "";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>