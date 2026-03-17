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

// 3. Consulta de mapas con JOIN 
$sql_mapas = "SELECT m.nombre_mapa 
              FROM mapas m 
              INNER JOIN dino_mapas dm ON m.id = dm.mapa_id 
              WHERE dm.dino_id = :id";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->bindParam(':id', $id);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

// 4. Paginación de Comentarios
$comentarios_por_pagina = 10;
$pagina_actual = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $comentarios_por_pagina;

// 4.1. Contar total de comentarios para este dino
$sql_count = "SELECT COUNT(*) FROM comentarios WHERE dino_id = :id";
$stmt_count = $conexion->prepare($sql_count);
$stmt_count->bindParam(':id', $id);
$stmt_count->execute();
$total_comentarios = $stmt_count->fetchColumn();
$total_paginas = ceil($total_comentarios / $comentarios_por_pagina);

// 4.2. Consulta de comentarios con LIMIT y OFFSET
$sql_comments = "SELECT c.*, u.nick, u.rol, u.foto_perfil 
                 FROM comentarios c 
                 JOIN usuarios u ON c.usuario_id = u.id 
                 WHERE c.dino_id = :id 
                 ORDER BY c.id DESC 
                 LIMIT :limit OFFSET :offset";
$stmt_comments = $conexion->prepare($sql_comments);
$stmt_comments->bindParam(':id', $id, PDO::PARAM_INT);
$stmt_comments->bindParam(':limit', $comentarios_por_pagina, PDO::PARAM_INT);
$stmt_comments->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_comments->execute();
$comentarios = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dino['nombre']; ?> - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.3">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php 
    $header_titulo = "Ficha de Criatura";
    $header_volver_link = "index.php";
    $header_volver_texto = "Volver al listado";
    include 'includes/header.php'; 
    ?>

    <main class="contenedor-detalle">
        <section class="ficha-principal">
            <h2 class="nombre-dino"><?php echo htmlspecialchars($dino['nombre']); ?></h2>
            
            <?php if(!empty($dino['imagen'])): ?>
                <div class="dino-img-detalle text-center mb-20">
                    <?php 
                    $src_dino_d = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : "assets/img/dinos/" . $dino['imagen'];
                    ?>
                    <img src="<?php echo htmlspecialchars($src_dino_d); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" class="w-100-max border-8">
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
                <div class="dino-descripcion mt-20">
                    <h3>Descripción</h3>
                    <p class="pre-wrap text-muted"><?php echo htmlspecialchars($dino['descripcion']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
                <div class="mt-20 text-center">
                    <a href="admin/editar.php?id=<?php echo $dino['id']; ?>" class="btn-nav btn-registro btn-edit">Editar Criatura</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="seccion-mapas">
            <h3>Ubicación conocida</h3>
            <div class="lista-mapas">
                <?php if (count($mapas) > 0): ?>
                    <?php foreach ($mapas as $mapa): ?>
                        <span class="tag-mapa"><?php echo $mapa['nombre_mapa']; ?></span>
                    <?php
    endforeach; ?>
                <?php
else: ?>
                    <p class="sin-datos">No se han registrado avistamientos en los mapas actuales.</p>
                <?php
endif; ?>
            </div>
        </section>

        <section class="seccion-comentarios mt-40">
            <h3>Comentarios y Aportes</h3>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form action="actions/procesar_comentario.php" method="POST" class="form-ark mb-25">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                    <div class="d-flex gap-15 mb-10">
                        <?php 
                        $foto_mismo = $_SESSION['foto_perfil'] ?? 'default.png';
                        $src_mismo = (strpos($foto_mismo, 'http') === 0) ? $foto_mismo : "assets/img/perfil/" . $foto_mismo;
                        ?>
                        <img src="<?php echo htmlspecialchars($src_mismo); ?>" 
                             alt="Mi Perfil" 
                             class="avatar-comentario avatar-40 flex-shrink-0">
                        <textarea name="texto" required placeholder="Añade tu comentario o estrategia (máx 10.000 palabras)..." rows="4" class="w-100 border-radius"></textarea>
                    </div>
                    <button type="submit" class="boton-insertar">Comentar</button>
                </form>
            <?php else: ?>
                <p class="bg-soft p-10 border-radius text-center">Debes <a href="login.php" class="accent-link">iniciar sesión</a> para dejar un comentario.</p>
            <?php endif; ?>

            <div class="comentarios-lista">
                <?php if (count($comentarios) > 0): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="comentario <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'comentario-admin' : ''; ?>">
                            <div class="comentario-header">
                                <div class="d-flex align-center gap-10">
                                    <?php 
                                    $foto_c = $c['foto_perfil'] ?? 'default.png';
                                    $src_c = (strpos($foto_c, 'http') === 0) ? $foto_c : "assets/img/perfil/" . $foto_c;
                                    
                                    // Solo mostrar enlace si eres moderador y no eres el propio usuario
                                    $can_moderate = isset($_SESSION['p_moderar']) && $_SESSION['p_moderar'] == 1 && $_SESSION['usuario_id'] != $c['usuario_id'] && $c['rol'] !== 'superadmin';
                                    ?>
                                    
                                    <?php if ($can_moderate): ?>
                                        <a href="admin/moderar_usuario.php?id=<?php echo $c['usuario_id']; ?>" title="Moderar a <?php echo htmlspecialchars($c['nick']); ?>" class="d-flex align-center gap-10 no-decoration inherit-color">
                                    <?php endif; ?>

                                    <img src="<?php echo htmlspecialchars($src_c); ?>" 
                                         alt="Avatar" 
                                         class="avatar-comentario">
                                    <strong class="comentario-nick <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'nick-admin' : ''; ?>">
                                        <?php echo htmlspecialchars($c['nick']); ?> <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '🛡️' : ''; ?>
                                    </strong>

                                    <?php if ($can_moderate): ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(isset($_SESSION['usuario_id']) && (($_SESSION['is_admin'] ?? false) === true || $_SESSION['usuario_id'] == $c['usuario_id'])): ?>
                                    <form action="actions/borrar_comentario.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="comentario_id" value="<?php echo $c['id']; ?>">
                                        <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                        <button type="submit" data-confirm="¿Borrar este comentario?" class="btn-borrar-comentario">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($total_paginas > 1): ?>
                        <div class="paginacion-comentarios d-flex justify-center gap-10 mt-20">
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
                    <p class="sin-datos text-center text-muted">No hay comentarios todavía. ¡Sé el primero en aportar info!</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
            <div class="mt-40 text-center border-top-thin p-20">
                <form action="actions/admin/procesar_eliminar.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres extinguir a <?php echo htmlspecialchars($dino['nombre']); ?>? Esta acción borrará sus datos de la base de datos y NO se puede deshacer.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $dino['id']; ?>">
                    <button type="submit" class="boton-eliminar no-border cursor-pointer">
                        Eliminar Criatura
                    </button>
                </form>
            </div>
        <?php
endif; ?>
    <?php include 'includes/footer.php'; ?>