<?php
session_start();
include 'config/db.php';
include 'config/sync_foto.php';

// 1. Recogemos el ID del dinosaurio desde la URL (con validación básica)
$id = isset($_GET['id']) ? $_GET['id'] : 1;

// 2. Consulta del dinosaurio
$sql_dino = "SELECT * FROM dinosaurios WHERE id = :id";
$stmt = $conexion->prepare($sql_dino);
$stmt->bindParam(':id', $id);
$stmt->execute();
$dino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dino) {
    header("Location: index.php");
    exit();
}

// 3. Consulta de mapas con JOIN 
$sql_mapas = "SELECT m.nombre_mapa 
              FROM mapas m 
              INNER JOIN dino_mapas dm ON m.id = dm.mapa_id 
              WHERE dm.dino_id = :id";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->bindParam(':id', $id);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

// 3b. Categorias del dinosaurio
$sql_cats = "SELECT c.nombre FROM categorias c
             INNER JOIN dino_categorias dc ON c.id = dc.categoria_id
             WHERE dc.dino_id = :id
             ORDER BY c.nombre ASC";
$stmt_cats = $conexion->prepare($sql_cats);
$stmt_cats->bindParam(':id', $id);
$stmt_cats->execute();
$cats_dino = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

// 4. Paginación de Comentarios
$comentarios_por_pagina = 10;
$pagina_actual = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $comentarios_por_pagina;

// 4.1. Contar total de comentarios RAÍZ (los que no son respuesta)
$sql_count = "SELECT COUNT(*) FROM comentarios WHERE dino_id = :id AND respuesta_a IS NULL";
$stmt_count = $conexion->prepare($sql_count);
$stmt_count->bindParam(':id', $id);
$stmt_count->execute();
$total_comentarios = $stmt_count->fetchColumn();
$total_paginas = ceil($total_comentarios / $comentarios_por_pagina);

// 4.2. Consulta de comentarios RAÍZ con LIMIT y OFFSET
$sql_comments = "SELECT c.*, u.nick, u.rol, u.foto_perfil 
                 FROM comentarios c 
                 JOIN usuarios u ON c.usuario_id = u.id 
                 WHERE c.dino_id = :id AND c.respuesta_a IS NULL
                 ORDER BY c.id DESC 
                 LIMIT :limit OFFSET :offset";
$stmt_comments = $conexion->prepare($sql_comments);
$stmt_comments->bindParam(':id', $id, PDO::PARAM_INT);
$stmt_comments->bindParam(':limit', $comentarios_por_pagina, PDO::PARAM_INT);
$stmt_comments->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_comments->execute();
$comentarios = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

