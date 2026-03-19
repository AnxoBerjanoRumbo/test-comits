<?php
session_start();

include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $nick = trim($_POST['nick']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM usuarios WHERE nick = :nick OR email = :nick";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':nick' => $nick]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 1. Verificar si el email del usuario está bloqueado totalmente
            $checkBlocked = $conexion->prepare("SELECT COUNT(*) FROM emails_bloqueados WHERE email = :email");
            $checkBlocked->execute([':email' => $user['email']]);
            if ($checkBlocked->fetchColumn() > 0) {
                header("Location: ../login.php?error=email_bloqueado");
                exit();
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            // 2. Verificar si el usuario está bajo baneo (temporal o permanente)
            if ($user['ban_permanente'] == 1) {
                $_SESSION['ban_motivo'] = $user['motivo_ban'];
                header("Location: ../login.php?error=baneado_permanente");
                exit();
            }

            if (!empty($user['baneado_hasta']) && strtotime($user['baneado_hasta']) > time()) {
                $_SESSION['ban_motivo'] = $user['motivo_ban'];
                $_SESSION['ban_hasta'] = $user['baneado_hasta'];
                header("Location: ../login.php?error=baneado_temporal");
                exit();
            }

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nick'] = $user['nick'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['foto_perfil'] = $user['foto_perfil'] ?? 'default.png';

            if ($user['rol'] === 'admin' || $user['rol'] === 'superadmin') {
                $_SESSION['is_admin'] = true;
                // Superadmin siempre tiene 1, admin depende de la DB
                $_SESSION['p_insertar'] = ($user['rol'] === 'superadmin') ? 1 : ($user['permiso_insertar_dino'] ?? 0);
                $_SESSION['p_eliminar'] = ($user['rol'] === 'superadmin') ? 1 : ($user['permiso_eliminar_comentario'] ?? 0);
                $_SESSION['p_moderar'] = ($user['rol'] === 'superadmin') ? 1 : ($user['permiso_moderar_usuarios'] ?? 0);
            }
            else {
                $_SESSION['is_admin'] = false;
            }

            header("Location: ../index.php");
            exit();
        }
        else {
            header("Location: ../login.php?error=credenciales");
            exit();
        }

    }
    catch (PDOException $e) {
        error_log("Error en el login: " . $e->getMessage());
        header("Location: ../login.php?error=interno");
        exit();
    }
}
else {
    header("Location: ../login.php");
    exit();
}