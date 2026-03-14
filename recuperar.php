<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <?php 
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>¿Olvidaste tu contraseña?</h2>
        <p style="text-align: center; color: #ccc; margin-bottom: 20px;">Introduce tu correo electrónico y te enviaremos un enlace para restablecerla.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'enviado'): ?>
            <div style="background-color: #4CAF50; color: white; padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                ✅ Si el correo existe en nuestra base de datos, recibirás un enlace en unos minutos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'email_no_encontrado'): ?>
            <div class="alerta-error">
                No hemos encontrado ninguna cuenta asociada a ese correo.
            </div>
        <?php endif; ?>

        <form action="actions/procesar_recuperar.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Tu Correo Electrónico:</label>
                <input type="email" name="email" required placeholder="tu@email.com">
            </div>

            <button type="submit" class="boton-insertar">Enviar Enlace de Recuperación</button>
        </form>
    <?php include 'includes/footer.php'; ?>
