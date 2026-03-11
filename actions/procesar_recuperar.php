<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: ../recuperar.php?error=vacio");
        exit();
    }

    try {
        // 1. Verificar si el email existe
        $stmt = $conexion->prepare("SELECT id, nick FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2. Generar token único
            $token = bin2hex(random_bytes(32));
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // 3. Guardar token en la BD
            $stmt_update = $conexion->prepare("UPDATE usuarios SET recuperar_token = :token, recuperar_expira = :expira WHERE id = :id");
            $stmt_update->execute([
                ':token' => $token,
                ':expira' => $expira,
                ':id' => $user['id']
            ]);

            // 4. "Simular" envío de correo (En un entorno real se usaría mail() o PHPMailer)
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/actions', '', dirname($_SERVER['PHP_SELF']));
            $reset_link = $base_url . "/reset_password.php?token=" . $token;
            
            // Logueamos el enlace para que el desarrollador pueda verlo (simulando envío)
            $log_msg = "[" . date("Y-m-d H:i:s") . "] Recuperación para " . $email . ": " . $reset_link . PHP_EOL;
            file_put_contents("recuperacion_log.txt", $log_msg, FILE_APPEND);
            
            // Nota para el usuario: "Se ha enviado un correo"
        }

        // Siempre redirigimos a éxito por seguridad (para no revelar si el correo existe o no)
        header("Location: ../recuperar.php?status=enviado");
        exit();

    } catch (PDOException $e) {
        error_log("Error en recuperación: " . $e->getMessage());
        header("Location: ../recuperar.php?error=interno");
        exit();
    }
} else {
    header("Location: ../recuperar.php");
    exit();
}
?>
