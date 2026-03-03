<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}
include 'config/db.php';

$comentario_id = $_POST['comentario_id'];
$dino_id = $_POST['dino_id'];

try {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = :id");
        $stmt->execute([':id' => $comentario_id]);
    } else {
        $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = :id AND usuario_id = :u_id");
        $stmt->execute([':id' => $comentario_id, ':u_id' => $_SESSION['usuario_id']]);
    }
} catch(PDOException $e) {
    // Si hay error saltará aquí silenciosamente
}

header("Location: detalle.php?id=" . $dino_id);
exit();
?>
