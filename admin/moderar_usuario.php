<?php
session_start();
include '../config/db.php';

// Verificar permisos: Solo admin con permiso de moderar o superadmin
$es_superadmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin';
$es_admin_moderador = isset($_SESSION['p_moderar']) && $_SESSION['p_moderar'] == 1;

if (!isset($_SESSION['usuario_id']) || (!$es_superadmin && !$es_admin_moderador)) {
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $header_titulo; ?> - ARK Hub</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.4">
</head>
<body class="admin-body">
    <?php include '../includes/header.php'; ?>

<main class="contenedor-moderacion">
    <div class="moderacion-card">
        <div class="moderacion-usuario-info">
            <?php 
                $foto = $user['foto_perfil'] ?? 'default.png';
                $src_foto = (strpos($foto, 'http') === 0) ? $foto : "../assets/img/perfil/" . $foto;
            ?>
            <img src="<?php echo htmlspecialchars($src_foto); ?>" alt="Perfil" class="moderacion-avatar">
            <h2 class="mt-15"><?php echo htmlspecialchars($user['nick']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <div class="moderacion-form-container">
            <form action="../actions/admin/procesar_moderacion.php" method="POST" class="form-ark form-moderacion">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="usuario_id" value="<?php echo $user['id']; ?>">

                <div class="campo">
                    <label>Motivo de la Sanción / Mensaje para el usuario:</label>
                    <textarea name="motivo" placeholder="Escribe el motivo del ban o un mensaje para el usuario..." required class="h-100"><?php echo htmlspecialchars($user['motivo_ban'] ?? ''); ?></textarea>
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
                            <option value="quitar" class="opcion-verde">[ QUITAR SANCIONES ACTUALES ]</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="botones-moderacion">
                    <button type="submit" class="boton-insertar btn-moderacion-aplicar">Aplicar Sanción</button>
                    <a href="../index.php" class="btn-nav btn-moderacion-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($user['ban_permanente'] || ($user['baneado_hasta'] && strtotime($user['baneado_hasta']) > time())): ?>
        <div class="alerta-ban-activa">
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
