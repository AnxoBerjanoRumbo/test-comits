<?php
session_start();
include 'config/db.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

// Si viene de un error de validación, recuperar el token de la sesión
if (empty($token) && !empty($_SESSION['reset_token_tmp'])) {
    $token = $_SESSION['reset_token_tmp'];
    unset($_SESSION['reset_token_tmp']);
}

$error = false;
$user_id = null;

if (empty($token)) {
    header("Location: login.php");
    exit();
}

try {
    // Verificar si el token es válido y no ha expirado
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE recuperar_token = :token AND recuperar_expira > NOW()");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "El enlace es inválido o ha expirado. Por favor, solicita uno nuevo.";
    } else {
        $user_id = $user['id'];
    }
} catch (PDOException $e) {
    $error = "Error interno del servidor.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <?php 
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Restablecer Contraseña</h2>

        <?php if ($error): ?>
            <div class="alerta-error">
                <?php echo $error; ?>
            </div>
            <div class="text-center mt-20">
                <a href="recuperar.php" class="btn-nav">Solicitar otro enlace</a>
            </div>
        <?php else: ?>
            <form action="actions/procesar_reset.php" method="POST" class="form-ark">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="campo">
                    <label>Nueva Contraseña:</label>
                    <input type="password" name="nueva_password" required placeholder="Mínimo 4 caracteres" id="pass">
                </div>

                <div class="campo">
                    <label>Confirmar Contraseña:</label>
                    <input type="password" name="confirmar_password" required placeholder="Repite la contraseña" id="confirm_pass">
                    <small id="error_pass" class="error-validacion">Las contraseñas no coinciden.</small>
                </div>

                <button type="submit" class="boton-insertar">Cambiar Contraseña</button>
            </form>
        <?php endif; ?>

    <script src="assets/js/reset_password.js"></script>
    <?php include 'includes/footer.php'; ?>
