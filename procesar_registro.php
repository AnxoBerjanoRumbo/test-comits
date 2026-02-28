<?php
session_start();
include 'config/db.php'; // Asegúrate de que la ruta a tu conexión es correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nick = trim($_POST['nick']);
    $password_introducida = $_POST['password'];

    // 1. Lógica de Roles y Contraseñas
    $rol = 'usuario';
    $password_final = $password_introducida;


    if (strpos(strtolower($nick), 'admin') !== false) {
        $rol = 'admin';
        $password_final = '';
    }

    try {
        // 2. Comprobamos que el nick no exista ya
        $check = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE nick = :nick");
        $check->execute([':nick' => $nick]);

        if ($check->fetchColumn() > 0) {
            header("Location: registro.php?error=duplicado");
            exit();
        }

        // 3. Insertamos en la base de datos
        $sql = "INSERT INTO usuarios (nick, password, rol) VALUES (:nick, :password, :rol)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nick' => $nick,
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
        echo "Error en el registro: " . $e->getMessage();
    }
}
else {
    header("Location: registro.php");
    exit();
}
?>