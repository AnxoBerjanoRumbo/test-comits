<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';
include 'config/sync_foto.php';

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
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.3">
</head>
<body>
    <?php 
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

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

        <div class="perfil-container">
            <?php 
            $foto_p = $usuario['foto_perfil'] ?? 'default.png';
            $src_p = (strpos($foto_p, 'http') === 0) ? $foto_p : "assets/img/perfil/" . $foto_p;
            ?>
            <img src="<?php echo htmlspecialchars($src_p); ?>" 
                 alt="Foto de perfil" 
                 class="perfil-foto-main"
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent); margin: 0 auto 15px auto; display: block;"
                 onerror="this.src='assets/img/perfil/default.png'">
            <p><strong><?php echo htmlspecialchars($usuario['nick']); ?></strong> 
               <span style="color: var(--accent);">(<?php echo htmlspecialchars($usuario['rol']); ?>)</span>
            </p>
        </div>

        <!-- Formulario para la Foto de Perfil (Auto-envío) -->
        <form id="form-foto" action="actions/procesar_perfil.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div class="campo">
                <label>Cambiar foto de perfil:</label>
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" style="display: none;">
                <button type="button" class="boton-insertar" onclick="document.getElementById('foto_perfil').click()">Seleccionar Nueva Imagen</button>
                <small style="display: block; margin-top: 10px; color: #aaa;">La foto se actualizará automáticamente al seleccionarla.</small>
            </div>
        </form>

        <hr style="border: 0; height: 1px; background: #333; margin: 30px 0;">

        <!-- Formulario para la Contraseña -->
        <form action="actions/procesar_password.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <h3>Cambiar Contraseña</h3>
            <div class="campo">
                <label>Nueva contraseña:</label>
                <input type="password" name="nueva_password" placeholder="Mínimo 4 caracteres" required>
            </div>

            <div class="campo">
                <label>Confirmar nueva contraseña:</label>
                <input type="password" name="confirmar_password" placeholder="Repite la nueva contraseña" required>
            </div>

            <button type="submit" class="boton-insertar">Confirmar Cambio de Contraseña</button>
        </form>

        <script src="assets/js/perfil.js"></script>
    <?php include 'includes/footer.php'; ?>
