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
    $mapas_ids = isset($_POST['mapas'])      ? $_POST['mapas']      : [];
    $cats_ids  = isset($_POST['categorias']) ? $_POST['categorias'] : [];

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
        $sqlUpdate = "UPDATE dinosaurios SET nombre = :n, especie = :e, dieta = :d, descripcion = :desc, imagen = :img,
            stat_health = :sh, stat_stamina = :ss, stat_oxygen = :so, stat_food = :sf,
            stat_weight = :sw, stat_melee = :sm, stat_speed = :sp, stat_torpidity = :st
            WHERE id = :id";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':n' => $nombre, ':e' => $especie, ':d' => $dieta, ':desc' => $descripcion, ':img' => $imagen, ':id' => $id,
            ':sh' => (int)($_POST['stat_health']    ?? 0),
            ':ss' => (int)($_POST['stat_stamina']   ?? 0),
            ':so' => (int)($_POST['stat_oxygen']    ?? 0),
            ':sf' => (int)($_POST['stat_food']      ?? 0),
            ':sw' => (int)($_POST['stat_weight']    ?? 0),
            ':sm' => (int)($_POST['stat_melee']     ?? 0),
            ':sp' => (int)($_POST['stat_speed']     ?? 0),
            ':st' => (int)($_POST['stat_torpidity'] ?? 0),
        ]);

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

        // Actualizar categorías
        $stmtDelCat = $conexion->prepare("DELETE FROM dino_categorias WHERE dino_id = :id");
        $stmtDelCat->execute([':id' => $id]);

        if (!empty($cats_ids)) {
            $sqlCat = "INSERT INTO dino_categorias (dino_id, categoria_id) VALUES (:dino_id, :cat_id)";
            $stmtCat = $conexion->prepare($sqlCat);
            foreach ($cats_ids as $c_id) {
                $stmtCat->execute([':dino_id' => $id, ':cat_id' => (int)$c_id]);
            }
        }

        // Sistema de Notificaciones
        include_once '../../config/notificaciones.php';
        $nick_admin = htmlspecialchars($_SESSION['nick'] ?? 'Un administrador');
        $mensaje_notif = "Información actualizada: " . htmlspecialchars($nombre);
        $enlace_notif = "detalle.php?id=" . $id;
        // Solo notificar a usuarios normales
        notificarPorRol($conexion, ['usuario'], $mensaje_notif, $enlace_notif, $_SESSION['usuario_id']);

        // Notificar a admins compañeros (no al autor)
        $msg_admins = "{$nick_admin} ha editado el dinosaurio: " . htmlspecialchars($nombre);
        notificarPorRol($conexion, ['admin'], $msg_admins, $enlace_notif, $_SESSION['usuario_id']);

        // Log admin
        include_once '../../config/admin_logger.php';
        registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Editar Dino', "Dinosaurio editado: " . htmlspecialchars($nombre));

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
