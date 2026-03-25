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
        // 1. Verificar si el email existe y el usuario está activado (tiene password)
        // Esto evita que admins que aún no han sido activados (password = '') puedan "setear" 
        // su propia contraseña vía recuperación sin permiso del superadmin.
        $stmt = $conexion->prepare("SELECT id, nick FROM usuarios WHERE email = :email AND password != ''");
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

            // 4. Enviar correo usando PHPMailer
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/actions', '', dirname($_SERVER['PHP_SELF']));
            $reset_link = $base_url . "/reset_password.php?token=" . $token;
            
            include_once '../config/mailer.php';
            $cuerpo = "<h3>Hola, " . htmlspecialchars($user['nick']) . "!</h3>
                       <p>Has solicitado restablecer tu contraseña en ARK Hub.</p>
                       <p>Haz clic en el siguiente enlace para cambiarla (caduca en 1 hora):</p>
                       <p><a href='$reset_link' style='padding: 10px 20px; background: #00ffcc; color: #000; text-decoration: none; border-radius: 5px;'>Restablecer Contraseña</a></p>
                       <p>Si no fuiste tú, puedes ignorar este correo.</p>";

            sendArkEmail($email, "Instrucciones de recuperación - ARK Hub", $cuerpo);
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
