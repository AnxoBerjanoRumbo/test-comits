<?php
session_start();
// Si ya hay alguien logueado, lo mandamos al inicio
if (isset($_SESSION['nick'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARK Hub - Registro</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <?php 
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Únete a la Wiki</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'espera'): ?>
            <div class="alerta-error" style="color: orange; border-color: orange; background: rgba(255,165,0,0.1);">
                <strong>Solicitud de Admin registrada.</strong> Tu cuenta está bloqueada. El Superadministrador (Anxo) debe asignarte una contraseña para poder acceder.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'pass_mismatch'): ?>
            <div class="alerta-error">
                Las contraseñas no coinciden. Inténtalo de nuevo.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'email_en_uso'): ?>
            <div class="alerta-error">
                Este <strong>correo electrónico</strong> ya está registrado. Intenta con otro o recupera tu contraseña.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'nick_en_uso'): ?>
            <div class="alerta-error">
                El <strong>nick</strong> ya está en uso por otro superviviente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'nick_admin_activo'): ?>
            <div class="alerta-error" style="color: orange; border-color: orange; background: rgba(255,165,0,0.1);">
                Ya existe un <strong>administrador</strong> activo o una solicitud pendiente con este nick exacto.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'interno'): ?>
            <div class="alerta-error">
                Hubo un error interno en el servidor. Por favor, inténtalo de nuevo más tarde.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'admin_invalido'): ?>
            <div class="alerta-error">
                Si deseas ser administrador, tu nick debe tener el formato exacto de 'admin' seguido de un número del 0 al 99 (ej: admin42). No puedes usar la palabra 'admin' de otra forma.
            </div>
        <?php endif; ?>

        <form action="actions/procesar_registro.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Nick de Usuario:</label>
                <input type="text" name="nick" required placeholder="Ej: Superviviente99 o admin1">
                <small style="color: #aaa;">Si tu nick incluye "admin", requerirá aprobación manual.</small>
            </div>

            <div class="campo">
                <label>Correo Electrónico:</label>
                <input type="email" name="email" required placeholder="tu@email.com">
                <small style="color: #aaa;">Necesario para recuperar tu contraseña.</small>
            </div>

            <div class="campo">
                <label>Contraseña (solo usuarios normales):</label>
                <input type="password" name="password" required placeholder="••••••••" id="pass">
            </div>

            <div class="campo">
                <label>Confirmar Contraseña:</label>
                <input type="password" name="confirm_password" required placeholder="••••••••" id="confirm_pass">
                <small id="error_pass" style="color: #ff4444; display: none; margin-top: 5px;">Las contraseñas no coinciden.</small>
            </div>

            <button type="submit" class="boton-insertar">Registrarse</button>
        </form>

    <script src="assets/js/registro.js"></script>
    <?php include 'includes/footer.php'; ?>