<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}
include '../config/db.php';
include_once '../config/verificar_sesion.php';
check_user_active_status($conexion);

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$nuevo_nick = trim($_POST['nuevo_nick']);
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

if (mb_strlen($nuevo_nick) > 25 || empty($nuevo_nick)) {
    header("Location: ../perfil.php?error=nick_invalido");
    exit();
}

// Prevenir que usurpen Nicks de admins o intenten nombrar admins
if (stripos($nuevo_nick, 'admin') !== false && $rol !== 'superadmin' && $rol !== 'admin') {
    header("Location: ../perfil.php?error=nick_reservado");
    exit();
}

try {
    // Comprobar si el nick ya está en uso por OTRA persona
    $check = $conexion->prepare("SELECT id FROM usuarios WHERE nick = :nick AND id != :id");
    $check->execute([':nick' => $nuevo_nick, ':id' => $usuario_id]);
    if ($check->fetchColumn()) {
        header("Location: ../perfil.php?error=nick_en_uso");
        exit();
    }

    $sqlUpdate = "UPDATE usuarios SET nick = :nick WHERE id = :id";
    $stmt = $conexion->prepare($sqlUpdate);
    $stmt->execute([':nick' => $nuevo_nick, ':id' => $usuario_id]);

    $_SESSION['nick'] = $nuevo_nick;

    header("Location: ../perfil.php?status=success");
    exit();

} catch (PDOException $e) {
    error_log("Error al cambiar nick: " . $e->getMessage());
    header("Location: ../perfil.php?error=db");
    exit();
}
?>
