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
        $error_busq = "Varios supervivientes encontrados. Por favor, sé más específico.";
    } else {
        $error_busq = "No se ha encontrado ninguna ficha con esos datos.";
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

// Determinar pestaña inicial
$tab_activa = (!empty($busqueda) || isset($_GET['tab_usuarios'])) ? 'usuarios' : 'admins';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control Superadmin</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.7">
    <style>
        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        .tab-button {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 1.1rem;
            font-weight: 800;
            padding: 10px 25px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px 8px 0 0;
            position: relative;
        }
        .tab-button:hover { color: var(--text-main); }
        .tab-button.active {
            color: var(--accent);
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
            box-shadow: 0 0 10px var(--accent);
        }
        .tab-content { display: none; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .busqueda-header {
            background: rgba(0,255,204,0.05);
            border: 1px solid var(--accent);
            padding: 25px;
            border-radius: var(--radius);
        }
    </style>
</head>
<body>
    <?php 
    $header_titulo = "Centro de Mando";
    $header_volver_link = "index.php";
    $header_volver_texto = "Wiki Principal";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-detalle max-w-1000">
        
        <!-- Selectores de Pestaña -->
        <div class="tabs-container">
            <button class="tab-button <?php echo $tab_activa == 'admins' ? 'active' : ''; ?>" onclick="switchTab('admins')">GESTOR DE EQUIPO</button>
            <button class="tab-button <?php echo $tab_activa == 'usuarios' ? 'active' : ''; ?>" onclick="switchTab('usuarios')">BUSCADOR DE USUARIOS</button>
        </div>

        <!-- CONTENIDO: GESTIÓN DE ADMINS -->
        <div id="tab-admins" class="tab-content <?php echo $tab_activa == 'admins' ? 'active' : ''; ?>">
            <section class="mb-50">
                <h2 class="mb-20">Aspirantes (Pendientes)</h2>
                <?php if (count($admins_pendientes) > 0): ?>
                    <div class="lista-admins">
                        <?php foreach ($admins_pendientes as $admin): ?>
                            <div class="admin-card">
                                <h3 class="admin-nick"><?php echo htmlspecialchars($admin['nick']); ?></h3>
                                <div class="admin-actions">
                                    <form action="actions/procesar_activar_admin.php" method="POST" class="form-activar">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                        <input type="text" name="nueva_password" required placeholder="Asignar clave..." class="input-password">
                                        <button type="submit" class="btn-activar">Activar</button>
                                    </form>
                                    <form action="actions/procesar_cancelar_admin.php" method="POST" class="w-100">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn-cancelar f-08" onclick="return confirm('¿Rechazar solicitud?');">Rechazar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mensaje-vacio">No hay solicitudes nuevas.</p>
                <?php endif; ?>
            </section>

            <section>
                <h2 class="accent-text mb-20">Administradores del Proyecto</h2>
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
                                        <label class="cursor-pointer d-flex align-center gap-10 f-09"><input type="checkbox" name="permiso_insertar_dino" <?php echo ($admin['permiso_insertar_dino'] == 1) ? 'checked' : ''; ?> class="w-auto"> Añadir Criaturas</label>
                                        <label class="cursor-pointer d-flex align-center gap-10 f-09"><input type="checkbox" name="permiso_eliminar_comentario" <?php echo ($admin['permiso_eliminar_comentario'] == 1) ? 'checked' : ''; ?> class="w-auto"> Borrar Comentarios</label>
                                        <label class="cursor-pointer d-flex align-center gap-10 f-09"><input type="checkbox" name="permiso_moderar_usuarios" <?php echo ($admin['permiso_moderar_usuarios'] == 1) ? 'checked' : ''; ?> class="w-auto"> Moderar (Vetos)</label>
                                    </div>
                                    <button type="submit" class="btn-activar f-085 p-10 bg-header-accent">Guardar Cambios</button>
                                </form>
                                <form action="actions/procesar_permisos.php" method="POST" class="w-100 mt-10">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="usuario_id" value="<?php echo $admin['id']; ?>">
                                    <input type="hidden" name="accion" value="quitar_admin">
                                    <button type="submit" class="btn-cancelar f-08 p-8" onclick="return confirm('¿Quitar rango de admin?');">Revocar Cargo</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mensaje-vacio">No hay administradores registrados.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- CONTENIDO: BUSCADOR DE USUARIOS -->
        <div id="tab-usuarios" class="tab-content <?php echo $tab_activa == 'usuarios' ? 'active' : ''; ?>">
            <div class="busqueda-header mb-40">
                <h2 class="accent-text mb-15">Registro Civil de Supervivientes</h2>
                <form action="panel_superadmin.php" method="GET" class="buscador" style="margin-bottom: 0; padding: 0; background: transparent; border: none; box-shadow: none;">
                    <input type="hidden" name="tab_usuarios" value="1">
                    <div style="display: flex; gap: 15px; width: 100%;">
                        <input type="text" name="buscar_usuario" placeholder="Introduce Nick o Email completo..." value="<?php echo htmlspecialchars($busqueda); ?>" style="flex: 1;">
                        <button type="submit">Localizar Sujeto</button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="panel_superadmin.php?tab_usuarios=1" class="boton-limpiar">Nueva Búsqueda</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($error_busq): ?>
                <div class="alerta-error"><?php echo $error_busq; ?></div>
            <?php endif; ?>

            <?php if (count($resultados_busq) > 1 && !$usuario_encontrado): ?>
                <div class="comentarios-lista mt-30">
                    <h3 class="mb-15">Usuarios encontrados con ese nombre:</h3>
                    <?php foreach ($resultados_busq as $res): ?>
                        <div class="comentario" style="border-left-color: var(--accent);">
                            <div class="comentario-header">
                                <span class="comentario-nick"><?php echo htmlspecialchars($res['nick']); ?></span>
                                <a href="panel_superadmin.php?buscar_usuario=<?php echo urlencode($res['nick']); ?>" class="btn-nav f-08">Revisar Expediente</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($usuario_encontrado): ?>
                <div class="ficha-principal border-accent-top mt-30">
                    <div class="flex-between-center mb-40" style="flex-wrap: wrap; gap: 20px;">
                        <div class="d-flex align-center gap-20">
                            <?php 
                            $f_u = $usuario_encontrado['foto_perfil'] ?? 'default.png';
                            $src_u = (strpos($f_u, 'http') === 0) ? $f_u : "assets/img/perfil/" . $f_u;
                            ?>
                            <img src="<?php echo htmlspecialchars($src_u); ?>" class="perfil-foto-main" style="margin: 0; width: 100px; height: 100px; border-width: 3px;">
                            <div>
                                <h1 class="f-15" style="margin-bottom: 5px;"><?php echo htmlspecialchars($usuario_encontrado['nick']); ?></h1>
                                <p class="accent-text f-09"><strong>RANGO:</strong> <?php echo strtoupper($usuario_encontrado['rol']); ?></p>
                            </div>
                        </div>
                        <a href="admin/moderar_usuario.php?id=<?php echo $usuario_encontrado['id']; ?>" class="boton-eliminar" style="background-color: #ff9800; border-radius: 8px;">Ir a Moderación</a>
                    </div>

                    <div class="info-grid mt-20" style="gap: 30px;">
                        <div class="dato"><span class="text-muted">ID:</span> <strong>#<?php echo $usuario_encontrado['id']; ?></strong></div>
                        <div class="dato"><span class="text-muted">Correo:</span> <strong><?php echo htmlspecialchars($usuario_encontrado['email']); ?></strong></div>
                        <div class="dato"><span class="text-muted">Estado:</span> 
                            <strong>
                            <?php 
                            if ($usuario_encontrado['ban_permanente']) echo "<span class='error-color'>BANEADO PERMANENTE</span>";
                            elseif (!empty($usuario_encontrado['baneado_hasta']) && strtotime($usuario_encontrado['baneado_hasta']) > time()) echo "<span class='error-color'>SUSPENDIDO</span>";
                            else echo "<span class='success-color'>ACTIVO</span>";
                            ?>
                            </strong>
                        </div>
                    </div>

                    <div class="mt-40">
                        <h3 class="mb-20">Historial de Aportaciones (<?php echo count($comentarios_usuario); ?>)</h3>
                        <div class="comentarios-lista">
                            <?php foreach ($comentarios_usuario as $c): ?>
                                <div class="comentario" style="border-left-color: var(--border-color);">
                                    <div class="comentario-header">
                                        <span class="f-09">Visto en: <a href="detalle.php?id=<?php echo $c['dino_id']; ?>" class="accent-text no-decoration"><strong><?php echo htmlspecialchars($c['dino_nombre']); ?></strong></a></span>
                                    </div>
                                    <p class="comentario-texto f-09"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($comentarios_usuario) == 0) echo "<p class='sin-datos'>El superviviente no ha dejado comentarios.</p>"; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));

            // Mostrar el seleccionado
            document.getElementById('tab-' + tab).classList.add('active');
            event.currentTarget.classList.add('active');
            
            // Actualizar URL sin recargar para mantener estado visual (opcional)
            const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab_' + tab + '=1';
            window.history.pushState({path:newurl},'',newurl);
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>