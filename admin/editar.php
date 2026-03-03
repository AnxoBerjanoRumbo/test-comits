<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($id == 0) {
    header("Location: ../index.php");
    exit();
}

$sql_dino = "SELECT * FROM dinosaurios WHERE id = :id";
$stmt = $conexion->prepare($sql_dino);
$stmt->execute([':id' => $id]);
$dino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dino) {
    header("Location: ../index.php");
    exit();
}

// Obtener mapas
$sql_mapas = "SELECT * FROM mapas ORDER BY nombre_mapa ASC";
$stmt_mapas = $conexion->query($sql_mapas);
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

// Mapas seleccionados
$sql_dino_mapas = "SELECT mapa_id FROM dino_mapas WHERE dino_id = :id";
$stmt_dm = $conexion->prepare($sql_dino_mapas);
$stmt_dm->execute([':id' => $id]);
$mapas_seleccionados = $stmt_dm->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Criatura</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.2">
</head>
<body class="admin-body">
    <header>
        <h1>Editar datos de la Criatura</h1>
        <a href="../detalle.php?id=<?php echo $dino['id']; ?>" class="boton-volver">Volver al detalle</a>
    </header>

    <main class="contenedor-formulario">
        <h2>Modificar <?php echo htmlspecialchars($dino['nombre']); ?></h2>

        <form action="procesar_editar.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="id" value="<?php echo $dino['id']; ?>">
            
            <div class="campo">
                <label>Nombre de la criatura:</label>
                <input type="text" name="nombre" required value="<?php echo htmlspecialchars($dino['nombre']); ?>">
            </div>

            <div class="campo">
                <label>Especie:</label>
                <input type="text" name="especie" required value="<?php echo htmlspecialchars($dino['especie']); ?>">
            </div>

            <div class="campo">
                <label>Dieta principal:</label>
                <select name="dieta" required>
                    <option value="Carnívoro" <?php echo ($dino['dieta'] == 'Carnívoro') ? 'selected' : ''; ?>>Carnívoro</option>
                    <option value="Herbívoro" <?php echo ($dino['dieta'] == 'Herbívoro') ? 'selected' : ''; ?>>Herbívoro</option>
                    <option value="Omnívoro" <?php echo ($dino['dieta'] == 'Omnívoro') ? 'selected' : ''; ?>>Omnívoro</option>
                    <option value="Piscívoro" <?php echo ($dino['dieta'] == 'Piscívoro') ? 'selected' : ''; ?>>Piscívoro</option>
                </select>
            </div>

            <div class="campo">
                <label>Descripción:</label>
                <textarea name="descripcion" required rows="4"><?php echo htmlspecialchars($dino['descripcion']); ?></textarea>
            </div>

            <div class="campo">
                <label>Imagen de la criatura (dejar en blanco para mantener la actual <?php echo htmlspecialchars($dino['imagen'] ?? ''); ?>):</label>
                <input type="file" name="imagen" accept="image/*">
            </div>

            <div class="campo">
                <label>Mapa principal de avistamiento:</label>
                <select name="mapa_id" required>
                    <option value="">Selecciona un mapa...</option>
                    <?php foreach ($mapas as $mapa): ?>
                        <option value="<?php echo $mapa['id']; ?>" <?php echo in_array($mapa['id'], $mapas_seleccionados) ? 'selected' : ''; ?>>
                            <?php echo $mapa['nombre_mapa']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="boton-insertar">Guardar Cambios</button>
        </form>
    </main>
</body>
</html>
