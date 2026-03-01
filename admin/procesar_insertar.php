<?php
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $especie = $_POST['especie'];
    $dieta = $_POST['dieta'];
    $mapa_id = $_POST['mapa_id'];

    try {

        // Insertar en tabla 'dinosaurios'
        $sqlDino = "INSERT INTO dinosaurios (nombre, especie, dieta) VALUES (:n, :e, :d)";
        $stmtDino = $conexion->prepare($sqlDino);
        $stmtDino->execute([':n' => $nombre, ':e' => $especie, ':d' => $dieta]);

        // Obtener el ID del dinosaurio que acabamos de crear
        $dino_id = $conexion->lastInsertId();

        // Insertar en la tabla intermedia 'dino_mapas' (según tu detalle.php)
        $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
        $stmtMapa = $conexion->prepare($sqlMapa);
        $stmtMapa->execute([':dino_id' => $dino_id, ':mapa_id' => $mapa_id]);

        $conexion->commit();
        header("Location: ../index.php?status=success");
        exit();

    } catch (PDOException $e) {
        $conexion->rollBack(); 
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: insertar.php");
    exit();
}