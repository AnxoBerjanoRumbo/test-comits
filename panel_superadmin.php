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
        <h1 class="titulo-superadmin">👑 Panel de Superadministrador</h1>
        <a href="index.php" class="boton-volver">Volver a la Wiki</a>
    </header>

    <main class="contenedor-detalle">
        <h2>Administradores Pendientes de Activación</h2>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'actualizado'): ?>
            <div class="alerta-exito">
                ✅ Contraseña asignada correctamente. El admin ya puede entrar.
            </div>
        <?php
endif; ?>

        <?php if (count($admins_pendientes) > 0): ?>
            <div class="lista-admins">
                <?php foreach ($admins_pendientes as $admin): ?>
                    <div class="admin-card">
                        <div class="admin-info">
                            <h3 class="admin-nick">Nick: <?php echo htmlspecialchars($admin['nick']); ?></h3>
                            <p class="admin-estado">Estado: Bloqueado (Sin contraseña)</p>
                        </div>
                        
                        <form action="procesar_password.php" method="POST" class="form-activar">
                            <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                            <input type="text" name="nueva_password" required placeholder="Escribe la contraseña..." class="input-password">
                            <button type="submit" class="boton-insertar btn-activar">Activar Admin</button>
                        </form>
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
</body>
</html>