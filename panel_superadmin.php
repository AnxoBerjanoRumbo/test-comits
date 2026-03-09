<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

$sql_pendientes = "SELECT * FROM usuarios WHERE rol = 'admin' AND password = ''";
$stmt = $conexion->prepare($sql_pendientes);
$stmt->execute();
$admins_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Superadministrador</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <header>
        <h1 class="titulo-superadmin">Panel de Superadministrador</h1>
        <a href="index.php" class="boton-volver">Volver a la Wiki</a>
    </header>

    <main class="contenedor-detalle">
        <h2>Administradores Pendientes de Activación</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'actualizado'): ?>
            <div class="alerta-exito">
                Contraseña asignada correctamente. El admin ya puede entrar.
            </div>
        <?php
endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'cancelado'): ?>
            <div class="alerta-exito" style="color: #ff4444; border-color: #ff4444; background: rgba(255, 68, 68, 0.1);">
                Solicitud de administrador cancelada y eliminada.
            </div>
        <?php
endif; ?>

        <?php if (count($admins_pendientes) > 0): ?>
            <div class="lista-admins">
                <?php foreach ($admins_pendientes as $admin): ?>
                    <div class="admin-card">
                        <h3 class="admin-nick"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                        <p class="admin-estado">Pendiente de activación</p>
                        
                        <div class="admin-actions">
                            <form action="procesar_password.php" method="POST" class="form-activar">
                                <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                <input type="text" name="nueva_password" required placeholder="Escribir contraseña..." class="input-password">
                                <button type="submit" class="btn-activar">Activar Admin</button>
                            </form>
                            
                            <form action="procesar_cancelar_admin.php" method="POST" style="width: 100%;">
                                <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                <button type="submit" class="btn-cancelar" onclick="return confirm('¿Seguro que quieres cancelar la solicitud de <?php echo htmlspecialchars($admin['nick']); ?>?');">Cancelar Solicitud</button>
                            </form>
                        </div>
                    </div>
                <?php
    endforeach; ?>
            </div>
        <?php
else: ?>
            <p class="mensaje-vacio">No hay administradores pendientes de activación.</p>
        <?php
endif; ?>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>