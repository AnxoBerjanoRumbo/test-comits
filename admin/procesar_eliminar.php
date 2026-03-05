<?php
session_start();
include '../config/db.php';

// Verificación de seguridad: ¿Es el admin?
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../index.php");
    exit();
}

// Comprobamos que nos llega el ID del dinosaurio vía POST y el CSRF
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $id = $_POST['id'];

    try {
        $conexion->beginTransaction();

        // 1. Primero borramos las relaciones en la tabla intermedia
        $sqlMapas = "DELETE FROM dino_mapas WHERE dino_id = :id";
        $stmtMapas = $conexion->prepare($sqlMapas);
        $stmtMapas->execute([':id' => $id]);

        // 2. Luego borramos el dinosaurio de la tabla principal
        $sqlDino = "DELETE FROM dinosaurios WHERE id = :id";
        $stmtDino = $conexion->prepare($sqlDino);
        $stmtDino->execute([':id' => $id]);

        $conexion->commit();

        header("Location: ../index.php?status=deleted");
        exit();

    }
    catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error al eliminar: " . $e->getMessage());
        header("Location: ../index.php?error=interno");
        exit();
    }
}
else {
    header("Location: ../index.php");
    exit();
}