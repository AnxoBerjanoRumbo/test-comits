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
    $nueva_password = $_POST['nueva_password'];

    if (empty($nueva_password)) {
        header("Location: panel_superadmin.php?error=vacio");
        exit();
    }

    try {
        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizamos la contraseña y nos aseguramos de que el rol sea admin
        $sql = "UPDATE usuarios SET password = :pass, rol = 'admin' WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':pass' => $hash,
            ':id'   => $id_usuario
        ]);

        header("Location: panel_superadmin.php?status=actualizado");
        exit();

    } catch (PDOException $e) {
        error_log("Error al activar admin: " . $e->getMessage());
        header("Location: panel_superadmin.php?error=db");
        exit();
    }
} else {
    header("Location: panel_superadmin.php");
    exit();
}
?>
