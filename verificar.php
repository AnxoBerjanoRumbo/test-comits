<?php
session_start();
include 'config/db.php';

$email = isset($_GET['email']) ? $_GET['email'] : '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARK Hub - Verificación de Cuenta</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <?php 
    $header_volver_link = "login.php";
    $header_volver_texto = "Volver Atrás";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Verificación de Seguridad</h2>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] == 'codigo_invalido'): ?>
                <div class="alerta-error">El código introducido es incorrecto o no existe.</div>
            <?php elseif ($_GET['error'] == 'falta_email'): ?>
                <div class="alerta-error">Error: Falta el parámetro de cuenta.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'verificado'): ?>
            <div class="alerta-exito">
                ✅ Validación completada con éxito. Ya puedes <a href="login.php" style="color:#000;">iniciar sesión</a>.
            </div>
        <?php else: ?>
            <div class="alerta-info mb-20">
                Se ha enviado un correo electrónico de verificación a <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
                Revisa tu bandeja de entrada o carpeta de Spam para activar la cuenta.
            </div>

            <form action="actions/procesar_verificacion.php" method="POST" class="form-ark">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="campo">
                    <label>Introduce el Código de 6 dígitos:</label>
                    <input type="text" name="codigo" required placeholder="Ej: 123456" maxlength="6" pattern="[0-9]{6}">
                </div>

                <button type="submit" class="boton-insertar w-100">Validar Identidad</button>
            </form>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
