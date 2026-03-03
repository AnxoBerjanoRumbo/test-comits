<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.2">
</head>
<body>
    <header class="header-principal">
        <div class="logo-titulo">
            <h1>Opciones de Usuario</h1>
        </div>
        <nav class="navegacion-usuario">
            <a href="index.php" class="btn-nav">Volver al Inicio</a>
        </nav>
    </header>

    <main class="contenedor-formulario">
        <h2>Mi Perfil</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div style="background-color: #4CAF50; color: white; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                ✅ Perfil actualizado correctamente.
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alerta-error">
                <?php 
                if ($_GET['error'] == 'pass_no_coincide') echo "⚠️ Las contraseñas no coinciden.";
                elseif ($_GET['error'] == 'pass_corta') echo "⚠️ La nueva contraseña debe tener al menos 4 caracteres.";
                elseif ($_GET['error'] == 'upload') echo "⚠️ Error al subir la imagen. Comprueba que sea un formato válido.";
                else echo "⚠️ Error al actualizar el perfil.";
                ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <img src="assets/img/perfil/<?php echo htmlspecialchars($usuario['foto_perfil'] ?? 'default.png'); ?>" alt="Foto de perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #ffcc00; margin-bottom: 10px;">
            <p><strong><?php echo htmlspecialchars($usuario['nick']); ?></strong> (<?php echo htmlspecialchars($usuario['rol']); ?>)</p>
        </div>

        <form action="procesar_perfil.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <div class="campo">
                <label>Cambiar foto de perfil (Opcional):</label>
                <input type="file" name="foto_perfil" accept="image/*">
            </div>

            <div class="campo">
                <label>Nueva contraseña (Opcional):</label>
                <input type="password" name="nueva_password" placeholder="Solo si deseas cambiarla">
            </div>

            <div class="campo">
                <label>Confirmar nueva contraseña:</label>
                <input type="password" name="confirmar_password" placeholder="Repite la nueva contraseña">
            </div>

            <button type="submit" class="boton-insertar">Guardar Perfil</button>
        </form>
    </main>
</body>
</html>
