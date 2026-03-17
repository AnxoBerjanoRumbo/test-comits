<?php
session_start();
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
    <title>ARK Hub - Acceso Restringido</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <?php 
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Identificación de Usuario</h2>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'registrado'): ?>
            <div class="alerta-exito">
                ✅ Registro completado. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'pass_cambiada'): ?>
            <div class="alerta-exito">
                ✅ Tu contraseña ha sido cambiada correctamente. Ya puedes acceder.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'expulsado'): ?>
            <div class="alerta-error">
                🚫 El usuario ha sido expulsado permanentemente del servidor.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] == 'credenciales'): ?>
                <div class="alerta-error">
                    Usuario o contraseña incorrectos. Acceso denegado.
                </div>
            <?php elseif ($_GET['error'] == 'email_bloqueado'): ?>
                <div class="alerta-error">
                    🚫 Este correo electrónico tiene un bloqueo total y no puede acceder ni registrarse.
                </div>
            <?php elseif ($_GET['error'] == 'baneado_permanente'): ?>
                <div class="alerta-error">
                    🛑 <strong>CUENTA BANEADA PERMANENTEMENTE</strong><br>
                    Motivo: <?php echo htmlspecialchars($_SESSION['ban_motivo'] ?? 'Incumplimiento de normas'); ?>
                </div>
            <?php elseif ($_GET['error'] == 'baneado_temporal'): ?>
                <div class="alerta-error">
                    ⏳ <strong>CUENTA SUSPENDIDA TEMPORALMENTE</strong><br>
                    Hasta: <?php echo date("d/m/Y H:i", strtotime($_SESSION['ban_hasta'] ?? 'now')); ?><br>
                    Motivo: <?php echo htmlspecialchars($_SESSION['ban_motivo'] ?? 'Revisión técnica'); ?>
                </div>
            <?php elseif ($_GET['error'] == 'token_invalido'): ?>
                <div class="alerta-error">
                    El enlace de recuperación ya no es válido.
                </div>
            <?php endif; ?>
        <?php endif; ?>


        <form action="actions/procesar_login.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Usuario (o Email):</label>
                <input type="text" name="nick" required placeholder="Introduce tu identificador">
            </div>

            <div class="campo">
                <label>Contraseña de acceso:</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div class="flex-between-center mb-20">
                <a href="recuperar.php" class="accent-link f-09 no-decoration">¿Olvidaste tu contraseña?</a>
                <a href="registro.php" class="text-muted f-09 no-decoration">Regístrate aquí</a>
            </div>

            <button type="submit" class="boton-insertar">Iniciar Sesión</button>
        </form>
    <?php include 'includes/footer.php'; ?>