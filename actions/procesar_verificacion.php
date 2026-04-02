<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $email  = trim($_POST['email']);
    $codigo = trim($_POST['codigo']);

    if (empty($email) || empty($codigo)) {
        header("Location: ../verificar.php?error=falta_email&email=" . urlencode($email));
        exit();
    }

    // Rate limiting: máximo 10 intentos de verificación por sesión
    $_SESSION['verify_attempts'] = ($_SESSION['verify_attempts'] ?? 0) + 1;
    if ($_SESSION['verify_attempts'] > 10) {
        header("Location: ../verificar.php?error=demasiados_intentos&email=" . urlencode($email));
        exit();
    }

    try {
        $sql = "SELECT id, verificado FROM usuarios WHERE email = :email AND codigo_verificacion = :codigo";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':email' => $email, ':codigo' => $codigo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Activar la cuenta y resetear contador de intentos
            $update = $conexion->prepare("UPDATE usuarios SET verificado = 1, codigo_verificacion = NULL WHERE id = :id");
            $update->execute([':id' => $user['id']]);
            unset($_SESSION['verify_attempts']);

            header("Location: ../verificar.php?status=verificado&email=" . urlencode($email));
            exit();
        } else {
            // Código incorrecto
            header("Location: ../verificar.php?error=codigo_invalido&email=" . urlencode($email));
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error en verificación: " . $e->getMessage());
        header("Location: ../verificar.php?error=codigo_invalido&email=" . urlencode($email));
        exit();
    }
}
?>
