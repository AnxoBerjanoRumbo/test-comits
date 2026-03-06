<?php
session_start();
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
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
    <header>
        <h1>Entrar a ARK Hub</h1>
        <a href="index.php" class="boton-volver">Volver a la Wiki</a>
    </header>

    <main class="contenedor-formulario">
        <h2>Identificación de Usuario</h2>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'registrado'): ?>
            <div style="background-color: #4CAF50; color: white; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                ✅ Registro completado. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'pass_cambiada'): ?>
            <div style="background-color: #4CAF50; color: white; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                ✅ Tu contraseña ha sido cambiada correctamente. Ya puedes acceder.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'credenciales'): ?>
            <div class="alerta-error">
                Usuario o contraseña incorrectos. Acceso denegado.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'token_invalido'): ?>
            <div class="alerta-error">
                El enlace de recuperación ya no es válido.
            </div>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Usuario (o Email):</label>
                <input type="text" name="nick" required placeholder="Introduce tu identificador">
            </div>

            <div class="campo">
                <label>Contraseña de acceso:</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <a href="recuperar.php" style="color: #4CAF50; font-size: 0.9em; text-decoration: none;">¿Olvidaste tu contraseña?</a>
                <a href="registro.php" style="color: #fff; font-size: 0.9em; text-decoration: none;">Regístrate aquí</a>
            </div>

            <button type="submit" class="boton-insertar">Iniciar Sesión</button>
        </form>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>