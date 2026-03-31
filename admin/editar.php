<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
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

// Categorías disponibles y seleccionadas
$stmt_cats = $conexion->query("SELECT * FROM categorias ORDER BY orden ASC");
$categorias = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

$stmt_dc = $conexion->prepare("SELECT categoria_id FROM dino_categorias WHERE dino_id = :id");
$stmt_dc->execute([':id' => $id]);
$cats_seleccionadas = $stmt_dc->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Criatura</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.3">
</head>
<body class="admin-body">
    <?php 
    $is_admin_panel = true;
    $header_titulo = "Editar Criatura";
    $header_volver_link = "../detalle.php?id=" . $dino['id'];
    $header_volver_texto = "Volver al detalle";
    include '../includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Modificar <?php echo htmlspecialchars($dino['nombre']); ?></h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-error">
                <?php 
                if ($_GET['error'] == 'formato') echo "⚠️ Formato de imagen no válido o archivo dañado. Usa JPG, PNG o WebP.";
                elseif ($_GET['error'] == 'interno') echo "⚠️ Error interno del servidor al procesar los datos.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../actions/admin/procesar_editar.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo $dino['id']; ?>">
            
            <div class="campo">
                <label>Nombre de la criatura:</label>
                <input type="text" name="nombre" required value="<?php echo htmlspecialchars($dino['nombre']); ?>" maxlength="40">
            </div>

            <div class="campo">
                <label>Especie:</label>
                <input type="text" name="especie" required value="<?php echo htmlspecialchars($dino['especie']); ?>" maxlength="60">
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
                <textarea name="descripcion" required rows="10" maxlength="10000"><?php echo htmlspecialchars($dino['descripcion']); ?></textarea>
            </div>

            <div class="campo">
                <label>Imagen de la criatura:</label>
                <?php if(!empty($dino['imagen'])): ?>
                    <div class="mb-15">
                        <p class="f-08 text-muted mb-10">Imagen actual:</p>
                        <?php 
                        $src_dino_edit = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : "../assets/img/dinos/" . $dino['imagen'];
                        ?>
                        <img src="<?php echo htmlspecialchars($src_dino_edit); ?>" class="border-8" style="max-width: 200px; height: auto; border: 2px solid var(--accent);">
                    </div>
                <?php endif; ?>
                <input type="file" name="imagen" accept="image/*">
                <small class="texto-auxiliar">Dejar vacío para mantener la imagen actual.</small>
            </div>

            <div class="campo">
                <label>Mapas de avistamiento:</label>
                <div class="grid-checkboxes">
                    <?php foreach ($mapas as $mapa): ?>
                        <label class="checkbox-tag">
                            <input type="checkbox" name="mapas[]" value="<?php echo $mapa['id']; ?>" <?php echo in_array($mapa['id'], $mapas_seleccionados) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($mapa['nombre_mapa']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo">
                <label>Categorías de la criatura:</label>
                <div class="grid-checkboxes">
                    <?php foreach ($categorias as $cat): ?>
                        <label class="checkbox-tag">
                            <input type="checkbox" name="categorias[]" value="<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $cats_seleccionadas) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($cat['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Stats Base ARK -->
            <div class="campo">
                <label style="font-size:1rem; font-weight:bold; color:var(--accent); margin-bottom:15px; display:block;">
                    <span class="material-symbols-outlined" style="vertical-align:middle; font-size:1.2rem;">radar</span>
                    Stats Base (Nivel 1 Salvaje)
                </label>
                <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:20px;">Introduce el valor base de cada stat a nivel 1 (consulta ARK Wiki).</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:18px;">
                    <?php
                    $stats = [
                        'stat_health'   => ['Vida (Health)',          'favorite',   '#e74c3c'],
                        'stat_stamina'  => ['Energía (Stamina)',       'bolt',       '#f39c12'],
                        'stat_oxygen'   => ['Oxígeno (Oxygen)',        'water_drop', '#3498db'],
                        'stat_food'     => ['Comida (Food)',           'restaurant', '#2ecc71'],
                        'stat_weight'   => ['Peso (Weight)',           'weight',     '#9b59b6'],
                        'stat_melee'    => ['Daño Cuerpo a Cuerpo',    'swords',     '#e67e22'],
                        'stat_speed'    => ['Velocidad (%)',           'speed',      '#1abc9c'],
                        'stat_torpidity'=> ['Torpor (Inconsciencia)',  'bedtime',    '#95a5a6'],
                    ];
                    foreach ($stats as $key => [$label, $icon, $color]): ?>
                    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; padding:15px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:0.88rem; font-weight:600; color:<?php echo $color; ?>; margin-bottom:10px;">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;"><?php echo $icon; ?></span>
                            <?php echo $label; ?>
                        </label>
                        <input type="number" name="<?php echo $key; ?>" min="0" max="99999"
                               value="<?php echo (int)($dino[$key] ?? 0); ?>"
                               style="font-size:1.1rem; font-weight:700; color:<?php echo $color; ?>; border-color:<?php echo $color; ?>33;">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="boton-insertar">Guardar Cambios</button>
        </form>
    <?php include '../includes/footer.php'; ?>
