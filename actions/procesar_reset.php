<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $token             = $_POST['token'];
    $nueva_password    = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];

    if ($nueva_password !== $confirmar_password) {
        // Token se guarda en sesión temporal para no exponerlo en URL
        $_SESSION['reset_token_tmp'] = $token;
        header("Location: ../reset_password.php?error=mismatch");
        exit();
    }

    if (strlen($nueva_password) < 8) {
        $_SESSION['reset_token_tmp'] = $token;
        header("Location: ../reset_password.php?error=corta");
        exit();
    }

    try {
        // 1. Verificar token de nuevo
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE recuperar_token = :token AND recuperar_expira > NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2. Actualizar contraseña y limpiar token
            $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $stmt_update = $conexion->prepare("UPDATE usuarios SET password = :pass, recuperar_token = NULL, recuperar_expira = NULL WHERE id = :id");
            $stmt_update->execute([
                ':pass' => $hash,
                ':id' => $user['id']
            ]);

            header("Location: ../login.php?status=pass_cambiada");
            exit();
        } else {
            header("Location: ../login.php?error=token_invalido");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error en reset pass: " . $e->getMessage());
        header("Location: ../login.php?error=interno");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>
