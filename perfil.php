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
            <div class="alerta-exito">
                Perfil actualizado correctamente.
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alerta-error">
                <?php 
                if ($_GET['error'] == 'pass_no_coincide') echo "Las contraseñas no coinciden.";
                elseif ($_GET['error'] == 'pass_corta') echo "La nueva contraseña debe tener al menos 4 caracteres.";
                elseif ($_GET['error'] == 'upload') echo "Error al subir la imagen. Comprueba que sea un formato válido.";
                elseif ($_GET['error'] == 'nick_invalido') echo "El nuevo apodo está vacío o supera los 25 caracteres.";
                elseif ($_GET['error'] == 'nick_reservado') echo "No puedes usar la palabra 'admin' en tu nombre por seguridad.";
                elseif ($_GET['error'] == 'nick_en_uso') echo "Ese apodo ya pertenece a otro superviviente.";
                else echo "Error al actualizar el perfil.";
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
                 onerror="this.src='assets/img/perfil/default.png'">
            <p><strong><?php echo htmlspecialchars($usuario['nick']); ?></strong> 
               <span class="accent-text">(<?php echo htmlspecialchars($usuario['rol']); ?>)</span>
            </p>
        </div>

        <!-- Formulario para la Foto de Perfil (Auto-envío) -->
        <form id="form-foto" action="actions/procesar_perfil.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div class="campo">
                <label>Cambiar foto de perfil:</label>
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" class="d-none">
                <button type="button" class="boton-insertar" onclick="document.getElementById('foto_perfil').click()">Seleccionar Nueva Imagen</button>
                <small class="texto-auxiliar">La foto se actualizará automáticamente al seleccionarla.</small>
            </div>
        </form>

        <hr class="separador">

        <!-- Formulario para cambiar el Nombre -->
        <form action="actions/procesar_cambio_nick.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <h3>Cambiar Apodo de Superviviente</h3>
            <div class="campo">
                <label>Nuevo apodo (Nick):</label>
                <input type="text" name="nuevo_nick" placeholder="Introduce tu nuevo nombre..." required maxlength="25" value="<?php echo htmlspecialchars($usuario['nick']); ?>">
                <small class="texto-auxiliar">Se actualizará retroactivamente en todos tus comentarios en la wiki.</small>
            </div>
            <button type="submit" class="boton-insertar">Actualizar Apodo</button>
        </form>

        <hr class="separador">

        <!-- Formulario para la Contraseña -->
        <form action="actions/procesar_password.php" method="POST" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <h3>Cambiar Contraseña</h3>
            <div class="campo">
                <label>Nueva contraseña:</label>
                <input type="password" name="nueva_password" placeholder="Mínimo 4 caracteres" required maxlength="100">
            </div>

            <div class="campo">
                <label>Confirmar nueva contraseña:</label>
                <input type="password" name="confirmar_password" placeholder="Repite la nueva contraseña" required maxlength="100">
            </div>

            <button type="submit" class="boton-insertar">Confirmar Cambio de Contraseña</button>
        </form>

        <?php if ($usuario['rol'] === 'admin' || $usuario['rol'] === 'superadmin'): ?>
            <hr class="separador">
            <?php
            $sql_mis_logs = "SELECT * FROM admin_logs WHERE id_usuario = :id ORDER BY fecha DESC LIMIT 30";
            $stmt_mis_logs = $conexion->prepare($sql_mis_logs);
            $stmt_mis_logs->execute([':id' => $usuario_id]);
            $mis_logs = $stmt_mis_logs->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div style="border:1px solid var(--border-color); border-left:4px solid #ff9800; border-radius:var(--radius); overflow:hidden; margin-top:10px;">
                <div style="padding:16px 22px; background:rgba(255,152,0,0.08); display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color);">
                    <span class="material-symbols-outlined" style="color:#ff9800;">history</span>
                    <h3 style="margin:0; color:#ff9800; font-size:1rem;">Mi Registro de Actividad</h3>
                    <span class="f-08 text-muted" style="margin-left:auto;"><?php echo count($mis_logs); ?> acciones</span>
                </div>
                <?php if (count($mis_logs) > 0): ?>
                <div style="max-height:400px; overflow-y:auto;">
                    <?php foreach ($mis_logs as $log):
                        $accion_lower = strtolower($log['accion']);
                        $log_color = '#ff9800'; $log_icon = 'edit';
                        if (str_contains($accion_lower, 'eliminar') || str_contains($accion_lower, 'borrar') || str_contains($accion_lower, 'expulsi')) {
                            $log_color = 'var(--error-color)'; $log_icon = 'delete';
                        } elseif (str_contains($accion_lower, 'añadir')) {
                            $log_color = 'var(--accent)'; $log_icon = 'add_circle';
                        } elseif (str_contains($accion_lower, 'levantar') || str_contains($accion_lower, 'mensaje')) {
                            $log_color = '#4caf50'; $log_icon = 'check_circle';
                        } elseif (str_contains($accion_lower, 'sancionar') || str_contains($accion_lower, 'ban')) {
                            $log_color = '#e91e63'; $log_icon = 'gavel';
                        }
                    ?>
                        <div style="display:flex; align-items:flex-start; gap:14px; padding:13px 20px; border-bottom:1px solid rgba(255,255,255,0.04);">
                            <span class="material-symbols-outlined" style="color:<?php echo $log_color; ?>; font-size:1.1rem; margin-top:2px; flex-shrink:0;"><?php echo $log_icon; ?></span>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; align-items:baseline; gap:8px; flex-wrap:wrap; margin-bottom:2px;">
                                    <strong style="color:<?php echo $log_color; ?>; font-size:0.88rem;"><?php echo htmlspecialchars($log['accion']); ?></strong>
                                    <span style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?></span>
                                </div>
                                <p style="margin:0; font-size:0.83rem; color:var(--text-muted); line-height:1.5;"><?php echo nl2br(htmlspecialchars($log['detalle'] ?? '')); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-muted f-09 p-15 text-center" style="margin:0;">Aún no tienes acciones registradas.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <script src="assets/js/perfil.js"></script>
    <?php include 'includes/footer.php'; ?>
