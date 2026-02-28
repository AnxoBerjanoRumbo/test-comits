<?php
session_start();
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nick = trim($_POST['nick']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM usuarios WHERE nick = :nick";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':nick' => $nick]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $password) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['nick'] = $user['nick'];
            $_SESSION['rol'] = $user['rol'];

            if ($user['rol'] === 'admin' || $user['rol'] === 'superadmin') {
                $_SESSION['is_admin'] = true;
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
        echo "Error en el login: " . $e->getMessage();
    }
}
else {
    header("Location: login.php");
    exit();
}