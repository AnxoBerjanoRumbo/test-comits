<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nueva_password = $_POST['nueva_password'];

    try {
        $sql = "UPDATE usuarios SET password = :password WHERE id = :id AND rol = 'admin'";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':password' => password_hash($nueva_password, PASSWORD_DEFAULT),
            ':id' => $id_usuario
        ]);

        header("Location: panel_superadmin.php?status=actualizado");
        exit();

    } catch (PDOException $e) {
        error_log("Error al actualizar contraseña: " . $e->getMessage());
        header("Location: panel_superadmin.php?error=interno");
        exit();
    }
} else {
    header("Location: panel_superadmin.php");
    exit();
}
?>
