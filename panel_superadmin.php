<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';
include 'config/sync_foto.php';

// --- Lógica de Gestión de Admins ---
$sql_pendientes = "SELECT * FROM usuarios WHERE rol = 'admin' AND password = ''";
$stmt_p = $conexion->prepare($sql_pendientes);
$stmt_p->execute();
$admins_pendientes = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$sql_activos = "SELECT * FROM usuarios WHERE rol = 'admin' AND password != ''";
$stmt_a = $conexion->prepare($sql_activos);
$stmt_a->execute();
$admins_activos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica de Buscador de Usuarios ---
$busqueda = isset($_GET['buscar_usuario']) ? trim($_GET['buscar_usuario']) : '';
$usuario_encontrado = null;
$comentarios_usuario = [];
$error_busq = null;
$resultados_busq = [];

if (!empty($busqueda)) {
    $stmt_b = $conexion->prepare("SELECT * FROM usuarios WHERE nick LIKE :busq OR email = :busq_directa LIMIT 5");
    $stmt_b->execute([':busq' => "%$busqueda%", ':busq_directa' => $busqueda]);
    $resultados_busq = $stmt_b->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultados_busq) === 1) {
        $usuario_encontrado = $resultados_busq[0];
    } elseif (count($resultados_busq) > 1) {
        $error_busq = "Se han encontrado varios resultados. Por favor, sé más específico.";
    } else {
        $error_busq = "No se ha encontrado ningún usuario con ese nick o email.";
    }

    if ($usuario_encontrado) {
        $sql_c = "SELECT c.*, d.nombre as dino_nombre 
                FROM comentarios c 
                JOIN dinosaurios d ON c.dino_id = d.id 
                WHERE c.usuario_id = :u_id 
                ORDER BY c.id DESC";
        $stmt_c = $conexion->prepare($sql_c);
        $stmt_c->execute([':u_id' => $usuario_encontrado['id']]);
        $comentarios_usuario = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Superadministrador</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.6">
    <style>
        .admin-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            max-width: 1400px !important;
            margin: 0 auto;
        }
        .admin-main-col { flex: 1; min-width: 0; }
        .admin-side-col { 
            flex: 0 0 400px; 
            background: var(--bg-card); 
            padding: 25px; 
            border-radius: var(--radius); 
            border: 1px solid var(--border-color);
            position: sticky;
            top: 20px;
        }
        @media (max-width: 1100px) {
            .admin-layout { flex-direction: column; }
            .admin-side-col { flex: none; width: 100%; position: static; }
        }
    </style>
