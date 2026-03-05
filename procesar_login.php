<?php
session_start();

include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }
    $nick = trim($_POST['nick']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM usuarios WHERE nick = :nick";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':nick' => $nick]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nick'] = $user['nick'];
            $_SESSION['rol'] = $user['rol'];

            if ($user['rol'] === 'admin' || $user['rol'] === 'superadmin') {
                $_SESSION['is_admin'] = true;
            } else {
                $_SESSION['is_admin'] = false;
            }

            header("Location: index.php");
            exit();
        }
        else {
            header("Location: login.php?error=credenciales");
            exit();
        }

    }
    catch (PDOException $e) {
        error_log("Error en el login: " . $e->getMessage());
        header("Location: login.php?error=interno");
        exit();
    }
}
else {
    header("Location: login.php");
    exit();
}