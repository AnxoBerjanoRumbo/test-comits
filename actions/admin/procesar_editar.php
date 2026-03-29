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
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $especie = $_POST['especie'];
    $dieta = $_POST['dieta'];
    $descripcion = $_POST['descripcion'];
    $mapas_ids = isset($_POST['mapas']) ? $_POST['mapas'] : [];

    // Obtener imagen actual por si no suben una nueva
    $sql_actual = "SELECT imagen FROM dinosaurios WHERE id = :id";
    $stmt_actual = $conexion->prepare($sql_actual);
    $stmt_actual->execute([':id' => $id]);
    $dino_actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
    $imagen = $dino_actual['imagen'] ?? 'default_dino.jpg';

    // Subida de nueva imagen con validación de seguridad
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        include_once '../../config/cloudinary_helper.php';
        $resultado = gestionarSubidaImagen($_FILES['imagen'], 'dinos', '../../assets/img/dinos/', 'dino_');

        if ($resultado) {
            // Borrar foto anterior (local o Cloudinary) si no es la default
            if ($imagen && $imagen !== 'default_dino.jpg') {
                if (strpos($imagen, 'http') !== false) {
                    eliminarImagenDeCloudinary($imagen);
                }
                else {
                    $old_path = '../../assets/img/dinos/' . $imagen;
                    if (file_exists($old_path)) unlink($old_path);
                }
            }
            $imagen = $resultado;
        } else {
            header("Location: ../../admin/editar.php?id=" . $id . "&error=formato");
            exit();
        }
    }


    try {
        $conexion->beginTransaction();

        // Actualizar en tabla 'dinosaurios'
        $sqlUpdate = "UPDATE dinosaurios SET nombre = :n, especie = :e, dieta = :d, descripcion = :desc, imagen = :img WHERE id = :id";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->execute([':n' => $nombre, ':e' => $especie, ':d' => $dieta, ':desc' => $descripcion, ':img' => $imagen, ':id' => $id]);

        // Actualizar tabla intermedia 'dino_mapas'
        // Lo más seguro es borrar y volver a insertar
        $stmtDelMap = $conexion->prepare("DELETE FROM dino_mapas WHERE dino_id = :id");
        $stmtDelMap->execute([':id' => $id]);

        if (!empty($mapas_ids)) {
            $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
            $stmtMapa = $conexion->prepare($sqlMapa);
            foreach ($mapas_ids as $m_id) {
                $stmtMapa->execute([':dino_id' => $id, ':mapa_id' => $m_id]);
            }
        }

        $conexion->commit();
        header("Location: ../../detalle.php?id=" . $id . "&status=edit_success");
        exit();

    }
    catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error al editar: " . $e->getMessage());
        header("Location: ../../admin/editar.php?id=" . $id . "&error=interno");
        exit();
    }
}
else {
    header("Location: ../../index.php");
    exit();
}
