<?php
// config/verificar_sesion.php
// Función para verificar baneo/expulsión en tiempo real en archivos de acción (sin HTML)

function check_user_active_status($conexion, $path_to_login = '../login.php') {
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $conexion->prepare("SELECT baneado_hasta, ban_permanente, motivo_ban FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            // Usuario ya no existe (expulsado/borrado)
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: $path_to_login?error=expulsado");
            exit();
        }

        $is_perm = ($status['ban_permanente'] == 1);
        $is_temp = (!empty($status['baneado_hasta']) && strtotime($status['baneado_hasta']) > time());

        if ($is_perm || $is_temp) {
            $motivo = $status['motivo_ban'];
            $hasta = $status['baneado_hasta'];
            
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['ban_motivo'] = $motivo;
            if ($is_temp) $_SESSION['ban_hasta'] = $hasta;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $type = $is_perm ? 'baneado_permanente' : 'baneado_temporal';
            header("Location: $path_to_login?error=$type");
            exit();
        }
    }
}
?>
