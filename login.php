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
        <h1>Acceso de Administración</h1>
        <a href="index.php" class="boton-volver">Volver a la Wiki</a>
    </header>

    <main class="contenedor-formulario">
        <h2>Identificación de Usuario</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'credenciales'): ?>
            <div class="alerta-error">
                Usuario o contraseña incorrectos. Acceso denegado.
            </div>
        <?php
endif; ?>

        <form action="procesar_login.php" method="POST" class="form-ark">
            <div class="campo">
                <label>Usuario (o Email):</label>
                <input type="text" name="nick" required placeholder="Introduce tu identificador">
            </div>

            <div class="campo">
                <label>Contraseña de acceso:</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="boton-insertar">Iniciar Sesión</button>
        </form>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>