<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

// --- Lógica de Gestión de Admins ---
$sql_pendientes = "SELECT * FROM usuarios WHERE rol = 'admin' AND password = ''";
$stmt_p = $conexion->prepare($sql_pendientes);
$stmt_p->execute();
$admins_pendientes = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$sql_activos = "SELECT * FROM usuarios WHERE rol = 'admin' AND password != ''";
$stmt_a = $conexion->prepare($sql_activos);
$stmt_a->execute();
$admins_activos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica de Buscador de Usuarios + Listado Completo ---
$busqueda = isset($_GET['buscar_usuario']) ? trim($_GET['buscar_usuario']) : '';

// Paginación para el listado COMPLETO
$users_por_pagina = 10;
$p_users = (isset($_GET['p_u']) && is_numeric($_GET['p_u'])) ? (int)$_GET['p_u'] : 1;
if ($p_users < 1) $p_users = 1;
$offset_users = ($p_users - 1) * $users_por_pagina;

$usuario_encontrado = null;
$comentarios_usuario = [];
$error_busq = null;
$resultados_busq = [];
$todos_los_usuarios = [];
$total_v_paginas = 0;

if (!empty($busqueda)) {
    $stmt_b = $conexion->prepare("SELECT * FROM usuarios WHERE (nick LIKE :busq OR email = :busq_directa) AND id != :my_id LIMIT 5");
    $stmt_b->execute([':busq' => "%$busqueda%", ':busq_directa' => $busqueda, ':my_id' => $_SESSION['usuario_id']]);
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

        // Si el usuario es admin, obtener también su registro de actividades
        if ($usuario_encontrado['rol'] === 'admin' || $usuario_encontrado['rol'] === 'superadmin') {
            $sql_logs = "SELECT * FROM admin_logs WHERE id_usuario = :u_id ORDER BY fecha DESC";
            $stmt_logs = $conexion->prepare($sql_logs);
            $stmt_logs->execute([':u_id' => $usuario_encontrado['id']]);
            $admin_logs_usuario = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $admin_logs_usuario = [];
        }
    }
} else {
    // Modo listado completo
    $sql_count_u = "SELECT COUNT(*) FROM usuarios WHERE id != :my_id";
    $stmt_count_u = $conexion->prepare($sql_count_u);
    $stmt_count_u->execute([':my_id' => $_SESSION['usuario_id']]);
    $total_u = $stmt_count_u->fetchColumn();
    $total_v_paginas = ceil($total_u / $users_por_pagina);

    $sql_all_u = "SELECT id, nick, email, rol, baneado_hasta, ban_permanente FROM usuarios WHERE id != :my_id ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt_all_u = $conexion->prepare($sql_all_u);
    $stmt_all_u->bindValue(':my_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmt_all_u->bindValue(':limit', $users_por_pagina, PDO::PARAM_INT);
    $stmt_all_u->bindValue(':offset', $offset_users, PDO::PARAM_INT);
    $stmt_all_u->execute();
    $todos_los_usuarios = $stmt_all_u->fetchAll(PDO::FETCH_ASSOC);
}

// --- Estadísticas Rápidas para el Dashboard ---
$total_dinos = $conexion->query("SELECT COUNT(*) FROM dinosaurios")->fetchColumn();
$total_users = $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// Sanciones: Usuarios baneados + Correos bloqueados
$total_bans_users = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE ban_permanente = 1 OR (baneado_hasta IS NOT NULL AND baneado_hasta > NOW())")->fetchColumn();
$total_blocked_emails = $conexion->query("SELECT COUNT(*) FROM emails_bloqueados")->fetchColumn();
$total_bans = $total_bans_users + $total_blocked_emails;

$acciones_24h = $conexion->query("SELECT COUNT(*) FROM admin_logs WHERE fecha >= NOW() - INTERVAL 1 DAY")->fetchColumn();

// --- Lógica de Lista Negra ---
$sql_blacklist = "SELECT * FROM emails_bloqueados ORDER BY fecha_bloqueo DESC";
$stmt_bl = $conexion->prepare($sql_blacklist);
$stmt_bl->execute();
$emails_bloqueados = $stmt_bl->fetchAll(PDO::FETCH_ASSOC);

// Determinar pestaña inicial
$tab_activa = 'admins';
if (!empty($busqueda) || isset($_GET['tab_usuarios'])) $tab_activa = 'usuarios';
if (isset($_GET['tab_blacklist'])) $tab_activa = 'blacklist';
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
        .tab-button:hover {
            color: var(--text-light);
            background: rgba(255, 255, 255, 0.05);
        }
        .tab-button.active {
            color: var(--accent);
            border-bottom: 2px solid var(--accent);
            margin-bottom: -2px;
        }
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Dashboard Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s, background 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent);
        }
        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 5px;
        }
        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tab-badge {
            background: var(--accent);
            color: #000;
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 50%;
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: 900;
        }

        /* Paginación */
        .btn-pag {
            display: inline-block;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .btn-pag:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(var(--accent-rgb), 0.1);
        }
        .btn-pag.active {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
            font-weight: bold;
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

    <main class="contenedor-principal" style="width: 95%; margin: 40px auto;">
        
        <!-- Dashboard Summary Header -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_users; ?></span>
                <span class="stat-label">Supervivientes</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $total_dinos; ?></span>
                <span class="stat-label">Criaturas Wiki</span>
            </div>
            <div class="stat-card">
                <span class="stat-value" style="color: var(--error-color);"><?php echo $total_bans; ?></span>
                <span class="stat-label">Sanciones Activas</span>
            </div>
            <div class="stat-card">
                <span class="stat-value" style="color: #4caf50;"><?php echo $acciones_24h; ?></span>
                <span class="stat-label">Acciones (24h)</span>
            </div>
        </div>

        <div class="tabs-container">
            <button class="tab-button <?php echo $tab_activa == 'admins' ? 'active' : ''; ?>" onclick="switchTab(event, 'admins')">
                Gestión de Equipo
                <?php if (count($admins_pendientes) > 0): ?>
                    <span class="tab-badge"><?php echo count($admins_pendientes); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button <?php echo $tab_activa == 'usuarios' ? 'active' : ''; ?>" onclick="switchTab(event, 'usuarios')">Expedientes de Supervivientes</button>
            <button class="tab-button <?php echo $tab_activa == 'blacklist' ? 'active' : ''; ?>" onclick="switchTab(event, 'blacklist')">Lista Negra</button>
        </div>

        <!-- CONTENIDO: GESTIÓN DE ADMINS -->
        <div id="gestion-equipo" class="tab-content <?php echo $tab_activa == 'admins' ? 'active' : ''; ?>">
            <section class="mb-50">
                <h2 class="mb-20">Aspirantes (Pendientes)</h2>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'actualizado'): ?>
                    <div class="alerta-exito mb-20">Se ha activado la credencial del nuevo Administrador.</div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'permisos_actualizados'): ?>
                    <div class="alerta-exito mb-20">Permisos de usuario actualizados correctamente.</div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'admin_quitado'): ?>
                    <div class="alerta-exito mb-20">Rango de Administrador revocado satisfactoriamente.</div>
                <?php endif; ?>

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
                                        <label class="cursor-pointer d-flex align-center gap-10 f-09"><input type="checkbox" name="permiso_insertar_dino" <?php echo ($admin['permiso_insertar_dino'] == 1) ? 'checked' : ''; ?> class="w-auto"> Añadir/Editar Criaturas</label>
                                        <label class="cursor-pointer d-flex align-center gap-10 f-09"><input type="checkbox" name="permiso_eliminar_comentario" <?php echo ($admin['permiso_eliminar_comentario'] == 1) ? 'checked' : ''; ?> class="w-auto"> Borrar/Contestar Comentarios</label>
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

            <section style="margin-top: 40px; margin-bottom: 50px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(0,255,204,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-outlined" style="color:var(--accent);font-size:1.2rem;">send</span>
                    </div>
                    <h2 class="accent-text" style="margin:0;">Enviar Comunicado al Equipo</h2>
                </div>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'mensaje_enviado'): ?>
                    <div class="alerta-exito mb-20">Comunicado enviado correctamente a todos los administradores.</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'mensaje_vacio'): ?>
                    <div class="alerta-error mb-20">Debes escribir algo en el mensaje antes de enviarlo.</div>
                <?php endif; ?>

                <form id="formulario-comunicado" action="actions/admin/procesar_mensaje_admins.php" method="POST" class="form-ark bg-soft-dark border-radius" style="padding: 25px; border-top: 1px solid var(--border-color); border-left: 4px solid var(--accent); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="campo mb-20">
                        <label style="display:block; margin-bottom:10px; font-weight:bold;">
                            Destinatario:
                        </label>
                        <select name="destinatario" required class="w-100 p-10 border-radius bg-black text-main" style="border: 1px solid var(--border-color); appearance: auto;">
                            <option value="todos">👉 A Todos los Administradores</option>
                            <optgroup label="Admins Específicos">
                                <?php 
                                $todos_admins = array_merge($admins_activos, $admins_pendientes);
                                foreach ($todos_admins as $adm): 
                                ?>
                                    <option value="<?php echo $adm['id']; ?>">
                                        <?php echo htmlspecialchars($adm['nick'] ?: $adm['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="campo mb-20">
                        <label style="display:block; margin-bottom:10px; font-weight:bold;">
                            Asunto Opcional <span style="font-weight:normal; font-size:0.85em; color:var(--text-muted);">(se mostrará en la alerta)</span>
                        </label>
                        <input type="text" name="asunto" placeholder="Ej: Nueva Política de Moderación">
                    </div>
                    
                    <div class="campo mb-20">
                        <label style="display:block; margin-bottom:10px; font-weight:bold;">
                            Mensaje:
                        </label>
                        <textarea name="mensaje" placeholder="Escribe aquí las instrucciones o el comunicado para el equipo de admins..." style="min-height: 120px;" required></textarea>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <button type="submit" class="boton-insertar w-100">Enviar mensaje</button>
                        <small class="texto-auxiliar d-block text-center mt-10" style="color: var(--text-muted);">El resto de administradores recibirán una alerta de tu parte con este mensaje.</small>
                    </div>
                </form>
            </section>
        </div>

        <!-- CONTENIDO: BUSCADOR DE USUARIOS -->
        <div id="buscador-usuarios" class="tab-content <?php echo $tab_activa == 'usuarios' ? 'active' : ''; ?>">
            <div class="busqueda-header mb-40">
                <h2 class="accent-text mb-15">Registro Civil de Supervivientes</h2>
                <form action="panel_superadmin.php#buscador-usuarios" method="GET" style="margin-bottom:0;">
                    <input type="hidden" name="tab_usuarios" value="1">
                    <div style="display:flex; gap:12px; width:100%; align-items:center;">
                        <div style="flex:1; position:relative; display:flex; align-items:center;">
                            <span class="material-symbols-outlined" style="position:absolute; left:12px; color:var(--text-muted); font-size:1.1rem; pointer-events:none;">search</span>
                            <input type="text" name="buscar_usuario" placeholder="Introduce Nick o Email completo..."
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                                style="width:100%; padding:12px 14px 12px 40px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); border-radius:8px; font-family:inherit; font-size:0.95rem; outline:none; transition:border-color 0.2s;"
                                onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                        </div>
                        <button type="submit" style="background:var(--accent); color:var(--accent-text); border:none; padding:12px 22px; border-radius:8px; font-weight:700; font-size:0.9rem; font-family:inherit; cursor:pointer; transition:all 0.2s; white-space:nowrap; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='var(--accent-hover)'" onmouseout="this.style.background='var(--accent)'">
                            <span class="material-symbols-outlined" style="font-size:1rem;">manage_search</span>
                            Localizar Sujeto
                        </button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="panel_superadmin.php?tab_usuarios=1"
                                style="display:inline-flex; align-items:center; gap:5px; padding:12px 16px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-muted); border-radius:8px; text-decoration:none; font-size:0.88rem; transition:all 0.2s; white-space:nowrap;"
                                onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                                <span class="material-symbols-outlined" style="font-size:0.95rem;">close</span>
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($error_busq): ?>
                <div class="alerta-error"><?php echo $error_busq; ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'usuario_borrado'): ?>
                <div class="alerta-exito mb-20">Expediente de usuario eliminado permanentemente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'autoborrado'): ?>
                <div class="alerta-error mb-20">Error: No puedes eliminar tu propio expediente de superadministrador.</div>
            <?php endif; ?>

            <?php if (empty($busqueda) && !empty($todos_los_usuarios)): ?>
                <div class="comentarios-lista mt-30" id="listado-maestro">
                    <h3 class="mb-20">Listado Maestro de Supervivientes (Página <?php echo $p_users; ?>)</h3>
                    <div class="grid-equipo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                        <?php foreach ($todos_los_usuarios as $u): ?>
                            <div class="admin-card" style="display: flex; justify-content: space-between; align-items: center; padding: 25px; border-top: 5px solid <?php 
                                if ($u['ban_permanente'] || ($u['baneado_hasta'] && strtotime($u['baneado_hasta']) > time())) echo 'var(--error-color)';
                                else echo 'var(--accent)';
                            ?>;">
                                <div>
                                    <strong class="d-block f-11"><?php echo htmlspecialchars($u['nick']); ?></strong>
                                    <span class="f-08 text-muted d-block"><?php echo htmlspecialchars($u['email']); ?></span>
                                    <span class="f-07 <?php echo ($u['rol'] == 'admin' ? 'accent-text' : ''); ?>">Rango: <?php echo strtoupper($u['rol']); ?></span>
                                </div>
                                <a href="panel_superadmin.php?buscar_usuario=<?php echo urlencode($u['nick']); ?>" class="btn-nav f-08">Ficha</a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_v_paginas > 1): ?>
                        <div class="paginacion mt-30 text-center" style="display: flex; justify-content: center; gap: 8px;">
                            <?php for ($i = 1; $i <= $total_v_paginas; $i++): ?>
                                <a href="panel_superadmin.php?tab_usuarios=1&p_u=<?php echo $i; ?>#listado-maestro" class="btn-pag <?php echo ($i == $p_users) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
                <?php if (isset($_GET['status']) && $_GET['status'] == 'usuario_borrado'): ?>
                    <div class="alerta-error" style="background: rgba(255, 68, 68, 0.2); border-color: #ff4444; color: #fff;">
                        Usuario eliminado por completo de la base de datos.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] == 'autoborrado'): ?>
                    <div class="alerta-error">No puedes eliminar tu propia cuenta de superadministrador.</div>
                <?php endif; ?>

                <div class="ficha-principal border-accent-top mt-30">
                    <div class="d-flex align-start mb-40" style="justify-content: space-between; flex-wrap: wrap; gap: 30px;">
                        <div class="d-flex align-center" style="flex: 1; min-width: 0; gap: 20px;">
                            <?php 
                            $f_u = $usuario_encontrado['foto_perfil'] ?? 'default.png';
                            $src_u = (strpos($f_u, 'http') === 0) ? $f_u : "assets/img/perfil/" . $f_u;
                            ?>
                            <img src="<?php echo htmlspecialchars($src_u); ?>" class="perfil-foto-main" style="margin: 0; width: 100px; height: 100px; border-width: 3px; flex-shrink: 0;">
                            <div style="min-width: 0; flex: 1; padding-left: 10px;">
                                <h1 class="f-15" style="margin-bottom: 5px; word-break: break-word; line-height: 1.2;"><?php echo htmlspecialchars($usuario_encontrado['nick']); ?></h1>
                                <p class="accent-text f-09"><strong>RANGO:</strong> <?php echo strtoupper($usuario_encontrado['rol']); ?></p>
                            </div>
                        </div>
                        <div class="d-flex gap-10 flex-wrap">
                            <a href="admin/moderar_usuario.php?id=<?php echo $usuario_encontrado['id']; ?>" class="boton-eliminar" style="background-color: #ff9800; border-radius: 8px;">Ir a Moderación</a>
                            
                            <form action="actions/admin/procesar_borrar_usuario.php" method="POST" onsubmit="return confirm('¿ESTÁS SEGURO? Esto borrará al usuario y todos sus comentarios PERMANENTEMENTE.');" style="margin:0; padding:0; background:none; border:none; box-shadow:none;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id_usuario" value="<?php echo $usuario_encontrado['id']; ?>">
                                <button type="submit" class="boton-eliminar" style="border-radius: 8px;">Eliminar</button>
                            </form>
                        </div>
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

                    <?php if ($usuario_encontrado['rol'] === 'admin' || $usuario_encontrado['rol'] === 'superadmin'): ?>
                    <div class="mt-40" style="border:1px solid var(--border-color); border-left:4px solid #ff9800; border-radius:var(--radius); overflow:hidden;">
                        <div style="padding:18px 25px; background:rgba(255,152,0,0.08); display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border-color);">
                            <span class="material-symbols-outlined" style="color:#ff9800;">history</span>
                            <h3 style="margin:0; color:#ff9800; font-size:1rem;">Registro de Actividad del Admin</h3>
                            <span class="f-08 text-muted" style="margin-left:auto;"><?php echo count($admin_logs_usuario); ?> acciones registradas</span>
                        </div>
                        <?php if (count($admin_logs_usuario) > 0): ?>
                        <div style="max-height:350px; overflow-y:auto;">
                            <?php foreach ($admin_logs_usuario as $log):
                                $accion_lower = strtolower($log['accion']);
                                $log_color = '#ff9800';
                                $log_icon = 'edit';
                                if (str_contains($accion_lower, 'eliminar') || str_contains($accion_lower, 'borrar') || str_contains($accion_lower, 'expulsi')) {
                                    $log_color = 'var(--error-color)'; $log_icon = 'delete';
                                } elseif (str_contains($accion_lower, 'añadir') || str_contains($accion_lower, 'añadir')) {
                                    $log_color = 'var(--accent)'; $log_icon = 'add_circle';
                                } elseif (str_contains($accion_lower, 'levantar') || str_contains($accion_lower, 'mensaje')) {
                                    $log_color = '#4caf50'; $log_icon = 'check_circle';
                                } elseif (str_contains($accion_lower, 'sancionar') || str_contains($accion_lower, 'ban')) {
                                    $log_color = '#e91e63'; $log_icon = 'gavel';
                                }
                            ?>
                                <div style="display:flex; align-items:flex-start; gap:14px; padding:14px 22px; border-bottom:1px solid rgba(255,255,255,0.04);">
                                    <span class="material-symbols-outlined" style="color:<?php echo $log_color; ?>; font-size:1.1rem; margin-top:2px; flex-shrink:0;"><?php echo $log_icon; ?></span>
                                    <div style="flex:1; min-width:0;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:3px;">
                                            <strong style="color:<?php echo $log_color; ?>; font-size:0.9rem;"><?php echo htmlspecialchars($log['accion']); ?></strong>
                                            <span class="f-08 text-muted" style="white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?></span>
                                        </div>
                                        <p style="margin:0; font-size:0.85rem; color:var(--text-muted); line-height:1.5;"><?php echo nl2br(htmlspecialchars($log['detalle'] ?? '')); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-muted f-09 p-20 text-center" style="margin:0;">Este administrador no tiene acciones registradas todavía.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTENIDO: LISTA NEGRA -->
        <div id="lista-negra" class="tab-content <?php echo $tab_activa == 'blacklist' ? 'active' : ''; ?>">
            <section class="mb-50">
                <h2 class="error-color mb-15">Zona de Exclusión (Correos Bloqueados)</h2>
                <p class="text-muted mb-30">Los correos en esta lista han sido expulsados permanentemente. No podrán crear nuevas cuentas.</p>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'desbloqueado'): ?>
                    <div class="alerta-exito mb-20">Email desbloqueado. El usuario ya puede volver a registrarse.</div>
                <?php endif; ?>

                <?php if (count($emails_bloqueados) > 0): ?>
                    <div class="comentarios-lista">
                        <?php foreach ($emails_bloqueados as $e): ?>
                            <div class="comentario" style="border-left-color: var(--error-color);">
                                <div class="comentario-header">
                                    <span class="comentario-nick"><?php echo htmlspecialchars($e['email']); ?></span>
                                    <span class="f-08 text-muted"><?php echo date("d/m/Y H:i", strtotime($e['fecha_bloqueo'])); ?></span>
                                </div>
                                <p class="comentario-texto f-09 mb-15"><strong>Motivo:</strong> <?php echo htmlspecialchars($e['motivo'] ?? 'Incumplimiento de normas (Expulsión Total)'); ?></p>
                                
                                <form action="actions/admin/procesar_desbloqueo_email.php" method="POST" onsubmit="return confirm('¿Retirar el bloqueo a este email? Podrá volver a crearse una cuenta.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id_bloqueo" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="btn-nav f-08" style="border-color: var(--error-color); color: var(--error-color);">Retirar de Lista Negra</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mensaje-vacio">No hay ningún correo en la lista negra.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        const tabIdMap = {
            'admins': 'gestion-equipo',
            'usuarios': 'buscador-usuarios',
            'blacklist': 'lista-negra'
        };

        function switchTab(e, tab) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));

            // Mostrar el seleccionado usando el mapa de IDs correcto
            const targetId = tabIdMap[tab];
            if (targetId) document.getElementById(targetId).classList.add('active');
            e.currentTarget.classList.add('active');
            
            // Actualizar URL sin recargar para mantener estado visual
            const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab_' + tab + '=1';
            window.history.pushState({path:newurl},'',newurl);
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>