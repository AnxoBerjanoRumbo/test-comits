<?php
session_start();
include '../config/db.php';

// Verificar permisos: Solo admin con permiso de moderar o superadmin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['p_moderar']) || $_SESSION['p_moderar'] != 1) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_GET['id'];

// Obtener datos del usuario a moderar
$stmt = $conexion->prepare("SELECT id, nick, email, foto_perfil, rol, baneado_hasta, motivo_ban, ban_permanente FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuario no encontrado.");
}

// No moderar a Superadmins ni a ti mismo
if ($user['rol'] === 'superadmin' || $user['id'] == $_SESSION['usuario_id']) {
    header("Location: ../index.php?error=permisos");
    exit();
}

$header_titulo = "Moderar Usuario: " . htmlspecialchars($user['nick']);
$header_volver_link = "../index.php";
$header_volver_texto = "Cancelar";
$is_admin_panel = true;
include '../includes/header.php';
?>

<main class="contenedor-detalle" style="max-width: 800px; margin-top: 50px;">
    <div class="admin-card" style="display: flex; gap: 30px; align-items: flex-start; padding: 30px;">
        <div style="text-align: center;">
            <?php 
                $foto = $user['foto_perfil'] ?? 'default.png';
                $src_foto = (strpos($foto, 'http') === 0) ? $foto : "../assets/img/perfil/" . $foto;
            ?>
            <img src="<?php echo htmlspecialchars($src_foto); ?>" alt="Perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent);">
            <h2 style="margin-top: 15px;"><?php echo htmlspecialchars($user['nick']); ?></h2>
            <p style="color: #aaa;"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <div style="flex: 1;">
            <form action="../actions/admin/procesar_moderacion.php" method="POST" class="form-ark" style="background: transparent; padding: 0; box-shadow: none;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="usuario_id" value="<?php echo $user['id']; ?>">

                <div class="campo">
                    <label>Motivo de la Sanción / Mensaje para el usuario:</label>
                    <textarea name="motivo" placeholder="Escribe el motivo del ban o un mensaje para el usuario..." required style="min-height: 100px;"><?php echo htmlspecialchars($user['motivo_ban'] ?? ''); ?></textarea>
                </div>

                <div class="campo">
                    <label>Tipo de Sanción / Duración:</label>
                    <select name="tipo_ban" required>
                        <option value="ninguno">-- Seleccionar Duración --</option>
                        <option value="10m">Ban Temporal: 10 Minutos</option>
                        <option value="30m">Ban Temporal: 30 Minutos</option>
                        <option value="1d">Ban Temporal: 1 Día</option>
                        <option value="4d">Ban Temporal: 4 Días</option>
                        <option value="1w">Ban Temporal: 1 Semana</option>
                        <option value="3w">Ban Temporal: 3 Semanas</option>
                        <option value="1mo">Ban Temporal: 1 Mes</option>
                        <option value="6mo">Ban Temporal: 6 Meses</option>
                        <option value="1y">Ban Temporal: 1 Año</option>
                        <option value="permanente">Veto Total (Permanente)</option>
                        <option value="expulsion">Expulsión Total (Borrar Cuenta + Bloquear Correo)</option>
                        <?php if ($user['ban_permanente'] || ($user['baneado_hasta'] && strtotime($user['baneado_hasta']) > time())): ?>
                            <option value="quitar" style="color: #4CAF50; font-weight: bold;">[ QUITAR SANCIONES ACTUALES ]</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 15px;">
                    <button type="submit" class="boton-insertar" style="flex: 2;">Aplicar Sanción</button>
                    <a href="../index.php" class="btn-nav" style="flex: 1; text-align: center; background: #333;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($user['ban_permanente'] || ($user['baneado_hasta'] && strtotime($user['baneado_hasta']) > time())): ?>
        <div class="alerta-exito" style="margin-top: 30px; background: rgba(255, 68, 68, 0.1); border-color: #ff4444; color: #ff4444;">
            <strong>ESTADO ACTUAL:</strong> 
            <?php 
                if ($user['ban_permanente']) {
                    echo "Baneado Permanentemente.";
                } else {
                    echo "Baneado hasta: " . date("d/m/Y H:i", strtotime($user['baneado_hasta']));
                }
            ?>
            <br>
            <strong>Mensaje:</strong> <?php echo htmlspecialchars($user['motivo_ban']); ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
