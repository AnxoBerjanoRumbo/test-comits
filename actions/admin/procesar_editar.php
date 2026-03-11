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
    $mapa_id = $_POST['mapa_id'];
    
    // Obtener imagen actual por si no suben una nueva
    $sql_actual = "SELECT imagen FROM dinosaurios WHERE id = :id";
    $stmt_actual = $conexion->prepare($sql_actual);
    $stmt_actual->execute([':id' => $id]);
    $dino_actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
    $imagen = $dino_actual['imagen'] ?? 'default_dino.jpg';

    // Subida de nueva imagen con validación de seguridad
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        $img_name = $_FILES['imagen']['name'];
        $img_tmp = $_FILES['imagen']['tmp_name'];
        
        $extension = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $validas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $validas)) {
            // Verificar si es una imagen real
            if (@getimagesize($img_tmp)) {
                
                // INTENTO DE SUBIDA A CLOUDINARY
                include_once '../../config/cloudinary_helper.php';
                $url_cloudinary = subirImagenACloudinary($img_tmp, 'dinos');

                if ($url_cloudinary) {
                    $nueva_imagen_valor = $url_cloudinary;
                } else {
                    // FALLBACK: Almacenamiento local si Cloudinary no está configurado o falla
                    $nuevo_nombre = uniqid('dino_') . '.' . $extension;
                    $destino = '../../assets/img/dinos/' . $nuevo_nombre;
                    if (move_uploaded_file($img_tmp, $destino)) {
                        $nueva_imagen_valor = $nuevo_nombre;
                    } else {
                        header("Location: ../../admin/editar.php?id=" . $id . "&error=formato");
                        exit();
                    }
                }

                // Borrar foto anterior LOCAL si no es la default y no es ya una URL
                if ($imagen && $imagen !== 'default_dino.jpg' && strpos($imagen, 'http') === false) {
                    $old_path = '../../assets/img/dinos/' . $imagen;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                $imagen = $nueva_imagen_valor;

            } else {
                header("Location: ../../admin/editar.php?id=" . $id . "&error=formato");
                exit();
            }
        } else {
            // Si la extensión no es válida, redirigimos con error
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

        $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
        $stmtMapa = $conexion->prepare($sqlMapa);
        $stmtMapa->execute([':dino_id' => $id, ':mapa_id' => $mapa_id]);

        $conexion->commit();
        header("Location: ../../detalle.php?id=" . $id);
        exit();

    } catch (PDOException $e) {
        $conexion->rollBack();
        error_log("Error al editar: " . $e->getMessage());
        header("Location: ../../admin/editar.php?id=" . $id . "&error=interno");
        exit();
    }
} else {
    header("Location: ../../index.php");
    exit();
}