</head>
<body>
    <?php 
    $header_titulo = "Panel Superadmin";
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-detalle admin-layout">
        <div class="admin-main-col">
            <!-- GESTIÓN DE ADMINS (IZQUIERDA) -->
            <section class="mb-50">
                <h2 class="mb-20">Solicitudes de Administrador</h2>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'actualizado'): ?>
                    <div class="alerta-exito">✅ Contraseña asignada correctamente.</div>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'cancelado'): ?>
                    <div class="alerta-error">❌ Solicitud eliminada.</div>
                <?php endif; ?>

                <?php if (count($admins_pendientes) > 0): ?>
                    <div class="lista-admins">
                        <?php foreach ($admins_pendientes as $admin): ?>
                            <div class="admin-card">
                                <h3 class="admin-nick"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                                <p class="admin-estado">Pendiente de activación</p>
                                <div class="admin-actions">
                                    <form action="actions/procesar_activar_admin.php" method="POST" class="form-activar">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                        <input type="text" name="nueva_password" required placeholder="Clave..." class="input-password">
                                        <button type="submit" class="btn-activar">Activar</button>
                                    </form>
                                    <form action="actions/procesar_cancelar_admin.php" method="POST" class="w-100">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn-cancelar f-08" onclick="return confirm('¿Rechazar?');">Rechazar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mensaje-vacio">No hay solicitudes pendientes.</p>
                <?php endif; ?>
            </section>

            <section>
                <h2 class="accent-text mb-20">Administradores Oficiales</h2>
                <?php if (count($admins_activos) > 0): ?>
                    <div class="lista-admins">
                        <?php foreach ($admins_activos as $admin): ?>
                            <div class="admin-card border-accent-top">
                                <h3 class="admin-nick accent-text"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                                <form action="actions/procesar_permisos.php" method="POST" class="admin-actions p-15 bg-soft-dark border-radius mt-10">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="usuario_id" value="<?php echo $admin['id']; ?>">
                                    <input type="hidden" name="accion" value="actualizar_permisos">
                                    <div class="flex-column align-start gap-10 mb-15">
                                        <label class="cursor-pointer d-flex align-center gap-10 f-08"><input type="checkbox" name="permiso_insertar_dino" <?php echo ($admin['permiso_insertar_dino'] == 1) ? 'checked' : ''; ?> class="w-auto"> Insertar Dinos</label>
                                        <label class="cursor-pointer d-flex align-center gap-10 f-08"><input type="checkbox" name="permiso_eliminar_comentario" <?php echo ($admin['permiso_eliminar_comentario'] == 1) ? 'checked' : ''; ?> class="w-auto"> Borrar Coment.</label>
                                        <label class="cursor-pointer d-flex align-center gap-10 f-08"><input type="checkbox" name="permiso_moderar_usuarios" <?php echo ($admin['permiso_moderar_usuarios'] == 1) ? 'checked' : ''; ?> class="w-auto"> Moderación</label>
                                    </div>
                                    <button type="submit" class="btn-activar f-08 p-8 bg-header-accent">Guardar</button>
                                </form>
                                <form action="actions/procesar_permisos.php" method="POST" class="w-100 mt-10">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="usuario_id" value="<?php echo $admin['id']; ?>">
                                    <input type="hidden" name="accion" value="quitar_admin">
                                    <button type="submit" class="btn-cancelar f-08 p-8" onclick="return confirm('¿Quitar admin?');">Revocar</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mensaje-vacio">No hay otros administradores.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- BUSCADOR DE USUARIOS (DERECHA) -->
        <aside class="admin-side-col">
            <h2 class="accent-text mb-20">Buscador de Supervivientes</h2>
            <form action="panel_superadmin.php" method="GET" class="form-ark mb-20">
                <div class="flex-column gap-10">
                    <input type="text" name="buscar_usuario" placeholder="Nick o Email..." value="<?php echo htmlspecialchars($busqueda); ?>" class="input-password" style="text-align: left;">
                    <button type="submit" class="boton-insertar f-09 p-10">Buscar</button>
                    <?php if (!empty($busqueda)): ?>
                        <a href="panel_superadmin.php" class="btn-cancelar text-center f-08" style="text-decoration: none;">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($error_busq): ?>
                <div class="alerta-error f-08"><?php echo $error_busq; ?></div>
            <?php endif; ?>

            <?php if (count($resultados_busq) > 1 && !$usuario_encontrado): ?>
                <div class="comentarios-lista">
                    <p class="f-08 text-muted mb-10">Varios resultados encontrados:</p>
                    <?php foreach ($resultados_busq as $res): ?>
                        <div class="comentario p-10" style="margin-bottom: 5px;">
                            <a href="panel_superadmin.php?buscar_usuario=<?php echo urlencode($res['nick']); ?>" class="accent-text no-decoration f-09"><strong><?php echo htmlspecialchars($res['nick']); ?></strong></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($usuario_encontrado): ?>
                <div class="ficha-principal p-15 border-accent-top">
                    <div class="d-flex align-center gap-10 mb-15">
                        <?php 
                        $f = $usuario_encontrado['foto_perfil'] ?? 'default.png';
                        $src = (strpos($f, 'http') === 0) ? $f : "assets/img/perfil/" . $f;
                        ?>
                        <img src="<?php echo htmlspecialchars($src); ?>" class="avatar-header" style="width: 50px; height: 50px;">
                        <div>
                            <h3 class="f-11" style="margin:0;"><?php echo htmlspecialchars($usuario_encontrado['nick']); ?></h3>
                            <span class="f-08 text-muted"><?php echo htmlspecialchars($usuario_encontrado['rol']); ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-15 f-08">
                        <p class="mb-5"><strong>Email:</strong> <?php echo htmlspecialchars($usuario_encontrado['email']); ?></p>
                        <p><strong>Estado:</strong> 
                            <?php 
                            if ($usuario_encontrado['ban_permanente']) echo "<span class='error-color'>BANEADO PERM.</span>";
                            elseif (!empty($usuario_encontrado['baneado_hasta']) && strtotime($usuario_encontrado['baneado_hasta']) > time()) echo "<span class='error-color'>SUSPENDIDO</span>";
                            else echo "<span class='success-color'>ACTIVO</span>";
                            ?>
                        </p>
                    </div>

                    <a href="admin/moderar_usuario.php?id=<?php echo $usuario_encontrado['id']; ?>" class="boton-insertar f-08 p-8" style="background-color: #ff9800; text-align: center; text-decoration: none; display: block;">Moderar / Vetar</a>

                    <hr class="separador" style="margin: 15px 0;">
                    <h4 class="f-09 mb-10">Comentarios (<?php echo count($comentarios_usuario); ?>)</h4>
                    <div class="comentarios-lista" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($comentarios_usuario as $c): ?>
                            <div class="comentario p-10" style="font-size: 0.85rem;">
                                <p class="mb-5">En: <strong><?php echo htmlspecialchars($c['dino_nombre']); ?></strong></p>
                                <p class="text-muted"><?php echo htmlspecialchars(substr($c['texto'], 0, 50)) . '...'; ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($comentarios_usuario) == 0) echo "<p class='sin-datos f-08'>Sin comentarios.</p>"; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>