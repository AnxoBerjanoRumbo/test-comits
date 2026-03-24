<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit();
}
include '../config/db.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$dino_id = (int)$_POST['dino_id'];
$texto = trim($_POST['texto']);
$usuario_id = $_SESSION['usuario_id'];

$respuesta_a = (!empty($_POST['respuesta_a'])) ? (int)$_POST['respuesta_a'] : null;
$texto = mb_substr($texto, 0, 10000);

if (!empty($texto) && !empty($dino_id)) {
    try {
        $stmt = $conexion->prepare("INSERT INTO comentarios (texto, usuario_id, dino_id, respuesta_a) VALUES (:texto, :u_id, :d_id, :resp_a)");
        $stmt->execute([':texto' => $texto, ':u_id' => $usuario_id, ':d_id' => $dino_id, ':resp_a' => $respuesta_a]);
    }
    catch (PDOException $e) {
        error_log("Error al insertar comentario: " . $e->getMessage());
    }
}

header("Location: ../detalle.php?id=" . $dino_id . "#comentarios");
exit();
?>
