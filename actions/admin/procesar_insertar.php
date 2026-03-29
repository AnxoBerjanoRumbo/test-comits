<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../../index.php");
    exit();
}
include '../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $nombre = trim($_POST['nombre']);
    $especie = $_POST['especie'];
    $dieta = $_POST['dieta'];
    $descripcion = $_POST['descripcion'];
    $mapas_ids = isset($_POST['mapas']) ? $_POST['mapas'] : [];
    
    // Subida de imagen con validación de seguridad
    $imagen = 'default_dino.jpg'; // por si falla
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        include_once '../../config/cloudinary_helper.php';
        $resultado = gestionarSubidaImagen($_FILES['imagen'], 'dinos', '../../assets/img/dinos/', 'dino_');
        if ($resultado) {
            $imagen = $resultado;
        } else {
            header("Location: ../../admin/insertar.php?error=formato");
            exit();
        }
    }


    try {

        // Validación de duplicados
        $check = $conexion->prepare("SELECT COUNT(*) FROM dinosaurios WHERE nombre = :nombre");
        $check->execute([':nombre' => $nombre]);

        if ($check->fetchColumn() > 0) {
            header("Location: ../../admin/insertar.php?error=duplicado&nombre=" . urlencode($nombre));
            exit();
        }

        $conexion->beginTransaction();

        // Insertar en tabla 'dinosaurios'
        $sqlDino = "INSERT INTO dinosaurios (nombre, especie, dieta, descripcion, imagen) VALUES (:n, :e, :d, :desc, :img)";
        $stmtDino = $conexion->prepare($sqlDino);
        $stmtDino->execute([':n' => $nombre, ':e' => $especie, ':d' => $dieta, ':desc' => $descripcion, ':img' => $imagen]);

        // Obtener el ID del dinosaurio que acabamos de crear
        $dino_id = $conexion->lastInsertId();

        // Insertar en la tabla intermedia 'dino_mapas' (Múltiples mapas)
        if (!empty($mapas_ids)) {
            $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
            $stmtMapa = $conexion->prepare($sqlMapa);
            foreach ($mapas_ids as $m_id) {
                $stmtMapa->execute([':dino_id' => $dino_id, ':mapa_id' => $m_id]);
            }
        }

        $conexion->commit();
        header("Location: ../../index.php?status=success");
        exit();

    }
    catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error al insertar: " . $e->getMessage());
        header("Location: ../../admin/insertar.php?error=interno");
        exit();
    }
}
else {
    header("Location: ../../admin/insertar.php");
    exit();
}