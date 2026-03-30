<?php
// config/verificar_sesion.php
// Función para verificar baneo/expulsión en tiempo real en archivos de acción (sin HTML)

function check_user_active_status($conexion, $path_to_login = '../login.php') {
    if (isset($_SESSION['usuario_id'])) {
        // Optimización: Unificamos verificación de baneo, foto de perfil y contador en 1 sola consulta
        $sql = "SELECT baneado_hasta, ban_permanente, motivo_ban, foto_perfil,
                (SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :id AND leida = 0) as n_count
                FROM usuarios WHERE id = :id";
        
        try {
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id' => $_SESSION['usuario_id']]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$status) {
                // Usuario ya no existe (expulsado/borrado)
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                }
                session_destroy();
                session_start();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: $path_to_login?error=expulsado");
                exit();
            }

            // Sincronización de datos vivos en la sesión y globales
            $_SESSION['foto_perfil'] = $status['foto_perfil'] ?: 'default.png';
            $GLOBALS['notif_count'] = (int)$status['n_count'];

            $is_perm = ($status['ban_permanente'] == 1);
            $is_temp = (!empty($status['baneado_hasta']) && strtotime($status['baneado_hasta']) > time());

            if ($is_perm || $is_temp) {
                $motivo = $status['motivo_ban'];
                $hasta = $status['baneado_hasta'];
                
                $_SESSION = array();
                session_destroy();
                session_start();
                $_SESSION['ban_motivo'] = $motivo;
                if ($is_temp) $_SESSION['ban_hasta'] = $hasta;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $type = $is_perm ? 'baneado_permanente' : 'baneado_temporal';
                header("Location: $path_to_login?error=$type");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Error en salud de sesión: " . $e->getMessage());
        }
    }
}
?>
