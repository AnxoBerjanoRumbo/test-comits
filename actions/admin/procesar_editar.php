<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../../index.php");
    exit();
}
include '../../config/db.php';
include_once '../../config/verificar_sesion.php';
check_user_active_status($conexion, '../../login.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $id = (int)$_POST['id'];
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
        $sqlUpdate = "UPDATE dinosaurios SET 
            nombre = :n, especie = :e, dieta = :d, descripcion = :desc, imagen = :img, audio_url = :aud,
            stat_health = :sh, stat_stamina = :ss, stat_oxygen = :so, stat_food = :sf,
            stat_weight = :sw, stat_melee = :sm, stat_speed = :sp, stat_torpidity = :st,
            iw_health = :iwh, iw_stamina = :iws, iw_oxygen = :iwo, iw_food = :iwf,
            iw_weight = :iww, iw_melee = :iwm, iw_speed = :iwsp, iw_torpidity = :iwt,
            es_tanque = :et, es_buff = :eb, es_recolector = :er, es_montura = :em, 
            es_volador = :ev, es_acuatico = :ea, es_subterraneo = :es,
            buff_descripcion = :bd, buff_damage = :bda, buff_armor = :bar, buff_speed = :bs, buff_otro = :bo,
            tiene_formas = :tf, formas_descripcion = :fd,
            recolecta_carne = :rc, recolecta_pescado = :rpe, recolecta_madera = :rma, 
            recolecta_piedra = :rpi, recolecta_metal = :rme, recolecta_bayas = :rba, 
            recolecta_paja = :rpa, recolecta_fibra = :rfi, recolecta_texugo = :rte,
            domable = :dom, metodo_domado = :mdom, comida_favorita = :cf, nivel_max_salvaje = :nms,
            tiempo_incubacion = :ti, tiempo_madurez = :tma, ayuda_cria = :ac, ayuda_cria_descripcion = :acd,
            region_0_nombre = :r0n, region_0_colores = :r0c,
            region_1_nombre = :r1n, region_1_colores = :r1c,
            region_2_nombre = :r2n, region_2_colores = :r2c,
            region_3_nombre = :r3n, region_3_colores = :r3c,
            region_4_nombre = :r4n, region_4_colores = :r4c,
            region_5_nombre = :r5n, region_5_colores = :r5c
            WHERE id = :id";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':n' => $nombre, ':e' => $especie, ':d' => $dieta, ':desc' => $descripcion, ':img' => $imagen, ':aud' => $_POST['audio_url'] ?? null, ':id' => $id,
            ':sh' => (int)($_POST['stat_health']    ?? 0),
            ':ss' => (int)($_POST['stat_stamina']   ?? 0),
            ':so' => (int)($_POST['stat_oxygen']    ?? 0),
            ':sf' => (int)($_POST['stat_food']      ?? 0),
            ':sw' => (int)($_POST['stat_weight']    ?? 0),
            ':sm' => (int)($_POST['stat_melee']     ?? 0),
            ':sp' => (int)($_POST['stat_speed']     ?? 0),
            ':st' => (int)($_POST['stat_torpidity'] ?? 0),
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
            ':r0n' => $_POST['region_0_nombre']  ?? null, ':r0c' => $_POST['region_0_colores'] ?? null,
            ':r1n' => $_POST['region_1_nombre']  ?? null, ':r1c' => $_POST['region_1_colores'] ?? null,
            ':r2n' => $_POST['region_2_nombre']  ?? null, ':r2c' => $_POST['region_2_colores'] ?? null,
            ':r3n' => $_POST['region_3_nombre']  ?? null, ':r3c' => $_POST['region_3_colores'] ?? null,
            ':r4n' => $_POST['region_4_nombre']  ?? null, ':r4c' => $_POST['region_4_colores'] ?? null,
            ':r5n' => $_POST['region_5_nombre']  ?? null, ':r5c' => $_POST['region_5_colores'] ?? null,
        ]);

        // Actualizar tabla intermedia 'dino_mapas'
        // Lo más seguro es borrar y volver a insertar
        $stmtDelMap = $conexion->prepare("DELETE FROM dino_mapas WHERE dino_id = :id");
        $stmtDelMap->execute([':id' => $id]);

        if (!empty($mapas_ids)) {
            $sqlMapa = "INSERT INTO dino_mapas (dino_id, mapa_id) VALUES (:dino_id, :mapa_id)";
            $stmtMapa = $conexion->prepare($sqlMapa);
            foreach ($mapas_ids as $m_id) {
                $stmtMapa->execute([':dino_id' => $id, ':mapa_id' => (int)$m_id]);
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
