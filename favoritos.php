<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

$usuario_id = $_SESSION['usuario_id'];

$favoritos = [];
try {
    $stmt_favs = $conexion->prepare(
        "SELECT d.id, d.nombre, d.imagen, d.dieta, d.especie, f.fecha
         FROM favoritos f
         JOIN dinosaurios d ON f.dino_id = d.id
         WHERE f.usuario_id = :u
         ORDER BY f.fecha DESC"
    );
    $stmt_favs->execute([':u' => $usuario_id]);
    $favoritos = $stmt_favs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabla favoritos no existe aún
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - ARK Survival Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.6">
</head>
<body>
    <?php
    $header_volver_link  = "index.php";
    $header_volver_texto = "Volver a la Wiki";
    include 'includes/header.php';
    ?>

    <main>
        <h2 class="titulo-seccion">
            Mis Criaturas Favoritas
            <?php if (!empty($favoritos)): ?>
                <span class="fav-count-badge"><?php echo count($favoritos); ?></span>
            <?php endif; ?>
        </h2>

        <?php if (empty($favoritos)): ?>
            <div class="fav-empty">
                <span class="material-symbols-outlined fav-empty-icon">favorite_border</span>
                <p>Aún no tienes criaturas favoritas.</p>
                <p class="fav-empty-sub">Entra en la ficha de cualquier criatura y pulsa el botón de corazón para guardarla aquí.</p>
                <a href="index.php" class="btn-nav btn-registro" style="margin-top:20px; display:inline-block; text-decoration:none;">
                    Explorar criaturas
                </a>
            </div>
        <?php else: ?>
            <div class="fav-grid">
                <?php foreach ($favoritos as $fav):
                    $src = (strpos($fav['imagen'] ?? '', 'http') === 0)
                        ? $fav['imagen']
                        : "assets/img/dinos/" . ($fav['imagen'] ?? 'default_dino.jpg');
                    if (strpos($src, 'res.cloudinary.com') !== false) {
                        $src = str_replace('/upload/', '/upload/w_300,c_fill,g_auto,f_auto,q_auto/', $src);
                    }
                ?>
                <div class="fav-card">
                    <div class="fav-card-img">
                        <img src="<?php echo htmlspecialchars($src); ?>"
                             alt="<?php echo htmlspecialchars($fav['nombre']); ?>"
                             onerror="this.src='assets/img/dinos/default_dino.jpg'">
                    </div>
                    <div class="fav-card-body">
                        <a href="detalle.php?id=<?php echo (int)$fav['id']; ?>" class="fav-card-nombre card-stretched-link">
                            <?php echo htmlspecialchars($fav['nombre']); ?>
                        </a>
                        <span class="fav-card-dieta"><?php echo htmlspecialchars($fav['dieta'] ?? ''); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
