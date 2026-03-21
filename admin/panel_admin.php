<?php
session_start();

// Seguridad: Solo administradores o superadmins pueden entrar aquí
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

$busqueda = isset($_GET['buscar_usuario']) ? trim($_GET['buscar_usuario']) : '';
$usuario_encontrado = null;
$comentarios_usuario = [];
$error = null;

if (!empty($busqueda)) {
    // Buscamos al usuario por Nick o Email
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nick LIKE :busq OR email = :busq_directa LIMIT 5");
    $stmt->execute([':busq' => "%$busqueda%", ':busq_directa' => $busqueda]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultados) === 1) {
        $usuario_encontrado = $resultados[0];
    } elseif (count($resultados) > 1) {
        $error = "Se han encontrado varios usuarios con ese nombre. Por favor, sé más específico.";
    } else {
        $error = "No se ha encontrado ningún usuario con el nick o email: " . htmlspecialchars($busqueda);
    }

    if ($usuario_encontrado) {
        // Si encontramos uno solo, cargamos sus comentarios asociados
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

// Variables para el header
$header_titulo = "Centro de Gestión Admin";
$header_volver_link = "../index.php";
$header_volver_texto = "Volver a la Wiki";
$is_admin_panel = true; // Para que el header use ../ en las rutas
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gestión de Usuarios</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.5">
</head>
<body class="admin-body">
    <?php include '../includes/header.php'; ?>

    <main class="contenedor-detalle max-w-1000">
        <section class="mb-50">
            <h2 class="accent-text text-center">Buscador de Supervivientes</h2>
            <p class="text-center text-muted mb-25">Introduce el nick o email para ver su expediente y comentarios.</p>
            
            <div class="buscador">
                <form action="panel_admin.php" method="GET">
                    <input type="text" name="buscar_usuario" placeholder="Ej: Superviviente99..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>" required>
                    <button type="submit">Buscar Usuario</button>
                    <?php if (!empty($busqueda)): ?>
                        <a href="panel_admin.php" class="boton-limpiar">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($error): ?>
                <div class="alerta-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (count($resultados ?? []) > 1 && !$usuario_encontrado): ?>
                <div class="comentarios-lista">
                    <h3>Resultados de la búsqueda:</h3>
                    <?php foreach ($resultados as $res): ?>
                        <div class="comentario" style="border-left-color: var(--accent);">
                            <div class="comentario-header">
                                <span class="comentario-nick"><?php echo htmlspecialchars($res['nick']); ?> (<?php echo htmlspecialchars($res['email']); ?>)</span>
                                <a href="panel_admin.php?buscar_usuario=<?php echo urlencode($res['nick']); ?>" class="btn-nav" style="padding: 5px 10px; font-size: 0.8rem;">Ver Detalles</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($usuario_encontrado): ?>
            <section class="ficha-principal animate-fade-in">
                <div class="flex-between-center mb-20" style="flex-wrap: wrap; gap: 20px;">
                    <div class="d-flex align-center gap-15">
                        <?php 
                        $f = $usuario_encontrado['foto_perfil'] ?? 'default.png';
                        $src = (strpos($f, 'http') === 0) ? $f : "../assets/img/perfil/" . $f;
                        ?>
                        <img src="<?php echo htmlspecialchars($src); ?>" class="avatar-header" style="width: 80px; height: 80px; border-width: 3px;">
                        <div>
                            <h2 style="margin: 0;"><?php echo htmlspecialchars($usuario_encontrado['nick']); ?></h2>
                            <p class="accent-text" style="text-transform: uppercase; font-size: 0.8rem; font-weight: 800;">Rango: <?php echo htmlspecialchars($usuario_encontrado['rol']); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <?php if (($_SESSION['p_moderar'] ?? 0) == 1): ?>
                            <a href="moderar_usuario.php?id=<?php echo $usuario_encontrado['id']; ?>" class="boton-eliminar" style="background-color: #ff9800;">Moderar / Vetar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="dato">
                        <span class="text-muted">ID de Usuario:</span>
                        <strong>#<?php echo $usuario_encontrado['id']; ?></strong>
                    </div>
                    <div class="dato">
                        <span class="text-muted">Email:</span>
                        <strong><?php echo htmlspecialchars($usuario_encontrado['email']); ?></strong>
                    </div>
                    <div class="dato">
                        <span class="text-muted">Estado de Baneo:</span>
                        <strong>
                            <?php 
                            if ($usuario_encontrado['ban_permanente']) echo "<span class='error-color'>PERMANENTE</span>";
                            elseif (!empty($usuario_encontrado['baneado_hasta']) && strtotime($usuario_encontrado['baneado_hasta']) > time()) echo "<span class='error-color'>TEMPORAL</span>";
                            else echo "<span class='success-color'>ACTIVO</span>";
                            ?>
                        </strong>
                    </div>
                </div>
            </section>

            <section class="seccion-comentarios mt-40">
                <h3>Comentarios Realizados (<?php echo count($comentarios_usuario); ?>)</h3>
                <div class="comentarios-lista">
                    <?php if (count($comentarios_usuario) > 0): ?>
                        <?php foreach ($comentarios_usuario as $c): ?>
                            <div class="comentario">
                                <div class="comentario-header">
                                    <span class="text-muted">En: <a href="../detalle.php?id=<?php echo $c['dino_id']; ?>" class="accent-text no-decoration"><strong><?php echo htmlspecialchars($c['dino_nombre']); ?></strong></a></span>
                                    <span class="f-08 text-muted">ID #<?php echo $c['id']; ?></span>
                                </div>
                                <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="sin-datos">Este usuario aún no ha realizado aportaciones en la wiki.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