// 4.3. Recoger todas las respuestas para estos comentarios
$respuestas = [];
if (count($comentarios) > 0) {
    $ids_raiz = array_column($comentarios, 'id');
    $placeholders = implode(',', array_fill(0, count($ids_raiz), '?'));
    
    $sql_resp = "SELECT c.*, u.nick, u.rol, u.foto_perfil 
                 FROM comentarios c 
                 JOIN usuarios u ON c.usuario_id = u.id 
                 WHERE c.respuesta_a IN ($placeholders)
                 ORDER BY c.id ASC";
    $stmt_resp = $conexion->prepare($sql_resp);
    $stmt_resp->execute($ids_raiz);
    $all_resp = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_resp as $r) {
        $respuestas[$r['respuesta_a']][] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dino['nombre']); ?> - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.3">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php 
    // Recuperar todos los parámetros GET menos el ID para mantener el contexto de búsqueda/filtros
    $params_volver = $_GET;
    unset($params_volver['id'], $params_volver['p'], $params_volver['status'], $params_volver['error']);
    
    $header_titulo = "Ficha de Criatura";
    $header_volver_link = "index.php" . (!empty($params_volver) ? '?' . http_build_query($params_volver) : '');
    $header_volver_texto = "Volver al listado";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-detalle">
        <?php if (isset($_GET['status']) && $_GET['status'] == 'edit_success'): ?>
            <div class="alerta-exito mb-20" style="text-align: center;">🦖 ¡Información de la criatura actualizada correctamente!</div>
        <?php endif; ?>
        <section class="ficha-principal">
            <h2 class="nombre-dino"><?php echo htmlspecialchars($dino['nombre']); ?></h2>
            
            <?php if(!empty($dino['imagen'])): ?>
                <div class="dino-img-detalle" style="text-align: center; margin-bottom: 20px;">
                    <?php 
                    $src_dino_d = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : "assets/img/dinos/" . $dino['imagen'];
                    
                    if (strpos($src_dino_d, 'res.cloudinary.com') !== false) {
                        // En el detalle usamos w_1200 o simplemente calidad auto
                        $src_dino_d = str_replace('/upload/', '/upload/f_auto,q_auto,w_1200,c_limit/', $src_dino_d);
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($src_dino_d); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" style="max-width: 100%; border-radius: 8px;" onerror="this.src='assets/img/dinos/default_dino.jpg'">
                </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="dato">
                    <strong>Especie:</strong> 
                    <span><?php echo htmlspecialchars($dino['especie']); ?></span>
                </div>
                <div class="dato">
                    <strong>Dieta:</strong> 
                    <span><?php echo htmlspecialchars($dino['dieta']); ?></span>
                </div>
            </div>

            <?php if(!empty($dino['descripcion'])): ?>
                <div class="dino-descripcion" style="margin-top: 20px;">
                    <h3>Descripción</h3>
                    <p style="white-space: pre-wrap; color: #ccc;"><?php echo htmlspecialchars($dino['descripcion']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="admin/editar.php?id=<?php echo $dino['id']; ?>" class="btn-nav btn-registro">Editar Criatura</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="seccion-mapas">
            <h3>Ubicación conocida</h3>
            <div class="lista-mapas">
                <?php if (count($mapas) > 0): ?>
                    <?php foreach ($mapas as $mapa): ?>
                        <span class="tag-mapa"><?php echo htmlspecialchars($mapa['nombre_mapa']); ?></span>
                    <?php
    endforeach; ?>
                <?php
else: ?>
                    <p class="sin-datos">No se han registrado avistamientos en los mapas actuales.</p>
                <?php
endif; ?>
            </div>
        </section>

        <?php if (count($cats_dino) > 0): ?>
        <section class="seccion-mapas">
            <h3>Categorías</h3>
            <div class="lista-mapas">
                <?php foreach ($cats_dino as $cat): ?>
                    <span class="tag-mapa"><?php echo htmlspecialchars($cat); ?></span>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="comentarios" class="seccion-comentarios" style="margin-top: 40px;">
            <h3>Comentarios y Aportes</h3>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form action="actions/procesar_comentario.php" method="POST" class="form-ark" style="margin-bottom: 25px;" id="form-comentario">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                    <input type="hidden" name="respuesta_a" id="input_respuesta_a" value="">
                    
                    <!-- Honeypot para evitar spam de bots -->
                    <div style="display:none !important;">
                        <label>No rellenar este campo:</label>
                        <input type="text" name="website_url" value="">
                    </div>
                    
                    <div id="indicador-respuesta" style="display: none; background: rgba(var(--accent-rgb), 0.1); padding: 10px; border-radius: 8px; margin-bottom: 10px; border: 1px dashed var(--accent);">
                        <span class="f-09">Respondiendo a <strong id="nick-respuesta">@usuario</strong></span>
                        <button type="button" onclick="cancelarRespuesta()" style="background: none; border: none; color: #ff5555; cursor: pointer; float: right; font-weight: bold;">[X] Cancelar</button>
                    </div>
                    <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                        <?php 
                        $foto_mismo = $_SESSION['foto_perfil'] ?? 'default.png';
                        $src_mismo = (strpos($foto_mismo, 'http') === 0) ? $foto_mismo : "assets/img/perfil/" . $foto_mismo;
                        ?>
                        <img src="<?php echo htmlspecialchars($src_mismo); ?>" 
                             alt="Mi Perfil" 
                             class="avatar-comentario"
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); flex-shrink: 0;"
                             onerror="this.src='assets/img/perfil/default.png'">
                        <textarea name="texto" required placeholder="Añade tu comentario o estrategia (máx 10.000 palabras)..." rows="4" style="width: 100%; border-radius: var(--radius);"></textarea>
                    </div>
                    <button type="submit" class="boton-insertar">Comentar</button>
                </form>
            <?php else: ?>
                <p style="background-color: #333; padding: 10px; border-radius: 5px; text-align: center;">Debes <a href="login.php" style="color: #4CAF50;">iniciar sesión</a> para dejar un comentario.</p>
            <?php endif; ?>

            <div class="comentarios-lista">
                <?php if (count($comentarios) > 0): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="comentario <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'comentario-admin' : ''; ?>">
                            <div class="comentario-header">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php 
                                    $foto_c = $c['foto_perfil'] ?? 'default.png';
                                    $src_c = (strpos($foto_c, 'http') === 0) ? $foto_c : "assets/img/perfil/" . $foto_c;
                                    
                                    // Solo mostrar enlace si eres moderador y no eres el propio usuario
                                    $can_moderate = isset($_SESSION['p_moderar']) && $_SESSION['p_moderar'] == 1 && $_SESSION['usuario_id'] != $c['usuario_id'] && $c['rol'] !== 'superadmin';
                                    ?>
                                    
                                    <?php if ($can_moderate): ?>
                                        <a href="admin/moderar_usuario.php?id=<?php echo $c['usuario_id']; ?>" title="Moderar a <?php echo htmlspecialchars($c['nick']); ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                                    <?php endif; ?>

                                    <img src="<?php echo htmlspecialchars($src_c); ?>" 
                                         alt="Avatar" 
                                         class="avatar-comentario"
                                         onerror="this.src='assets/img/perfil/default.png'">
                                    <strong class="comentario-nick <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'nick-admin' : ''; ?>">
                                        <?php echo htmlspecialchars($c['nick']); ?> <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '🛡️' : ''; ?>
                                    </strong>

                                    <?php if ($can_moderate): ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex align-center gap-10">
                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                                        <button type="button" class="btn-nav f-08" style="padding: 4px 10px; border-color: var(--accent); color: var(--accent);" onclick="prepararRespuesta(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nick'])); ?>')">Contestar</button>
                                    <?php endif; ?>
                                    <?php if(isset($_SESSION['usuario_id']) && (($_SESSION['is_admin'] ?? false) === true || $_SESSION['usuario_id'] == $c['usuario_id'])): ?>
                                        <form action="actions/borrar_comentario.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="comentario_id" value="<?php echo $c['id']; ?>">
                                            <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                            <button type="submit" onclick="return confirm('¿Borrar este comentario?');" class="btn-borrar-comentario">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                        </div>
                        
                        <?php if (isset($respuestas[$c['id']])): ?>
                            <?php foreach ($respuestas[$c['id']] as $r): ?>
                                <div class="comentario comentario-admin comentario-respuesta">
                                    <div class="comentario-header">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php 
                                            $foto_r = $r['foto_perfil'] ?? 'default.png';
                                            $src_r = (strpos($foto_r, 'http') === 0) ? $foto_r : "assets/img/perfil/" . $foto_r;
                                            ?>
                                            <img src="<?php echo htmlspecialchars($src_r); ?>" alt="Avatar" class="avatar-comentario" onerror="this.src='assets/img/perfil/default.png'">
                                            <strong class="comentario-nick nick-admin">
                                                <?php echo htmlspecialchars($r['nick']); ?> 🛡️
                                            </strong>
                                            <span class="f-08 text-muted">ha respondido</span>
                                        </div>
                                        <?php if(isset($_SESSION['usuario_id']) && (($_SESSION['is_admin'] ?? false) === true || $_SESSION['usuario_id'] == $r['usuario_id'])): ?>
                                            <form action="actions/borrar_comentario.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="comentario_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                                <button type="submit" onclick="return confirm('¿Borrar esta respuesta?');" class="btn-borrar-comentario">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($r['texto'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($total_paginas > 1): ?>
                        <div class="paginacion-comentarios" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $pagina_actual - 1; ?>" class="btn-pag">« Anterior</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $i; ?>" class="btn-pag <?php echo ($i === $pagina_actual) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $pagina_actual + 1; ?>" class="btn-pag">Siguiente »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="sin-datos" style="text-align: center; color: #888;">No hay comentarios todavía. ¡Sé el primero en aportar info!</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
            <div style="margin-top: 40px; text-align: center; border-top: 1px solid #444; padding-top: 20px;">
                <form action="actions/admin/procesar_eliminar.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres extinguir a <?php echo htmlspecialchars($dino['nombre']); ?>? Esta acción borrará sus datos de la base de datos y NO se puede deshacer.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $dino['id']; ?>">
                    <button type="submit" class="boton-eliminar" style="border: none; cursor: pointer;">
                        Eliminar Criatura
                    </button>
                </form>
            </div>
        <?php
endif; ?>

    <script>
        function prepararRespuesta(id, nick) {
            document.getElementById('input_respuesta_a').value = id;
            document.getElementById('nick-respuesta').innerText = '@' + nick;
            document.getElementById('indicador-respuesta').style.display = 'block';
            
            // Hacer scroll suave hasta el formulario
            document.getElementById('form-comentario').scrollIntoView({ behavior: 'smooth' });
            // Enfocar el textarea
            document.querySelector('#form-comentario textarea').focus();
        }

        function cancelarRespuesta() {
            document.getElementById('input_respuesta_a').value = '';
            document.getElementById('indicador-respuesta').style.display = 'none';
        }
    </script>
    <?php include 'includes/footer.php'; ?>