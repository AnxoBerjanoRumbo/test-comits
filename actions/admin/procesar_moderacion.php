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
                } else {
                    $local_path = '../../assets/img/perfil/' . $foto;
                    if (file_exists($local_path)) unlink($local_path);
                }
            }

            // 3. Eliminar Comentarios del usuario
            $stmt_comm = $conexion->prepare("DELETE FROM comentarios WHERE usuario_id = :id");
            $stmt_comm->execute([':id' => $id_moderado]);

            // 4. Eliminar Usuario
            $sql_del = "DELETE FROM usuarios WHERE id = :id";
            $stmt_d = $conexion->prepare($sql_del);
            $stmt_d->execute([':id' => $id_moderado]);

            header("Location: ../../login.php?status=expulsado");
            exit();
        }


        // Calcular fecha de fin para bans temporales
        $baneado_hasta = null;
        $permanente = 0;

        switch ($tipo) {
            case '10m': $baneado_hasta = date('Y-m-d H:i:s', strtotime('+10 minutes')); break;
            case '30m': $baneado_hasta = date('Y-m-d H:i:s', strtotime('+30 minutes')); break;
            case '1d':  $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 day')); break;
            case '4d':  $baneado_hasta = date('Y-m-d H:i:s', strtotime('+4 days')); break;
            case '1w':  $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 week')); break;
            case '3w':  $baneado_hasta = date('Y-m-d H:i:s', strtotime('+3 weeks')); break;
            case '1mo': $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 month')); break;
            case '6mo': $baneado_hasta = date('Y-m-d H:i:s', strtotime('+6 months')); break;
            case '1y':  $baneado_hasta = date('Y-m-d H:i:s', strtotime('+1 year')); break;
            case 'permanente': $permanente = 1; break;
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

        header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&status=sancionado");
        exit();

    } catch (PDOException $e) {
        error_log("Error en moderación: " . $e->getMessage());
        header("Location: ../../admin/moderar_usuario.php?id=$id_moderado&error=interno");
        exit();
    }
}
?>
