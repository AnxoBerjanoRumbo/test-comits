<?php
session_start();
include '../../config/db.php';

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['p_moderar']) || $_SESSION['p_moderar'] != 1) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $id_moderado = $_POST['usuario_id'];
    $motivo = trim($_POST['motivo']);
    $tipo = $_POST['tipo_ban'];

    // Obtener datos del usuario a moderar
    $stmt = $conexion->prepare("SELECT id, email, rol, foto_perfil FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id_moderado]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['rol'] === 'superadmin') {
        header("Location: ../../index.php?error=no_moderar");
        exit();
    }

    try {
        if ($tipo === 'quitar') {
            $sql = "UPDATE usuarios SET baneado_hasta = NULL, motivo_ban = NULL, ban_permanente = 0 WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id' => $id_moderado]);

            include_once '../../config/mailer.php';
            $cuerpo = "<h3>Hola " . htmlspecialchars($user['email']) . ",</h3>
                       <p>Buenas noticias: Se han levantado todas tus restricciones en ARK Hub.</p>
                       <p>Ya puedes volver a participar en la comunidad.</p>";
            sendArkEmail($user['email'], "Sanción levantada - ARK Hub", $cuerpo);

            include_once '../../config/notificaciones.php';
            añadirNotificacion($conexion, $id_moderado, "Tus restricciones han sido levantadas. ¡Bienvenido de nuevo!");

            include_once '../../config/admin_logger.php';
            registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Levantar Sanción', "Se levantó la sanción al usuario ID {$id_moderado} ({$user['email']})");

            header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&status=quitado");
            exit();
        }

        if ($tipo === 'expulsion') {
            include_once '../../config/cloudinary_helper.php';

            // 1. Bloquear Email
            $sql_bloqueo = "INSERT IGNORE INTO emails_bloqueados (email, motivo) VALUES (:email, :motivo)";
            $stmt_b = $conexion->prepare($sql_bloqueo);
            $stmt_b->execute([':email' => $user['email'], ':motivo' => $motivo]);

            // 2. Borrar Foto de Perfil (Local o Cloudinary)
            $foto = $user['foto_perfil'];
            if ($foto && $foto !== 'default.png') {
                if (strpos($foto, 'http') !== false) {
                    eliminarImagenDeCloudinary($foto);
                }
                else {
                    $local_path = '../../assets/img/perfil/' . $foto;
                    if (file_exists($local_path))
                        unlink($local_path);
                }
            }

            // 3. Eliminar Comentarios del usuario y respuestas huérfanas
            $stmt_h = $conexion->prepare("DELETE c1 FROM comentarios c1 INNER JOIN comentarios c2 ON c1.respuesta_a = c2.id WHERE c2.usuario_id = :id");
            $stmt_h->execute([':id' => $id_moderado]);

            $stmt_comm = $conexion->prepare("DELETE FROM comentarios WHERE usuario_id = :id");
            $stmt_comm->execute([':id' => $id_moderado]);

            // 4. Eliminar Usuario
            $sql_del = "DELETE FROM usuarios WHERE id = :id";
            $stmt_d = $conexion->prepare($sql_del);
            $stmt_d->execute([':id' => $id_moderado]);

            include_once '../../config/mailer.php';
            $msg_motivo = !empty($motivo) ? "<p>Motivo proporcionado por moderación:</p>
                           <blockquote style='background:#f4f4f4; padding:10px; border-left:5px solid #ff4444;'>
                           " . nl2br(htmlspecialchars($motivo)) . "
                           </blockquote>" : "<p>El sistema de moderación ha decidido restringir tu acceso de forma permanente debido al incumplimiento de la normativa comunitaria.</p>";

            $cuerpo = "<h3>Hola " . htmlspecialchars($user['email']) . ",</h3>
                       <p>Lamentamos informarte de que has sido <strong>EXPULSADO PERMANENTEMENTE</strong> de ARK Hub.</p>
                       $msg_motivo
                       <p>Tu cuenta y la información asociada han sido eliminadas y tu email bloqueado.</p>";
            sendArkEmail($user['email'], "Aviso Crítico: Cuenta Expulsada - ARK Hub", $cuerpo);

            include_once '../../config/admin_logger.php';
            registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Expulsión Total', "Usuario ID {$id_moderado} ({$user['email']}) expulsado y bloqueado su email. Motivo: {$motivo}");

            header("Location: ../../panel_superadmin.php?status=usuario_borrado");
            exit();
        }


        // Calcular fecha de fin para bans temporales
        $baneado_hasta = null;
        $permanente = 0;

        switch ($tipo) {
            case '10m':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                break;
            case '30m':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                break;
            case '1d':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 day'));
                break;
            case '4d':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+4 days'));
                break;
            case '1w':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 week'));
                break;
            case '3w':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+3 weeks'));
                break;
            case '1mo':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 month'));
                break;
            case '6mo':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+6 months'));
                break;
            case '1y':
                $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
            case 'permanente':
                $permanente = 1;
                break;
            default:
                header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&error=opcion");
                exit();
        }

        $sql = "UPDATE usuarios SET baneado_hasta = :hasta, motivo_ban = :motivo, ban_permanente = :perm WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':hasta' => $baneado_hasta,
            ':motivo' => $motivo,
            ':perm' => $permanente,
            ':id' => $id_moderado
        ]);

        include_once '../../config/mailer.php';
        $duracion_txt = $permanente ? "forma permanente" : "hasta el " . date("d/m/Y H:i", strtotime($baneado_hasta));

        $msg_motivo = !empty($motivo) ? "<p>Motivo de la sanción:</p>
                       <blockquote style='background:#f4f4f4; padding:10px; border-left:5px solid #ffaa00;'>
                       " . nl2br(htmlspecialchars($motivo)) . "
                       </blockquote>" : "<p>Has sido sancionado por incumplir las normas de convivencia de la wiki oficial.</p>";

        $cuerpo = "<h3>Aviso de Moderación: " . htmlspecialchars($user['email']) . "</h3>
                   <p>Tu cuenta ha sido suspendida de $duracion_txt.</p>
                   $msg_motivo
                   <p>Si consideras que es un error, contacta con el administrador.</p>";
        sendArkEmail($user['email'], "Aviso de Sanción en ARK Hub", $cuerpo);

        include_once '../../config/admin_logger.php';
        registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Sancionar', "Baneado {$duracion_txt} al usuario ID {$id_moderado} ({$user['email']}). Motivo: {$motivo}");

        header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&status=sancionado");
        exit();

    }
    catch (PDOException $e) {
        error_log("Error en moderación: " . $e->getMessage());
        header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&error=interno");
        exit();
    }
}
?>
