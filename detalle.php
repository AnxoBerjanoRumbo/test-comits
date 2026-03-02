<?php
session_start();
$_SESSION['is_admin'] = true;

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
    <header>
        <h1>Ficha de Criatura</h1>
        <a href="index.php" class="boton-volver">Volver al listado</a>
    </header>

    <main class="contenedor-detalle">
        <section class="ficha-principal">
            <h2 class="nombre-dino"><?php echo $dino['nombre']; ?></h2>
            <div class="info-grid">
                <div class="dato">
                    <strong>Especie:</strong> 
                    <span><?php echo $dino['especie']; ?></span>
                </div>
                <div class="dato">
                    <strong>Dieta:</strong> 
                    <span><?php echo $dino['dieta']; ?></span>
                </div>
            </div>
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
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <div style="margin-top: 40px; text-align: center; border-top: 1px solid #444; padding-top: 20px;">
                <a href="admin/procesar_eliminar.php?id=<?php echo $dino['id']; ?>" 
                    class="boton-eliminar" 
                    onclick="return confirm('⚠️¿Estás seguro de que quieres extinguir a <?php echo htmlspecialchars($dino['nombre']); ?>? Esta acción borrará sus datos de la base de datos y NO se puede deshacer.');">
                    Eliminar Criatura
                </a>
            </div>
        <?php
endif; ?>
    </main>
</body>
</html>