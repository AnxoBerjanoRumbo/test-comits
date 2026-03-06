<?php
session_start();
include 'config/db.php'; // Asegúrate de que la ruta a tu conexión es correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $nick = trim($_POST['nick']);
    $email = trim($_POST['email']);
    $password_introducida = $_POST['password'];
    $confirm_password_introducida = $_POST['confirm_password'];

    if ($password_introducida !== $confirm_password_introducida) {
        header("Location: registro.php?error=pass_mismatch");
        exit();
    }

    // 1. Lógica de Roles y Contraseñas
    $rol = 'usuario';
    $password_final = password_hash($password_introducida, PASSWORD_DEFAULT);

    if (stripos($nick, 'admin') !== false) {
        if (!preg_match('/^admin[0-9]{1,2}$/i', $nick) || (int)substr($nick, 5) > 99) {
            header("Location: registro.php?error=admin_invalido");
            exit();
        }
        $rol = 'admin';
        $password_final = '';
    }

    try {
        // 2. Comprobamos que el nick o el email no existan ya
        $check = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE nick = :nick OR email = :email");
        $check->execute([':nick' => $nick, ':email' => $email]);

        if ($check->fetchColumn() > 0) {
            header("Location: registro.php?error=duplicado");
            exit();
        }

        // 3. Insertamos en la base de datos
        $sql = "INSERT INTO usuarios (nick, email, password, rol) VALUES (:nick, :email, :password, :rol)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nick' => $nick,
            ':email' => $email,
            ':password' => $password_final,
            ':rol' => $rol
        ]);

        // 4. Redirecciones según el rol
        if ($rol === 'admin') {
            header("Location: registro.php?status=espera");
        }
        else {
            header("Location: login.php?status=registrado");
        }
        exit();

    }
    catch (PDOException $e) {
        error_log("Error en el registro: " . $e->getMessage());
        header("Location: registro.php?error=interno");
        exit();
    }
}
else {
    header("Location: registro.php");
    exit();
}
?>