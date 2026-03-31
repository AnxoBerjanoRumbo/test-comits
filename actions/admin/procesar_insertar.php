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
    $cats_ids  = isset($_POST['categorias']) ? $_POST['categorias'] : [];
    
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
        $sqlDino = "INSERT INTO dinosaurios (nombre, especie, dieta, descripcion, imagen, stat_health, stat_stamina, stat_oxygen, stat_food, stat_weight, stat_melee, stat_speed, stat_torpidity) VALUES (:n, :e, :d, :desc, :img, :sh, :ss, :so, :sf, :sw, :sm, :sp, :st)";
        $stmtDino = $conexion->prepare($sqlDino);
        $stmtDino->execute([
            ':n' => $nombre, ':e' => $especie, ':d' => $dieta, ':desc' => $descripcion, ':img' => $imagen,
            ':sh' => (int)($_POST['stat_health']   ?? 0),
            ':ss' => (int)($_POST['stat_stamina']  ?? 0),
            ':so' => (int)($_POST['stat_oxygen']   ?? 0),
            ':sf' => (int)($_POST['stat_food']     ?? 0),
            ':sw' => (int)($_POST['stat_weight']   ?? 0),
            ':sm' => (int)($_POST['stat_melee']    ?? 0),
            ':sp' => (int)($_POST['stat_speed']    ?? 0),
            ':st' => (int)($_POST['stat_torpidity']?? 0),
        ]);

        // Obtener el ID del dinosaurio que acabamos de crear
        $dino_id = $conexion->lastInsertId();

        // Insertar en la tabla intermedia 'dino_mapas' (Múltiples mapas)
        // Insertar mapas
        if (!empty($mapas_ids)) {
            $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
            $stmtMapa = $conexion->prepare($sqlMapa);
            foreach ($mapas_ids as $m_id) {
                $stmtMapa->execute([':dino_id' => $dino_id, ':mapa_id' => $m_id]);
            }
        }

        // Insertar categorías
        if (!empty($cats_ids)) {
            $sqlCat = "INSERT INTO dino_categorias (dino_id, categoria_id) VALUES (:dino_id, :cat_id)";
            $stmtCat = $conexion->prepare($sqlCat);
            foreach ($cats_ids as $c_id) {
                $stmtCat->execute([':dino_id' => $dino_id, ':cat_id' => (int)$c_id]);
            }
        }

        // Sistema de Notificaciones
        include_once '../../config/notificaciones.php';
        $nick_admin = htmlspecialchars($_SESSION['nick'] ?? 'Un administrador');
        $mensaje_notif = "Nuevo dinosaurio añadido: " . htmlspecialchars($nombre);
        $enlace_notif = "detalle.php?id=" . $dino_id;
        // Solo notificar a usuarios normales
        notificarPorRol($conexion, ['usuario'], $mensaje_notif, $enlace_notif, $_SESSION['usuario_id']);

        // Notificar a admins peers (no al autor) sobre la nueva incorporación
        $msg_admins = "{$nick_admin} ha añadido el dinosaurio: " . htmlspecialchars($nombre);
        notificarPorRol($conexion, ['admin'], $msg_admins, $enlace_notif, $_SESSION['usuario_id']);

        // Log admin
        include_once '../../config/admin_logger.php';
        registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Añadir Dino', "Dinosaurio añadido: " . htmlspecialchars($nombre));

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