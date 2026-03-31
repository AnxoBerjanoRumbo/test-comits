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
        $sqlDino = "INSERT INTO dinosaurios (nombre, especie, dieta, descripcion, imagen, 
            stat_health, stat_stamina, stat_oxygen, stat_food, stat_weight, stat_melee, stat_speed, stat_torpidity,
            iw_health, iw_stamina, iw_oxygen, iw_food, iw_weight, iw_melee, iw_speed, iw_torpidity,
            es_tanque, es_buff, es_recolector, es_montura, es_volador, es_acuatico, es_subterraneo,
            buff_descripcion, buff_damage, buff_armor, buff_speed, buff_otro,
            tiene_formas, formas_descripcion,
            recolecta_carne, recolecta_pescado, recolecta_madera, recolecta_piedra, recolecta_metal, 
            recolecta_bayas, recolecta_paja, recolecta_fibra, recolecta_texugo,
            domable, metodo_domado, comida_favorita, nivel_max_salvaje,
            tiempo_incubacion, tiempo_madurez, ayuda_cria, ayuda_cria_descripcion
            ) VALUES (:n, :e, :d, :desc, :img, :sh, :ss, :so, :sf, :sw, :sm, :sp, :st,
            :iwh, :iws, :iwo, :iwf, :iww, :iwm, :iwsp, :iwt,
            :et, :eb, :er, :em, :ev, :ea, :es, :bd, :bda, :bar, :bs, :bo, :tf, :fd,
            :rc, :rpe, :rma, :rpi, :rme, :rba, :rpa, :rfi, :rte,
            :dom, :mdom, :cf, :nms, :ti, :tma, :ac, :acd)";
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
            ':iwh'  => (float)($_POST['iw_health']    ?? 0.2),
            ':iws'  => (float)($_POST['iw_stamina']   ?? 0.1),
            ':iwo'  => (float)($_POST['iw_oxygen']    ?? 0.1),
            ':iwf'  => (float)($_POST['iw_food']      ?? 0.15),
            ':iww'  => (float)($_POST['iw_weight']    ?? 0.02),
            ':iwm'  => (float)($_POST['iw_melee']     ?? 0.05),
            ':iwsp' => (float)($_POST['iw_speed']     ?? 0.0),
            ':iwt'  => (float)($_POST['iw_torpidity'] ?? 0.06),
            ':et' => isset($_POST['es_tanque']) ? 1 : 0,
            ':eb' => isset($_POST['es_buff']) ? 1 : 0,
            ':er' => isset($_POST['es_recolector']) ? 1 : 0,
            ':em' => isset($_POST['es_montura']) ? 1 : 0,
            ':ev' => isset($_POST['es_volador']) ? 1 : 0,
            ':ea' => isset($_POST['es_acuatico']) ? 1 : 0,
            ':es' => isset($_POST['es_subterraneo']) ? 1 : 0,
            ':bd' => $_POST['buff_descripcion'] ?? '',
            ':bda' => (int)($_POST['buff_damage'] ?? 0),
            ':bar' => (int)($_POST['buff_armor'] ?? 0),
            ':bs' => (int)($_POST['buff_speed'] ?? 0),
            ':bo' => $_POST['buff_otro'] ?? '',
            ':tf' => isset($_POST['tiene_formas']) ? 1 : 0,
            ':fd' => $_POST['formas_descripcion'] ?? '',
            ':rc' => isset($_POST['recolecta_carne']) ? 1 : 0,
            ':rpe' => isset($_POST['recolecta_pescado']) ? 1 : 0,
            ':rma' => isset($_POST['recolecta_madera']) ? 1 : 0,
            ':rpi' => isset($_POST['recolecta_piedra']) ? 1 : 0,
            ':rme' => isset($_POST['recolecta_metal']) ? 1 : 0,
            ':rba' => isset($_POST['recolecta_bayas']) ? 1 : 0,
            ':rpa' => isset($_POST['recolecta_paja']) ? 1 : 0,
            ':rfi' => isset($_POST['recolecta_fibra']) ? 1 : 0,
            ':rte' => isset($_POST['recolecta_texugo']) ? 1 : 0,
            ':dom' => (int)($_POST['domable'] ?? 1),
            ':mdom' => $_POST['metodo_domado'] ?? '',
            ':cf' => $_POST['comida_favorita'] ?? '',
            ':nms' => (int)($_POST['nivel_max_salvaje'] ?? 150),
            ':ti' => (int)($_POST['tiempo_incubacion'] ?? 0),
            ':tma' => (int)($_POST['tiempo_madurez'] ?? 0),
            ':ac' => isset($_POST['ayuda_cria']) ? 1 : 0,
            ':acd' => $_POST['ayuda_cria_descripcion'] ?? '',
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