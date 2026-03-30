<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit();
}
include '../config/db.php';
include_once '../config/verificar_sesion.php';
check_user_active_status($conexion);

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

// Honeypot: si este campo está relleno, es un bot
if (!empty($_POST['website_url'])) {
    error_log("Spam detectado de bot.");
    header("Location: ../detalle.php?id=" . (int)$_POST['dino_id']);
    exit();
}

$dino_id = (int)$_POST['dino_id'];
$texto = trim($_POST['texto']);
$usuario_id = $_SESSION['usuario_id'];

$respuesta_a = (!empty($_POST['respuesta_a'])) ? (int)$_POST['respuesta_a'] : null;
$texto = mb_substr($texto, 0, 10000);

if (!empty($texto) && !empty($dino_id)) {
    try {
        // Verificar jerarquía: si es una respuesta, el padre debe pertenecer al mismo dinosaurio
        if ($respuesta_a !== null) {
            // Solo los administradores pueden responder
            if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
                $respuesta_a = null;
            }
            else {
                $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM comentarios WHERE id = :p_id AND dino_id = :d_id");
                $stmt_check->execute([':p_id' => $respuesta_a, ':d_id' => $dino_id]);
                if ($stmt_check->fetchColumn() == 0) {
                    $respuesta_a = null; // Evita romper la visualización si el ID es falso o de otro dino
                }
            }
        }

        $stmt = $conexion->prepare("INSERT INTO comentarios (texto, usuario_id, dino_id, respuesta_a) VALUES (:texto, :u_id, :d_id, :resp_a)");
        $stmt->execute([':texto' => $texto, ':u_id' => $usuario_id, ':d_id' => $dino_id, ':resp_a' => $respuesta_a]);

        // Sistema de Notificaciones (si es una respuesta)
        if ($respuesta_a !== null) {
            $stmt_autor = $conexion->prepare("SELECT usuario_id FROM comentarios WHERE id = :id");
            $stmt_autor->execute([':id' => $respuesta_a]);
            $autor_original_id = $stmt_autor->fetchColumn();

            if ($autor_original_id && $autor_original_id != $usuario_id) {
                include_once '../config/notificaciones.php';
                $nick_resp = htmlspecialchars($_SESSION['nick']);
                $mensaje = "El admin " . $nick_resp . " ha respondido a tu comentario.";
                $enlace = "detalle.php?id=" . $dino_id . "#comentarios";
                añadirNotificacion($conexion, $autor_original_id, $mensaje, $enlace);
            }

            // Log admin: registro de comentario/respuesta
            include_once '../config/admin_logger.php';
            registrarAccionAdmin($conexion, $usuario_id, 'Responder Comentario', "Respuesta en dino ID {$dino_id} al comentario ID {$respuesta_a}");
        }
    }
    catch (PDOException $e) {
        error_log("Error al insertar comentario: " . $e->getMessage());
    }
}

header("Location: ../detalle.php?id=" . $dino_id . "#comentarios");
exit();
?>
