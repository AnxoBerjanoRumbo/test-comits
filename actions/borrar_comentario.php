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

$comentario_id = (int)$_POST['comentario_id'];
$dino_id = (int)$_POST['dino_id'];

try {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_eliminar'] ?? 0) == 1) {
        // Borrar primero los hijos (respuestas)
        $stmt_h = $conexion->prepare("DELETE FROM comentarios WHERE respuesta_a = :id");
        $stmt_h->execute([':id' => $comentario_id]);
        
        // Borrar el original
        $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = :id");
        $stmt->execute([':id' => $comentario_id]);
    } else {
        // Un usuario normal solo puede borrar sus comentarios (y sus respuestas se borrarían si fuera admin, pero aquí solo borra el suyo)
        // Por seguridad, si un usuario borra su comentario, borramos también las respuestas que pudiera tener
        $stmt_h = $conexion->prepare("DELETE FROM comentarios WHERE respuesta_a = :id");
        $stmt_h->execute([':id' => $comentario_id]);

        $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = :id AND usuario_id = :u_id");
        $stmt->execute([':id' => $comentario_id, ':u_id' => $_SESSION['usuario_id']]);
    }
} catch(PDOException $e) {
    error_log("Error al borrar comentario: " . $e->getMessage());
}

header("Location: ../detalle.php?id=" . $dino_id);
exit();
?>
