<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}
include '../config/db.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$usuario_id = $_SESSION['usuario_id'];
$nueva_password = $_POST['nueva_password'];
$confirmar_password = $_POST['confirmar_password'];

if (empty($nueva_password)) {
    header("Location: ../perfil.php?error=vacio");
    exit();
}

if ($nueva_password !== $confirmar_password) {
    header("Location: ../perfil.php?error=pass_no_coincide");
    exit();
}

if (strlen($nueva_password) < 4) {
    header("Location: ../perfil.php?error=pass_corta");
    exit();
}

if (strlen($nueva_password) > 100) {
    header("Location: ../perfil.php?error=pass_larga");
    exit();
}

try {
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    $sqlPass = "UPDATE usuarios SET password = :p WHERE id = :id";
    $stmtPass = $conexion->prepare($sqlPass);
    $stmtPass->execute([':p' => $hash, ':id' => $usuario_id]);

    header("Location: ../perfil.php?status=success");
    exit();

} catch (PDOException $e) {
    error_log("Error al cambiar contraseña: " . $e->getMessage());
    header("Location: ../perfil.php?error=db");
    exit();
}
?>
