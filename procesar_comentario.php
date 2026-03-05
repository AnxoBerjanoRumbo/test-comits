<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}
include 'config/db.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$dino_id = $_POST['dino_id'];
$texto = trim($_POST['texto']);
$usuario_id = $_SESSION['usuario_id'];

// Limitar el texto aproximado a 10000 palabras (asumiendo 1 palabra = ~6 caracteres promedio)
$texto = substr($texto, 0, 60000);

if (!empty($texto) && !empty($dino_id)) {
    try {
        $stmt = $conexion->prepare("INSERT INTO comentarios (texto, usuario_id, dino_id) VALUES (:texto, :u_id, :d_id)");
        $stmt->execute([':texto' => $texto, ':u_id' => $usuario_id, ':d_id' => $dino_id]);
    } catch(PDOException $e) {
        // Si hay error, simplemente volverá al detalle
    }
}

header("Location: detalle.php?id=" . $dino_id);
exit();
?>
