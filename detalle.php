<?php
session_start();
include 'config/db.php';

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

// 4. Consulta de comentarios
$sql_comments = "SELECT c.*, u.nick, u.rol FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.dino_id = :id ORDER BY c.id DESC";
$stmt_comments = $conexion->prepare($sql_comments);
$stmt_comments->bindParam(':id', $id);
$stmt_comments->execute();
$comentarios = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dino['nombre']; ?> - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.2">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header class="header-principal" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; flex-wrap: wrap; gap: 15px;">
        <div class="logo-titulo">
            <h1>Ficha de Criatura</h1>
        </div>
        <nav class="navegacion-usuario" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="index.php" class="btn-nav">Volver al listado</a>
            <?php if (isset($_SESSION['nick'])): ?>
                <a href="perfil.php" class="enlace-perfil" style="color: white; text-decoration: none;">
                    <span class="bienvenida">Hola, <strong><?php echo htmlspecialchars($_SESSION['nick']); ?></strong></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-nav">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="contenedor-detalle">
        <section class="ficha-principal">
            <h2 class="nombre-dino"><?php echo htmlspecialchars($dino['nombre']); ?></h2>
            
            <?php if(!empty($dino['imagen'])): ?>
                <div class="dino-img-detalle" style="text-align: center; margin-bottom: 20px;">
                    <img src="assets/img/dinos/<?php echo htmlspecialchars($dino['imagen']); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" style="max-width: 100%; border-radius: 8px;">
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
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="admin/editar.php?id=<?php echo $dino['id']; ?>" class="btn-nav btn-registro" style="background-color: #007bff; border-color: #007bff; color: white;">Editar Criatura</a>
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

        <section class="seccion-comentarios" style="margin-top: 40px;">
            <h3>Comentarios y Aportes</h3>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form action="procesar_comentario.php" method="POST" class="form-ark" style="margin-bottom: 20px;">
                    <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                    <textarea name="texto" required placeholder="Añade tu comentario o estrategia (máx 10.000 palabras)..." rows="4" style="width: 100%; margin-bottom: 10px;"></textarea>
                    <button type="submit" class="boton-insertar">Comentar</button>
                </form>
            <?php else: ?>
                <p style="background-color: #333; padding: 10px; border-radius: 5px; text-align: center;">Debes <a href="login.php" style="color: #4CAF50;">iniciar sesión</a> para dejar un comentario.</p>
            <?php endif; ?>

            <div class="comentarios-lista">
                <?php if (count($comentarios) > 0): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="comentario" style="background-color: #222; margin-bottom: 15px; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '#ffcc00' : '#4CAF50'; ?>;">
                            <div class="comentario-header" style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <strong style="color: <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '#ffcc00' : '#fff'; ?>;">
                                    <?php echo htmlspecialchars($c['nick']); ?> <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '🛡️' : ''; ?>
                                </strong>
                                
                                <?php if(isset($_SESSION['usuario_id']) && ($_SESSION['is_admin'] === true || $_SESSION['usuario_id'] == $c['usuario_id'])): ?>
                                    <form action="borrar_comentario.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="comentario_id" value="<?php echo $c['id']; ?>">
                                        <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                        <button type="submit" onclick="return confirm('¿Borrar este comentario?');" style="background: none; border: none; color: #ff5555; cursor: pointer; font-size: 0.9em; padding: 0;">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p style="white-space: pre-wrap; font-size: 0.95em; color: #ddd; margin: 0;"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sin-datos" style="text-align: center; color: #888;">No hay comentarios todavía. ¡Sé el primero en aportar info!</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <div style="margin-top: 40px; text-align: center; border-top: 1px solid #444; padding-top: 20px;">
                <a href="admin/procesar_eliminar.php?id=<?php echo $dino['id']; ?>" 
                    class="boton-eliminar" 
                    onclick="return confirm('¿Estás seguro de que quieres extinguir a <?php echo htmlspecialchars($dino['nombre']); ?>? Esta acción borrará sus datos de la base de datos y NO se puede deshacer.');">
                    Eliminar Criatura
                </a>
            </div>
        <?php
endif; ?>
    </main>
    <script src="assets/js/main.js"></script>
</body>
</html>