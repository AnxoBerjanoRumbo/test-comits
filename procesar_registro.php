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
        if ($rol === 'admin') {
            $checkEmail = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $checkEmail->execute([':email' => $email]);
            if ($checkEmail->fetchColumn() > 0) {
                header("Location: registro.php?error=email_en_uso");
                exit();
            }

            $checkNick = $conexion->prepare("SELECT id, rol FROM usuarios WHERE nick = :nick");
            $checkNick->execute([':nick' => $nick]);
            $existente = $checkNick->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                if ($existente['rol'] === 'admin') {
                    // Ya hay un admin activo o pendiente con ese nick
                    header("Location: registro.php?error=nick_admin_activo");
                    exit();
                }
                // El nick existe como usuario normal (slot de admin previamente revocado):
                // reciclamos el registro en lugar de insertar uno nuevo.
                $sqlUpdate = "UPDATE usuarios SET email = :email, password = '', rol = 'admin',
                              permiso_insertar_dino = 0, permiso_eliminar_comentario = 0,
                              recuperar_token = NULL, recuperar_expira = NULL WHERE id = :id";
                $stmtU = $conexion->prepare($sqlUpdate);
                $stmtU->execute([':email' => $email, ':id' => $existente['id']]);
            }
            else {
                // Nick libre: inserción normal
                $sqlInsert = "INSERT INTO usuarios (nick, email, password, rol) VALUES (:nick, :email, '', 'admin')";
                $stmtI = $conexion->prepare($sqlInsert);
                $stmtI->execute([':nick' => $nick, ':email' => $email]);
            }

            header("Location: registro.php?status=espera");
            exit();

        }
        else {
            // Registro de usuario normal: comprobación estándar de nick Y email
            $check = $conexion->prepare("SELECT id, nick, email FROM usuarios WHERE nick = :nick OR email = :email LIMIT 1");
            $check->execute([':nick' => $nick, ':email' => $email]);
            $conflicto = $check->fetch(PDO::FETCH_ASSOC);
            if ($conflicto) {
                if ($conflicto['email'] === $email) {
                    header("Location: registro.php?error=email_en_uso");
                } else {
                    header("Location: registro.php?error=nick_en_uso");
                }
                exit();
            }

            $sql = "INSERT INTO usuarios (nick, email, password, rol) VALUES (:nick, :email, :password, :rol)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':nick' => $nick,
                ':email' => $email,
                ':password' => $password_final,
                ':rol' => $rol
            ]);
            header("Location: login.php?status=registrado");
            exit();
        }

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