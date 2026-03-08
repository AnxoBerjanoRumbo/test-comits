<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $id_usuario = (int)$_POST['id_usuario'];

    try {
        // Eliminamos la solicitud pendiente. No usamos AND password = '' porque en
        // algunos entornos el campo puede estar almacenado como NULL en vez de '',
        // lo que haría que el DELETE fallara silenciosamente y el nick quedara bloqueado.
        $sql = "DELETE FROM usuarios WHERE id = :id AND rol = 'admin'";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id' => $id_usuario]);

        header("Location: panel_superadmin.php?status=cancelado");
        exit();

    } catch (PDOException $e) {
        error_log("Error al cancelar la solicitud: " . $e->getMessage());
        header("Location: panel_superadmin.php?error=interno");
        exit();
    }
} else {
    header("Location: panel_superadmin.php");
    exit();
}
?>
