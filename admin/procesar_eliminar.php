<?php
session_start();
include '../config/db.php';

// Verificación de seguridad: ¿Es el admin?
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Comprobamos que nos llega el ID del dinosaurio a borrar
if (isset($_GET['id'])) {
    $id = $_GET['id'];

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
        echo "Error al eliminar: " . $e->getMessage();
    }
}
else {
    header("Location: ../index.php");
    exit();
}