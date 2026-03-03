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
    <header>
        <h1>Crear Cuenta</h1>
        <a href="login.php" class="boton-volver">Ya tengo cuenta (Login)</a>
    </header>

    <main class="contenedor-formulario">
        <h2>Únete a la Wiki</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'espera'): ?>
            <div class="alerta-error" style="color: orange; border-color: orange; background: rgba(255,165,0,0.1);">
                ⚠️ <strong>Solicitud de Admin registrada.</strong> Tu cuenta está bloqueada. El Superadministrador (Anxo) debe asignarte una contraseña para poder acceder.
            </div>
        <?php
endif; ?>

        <form action="procesar_registro.php" method="POST" class="form-ark">
            <div class="campo">
                <label>Nick de Usuario:</label>
                <input type="text" name="nick" required placeholder="Ej: Superviviente99 o admin1">
                <small style="color: #aaa;">Si tu nick incluye "admin", requerirá aprobación manual.</small>
            </div>

            <div class="campo">
                <label>Contraseña (solo usuarios normales):</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="boton-insertar">Registrarse</button>
        </form>
    </main>
</body>
</html>