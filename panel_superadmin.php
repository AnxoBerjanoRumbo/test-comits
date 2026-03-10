<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';
include 'config/sync_foto.php';

$sql_pendientes = "SELECT * FROM usuarios WHERE rol = 'admin' AND password = ''";
$stmt_p = $conexion->prepare($sql_pendientes);
$stmt_p->execute();
$admins_pendientes = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$sql_activos = "SELECT * FROM usuarios WHERE rol = 'admin' AND password != ''";
$stmt_a = $conexion->prepare($sql_activos);
$stmt_a->execute();
$admins_activos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
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
    <?php 
    $header_titulo = "Panel Superadmin";
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-detalle" style="max-width: 1000px;">
        <section style="margin-bottom: 50px;">
            <h2>Solicitudes de Administrador (Pendientes)</h2>
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'actualizado'): ?>
                <div class="alerta-exito">
                    ✅ Contraseña asignada correctamente. El admin ya puede entrar.
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] == 'cancelado'): ?>
                <div class="alerta-exito" style="color: #ff4444; border-color: #ff4444; background: rgba(255, 68, 68, 0.1);">
                    ❌ Solicitud de administrador cancelada y eliminada.
                </div>
            <?php endif; ?>

            <?php if (count($admins_pendientes) > 0): ?>
                <div class="lista-admins">
                    <?php foreach ($admins_pendientes as $admin): ?>
                        <div class="admin-card">
                            <h3 class="admin-nick"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                            <p class="admin-estado">Esperando activación de clave</p>
                            
                            <div class="admin-actions">
                                <form action="actions/procesar_activar_admin.php" method="POST" class="form-activar">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                    <input type="text" name="nueva_password" required placeholder="Asignar contraseña..." class="input-password">
                                    <button type="submit" class="btn-activar">Activar Acceso</button>
                                </form>
                                
                                <form action="actions/procesar_cancelar_admin.php" method="POST" style="width: 100%;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" class="btn-cancelar" onclick="return confirm('¿Rechazar a <?php echo htmlspecialchars($admin['nick']); ?>?');">Rechazar Solicitud</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mensaje-vacio">No hay solicitudes nuevas en este momento.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2 style="color: var(--accent);">Administradores Oficiales</h2>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'permisos_actualizados'): ?>
                <div class="alerta-exito">
                    ✅ Permisos actualizados correctamente.
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] == 'admin_quitado'): ?>
                <div class="alerta-exito" style="color: #ff4444; border-color: #ff4444; background: rgba(255, 68, 68, 0.1);">
                    👤 El usuario ya no es administrador.
                </div>
            <?php endif; ?>

            <?php if (count($admins_activos) > 0): ?>
                <div class="lista-admins">
                    <?php foreach ($admins_activos as $admin): ?>
                        <div class="admin-card" style="border-top: 5px solid var(--accent);">
                            <h3 class="admin-nick" style="color: var(--accent);"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                            <p class="admin-estado">Cargo: Administrator</p>
                            
                            <form action="actions/procesar_permisos.php" method="POST" class="admin-actions" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="usuario_id" value="<?php echo $admin['id']; ?>">
                                <input type="hidden" name="accion" value="actualizar_permisos">
                                
                                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin-bottom: 15px;">
                                    <label style="cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 0.9em;">
                                        <input type="checkbox" name="permiso_insertar_dino" <?php echo ($admin['permiso_insertar_dino'] == 1) ? 'checked' : ''; ?> style="width: auto;"> 
                                        Añadir Criaturas
                                    </label>
                                    <label style="cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 0.9em;">
                                        <input type="checkbox" name="permiso_eliminar_comentario" <?php echo ($admin['permiso_eliminar_comentario'] == 1) ? 'checked' : ''; ?> style="width: auto;"> 
                                        Gestionar Comentarios
                                    </label>
                                </div>

                                <button type="submit" class="btn-activar" style="background-color: var(--bg-card); color: var(--accent); border: 1px solid var(--accent); font-size: 0.85em; padding: 10px;">Aplicar Permisos</button>
                            </form>

                            <form action="actions/procesar_permisos.php" method="POST" style="width: 100%; margin-top: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="usuario_id" value="<?php echo $admin['id']; ?>">
                                <input type="hidden" name="accion" value="quitar_admin">
                                <button type="submit" class="btn-cancelar" style="font-size: 0.8em; padding: 8px;" onclick="return confirm('¿Seguro que quieres quitar el rango de admin a <?php echo htmlspecialchars($admin['nick']); ?>?');">Revocar Admin</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mensaje-vacio">No hay administradores registrados aparte de ti.</p>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>