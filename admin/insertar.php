<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';
include '../config/sync_foto.php';

$sql_mapas = "SELECT * FROM mapas ORDER BY nombre_mapa ASC";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

$sql_cats = "SELECT * FROM categorias ORDER BY orden ASC";
$stmt_cats = $conexion->prepare($sql_cats);
$stmt_cats->execute();
$categorias = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Añadir Criatura</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.3">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="admin-body">
    <?php 
    $is_admin_panel = true;
    $header_titulo = "Panel de Administración";
    $header_volver_link = "../index.php";
    $header_volver_texto = "Volver a la Wiki";
    include '../includes/header.php'; 
    ?>

    <main class="contenedor-formulario">
        <h2>Registrar nueva criatura</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-error">
                <?php 
                if ($_GET['error'] == 'duplicado') echo "⚠️ El dinosaurio <strong>".htmlspecialchars($_GET['nombre'])."</strong> ya existe en el sistema.";
                elseif ($_GET['error'] == 'formato') echo "⚠️ Formato de imagen no válido o archivo dañado. Usa JPG, PNG o WebP.";
                elseif ($_GET['error'] == 'interno') echo "⚠️ Error interno del servidor al procesar los datos.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../actions/admin/procesar_insertar.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Nombre de la criatura:</label>
                <input type="text" name="nombre" required placeholder="Ej: Thylacoleo" maxlength="40">
            </div>

            <div class="campo">
                <label>Especie:</label>
                <input type="text" name="especie" required placeholder="Ej: Thylacoleo furtimorsus" maxlength="60">
            </div>

            <div class="campo">
                <label>Dieta principal:</label>
                <select name="dieta" required>
                    <option value="Carnívoro">Carnívoro</option>
                    <option value="Herbívoro">Herbívoro</option>
                    <option value="Omnívoro">Omnívoro</option>
                    <option value="Piscívoro">Piscívoro</option>
                </select>
            </div>

            <div class="campo">
                <label>Descripción:</label>
                <textarea name="descripcion" required placeholder="Aquí va la información importante de la criatura (habilidades, tameo, etc.)..." rows="10" maxlength="10000"></textarea>
            </div>

            <div class="campo">
                <label>Imagen de la criatura:</label>
                <input type="file" name="imagen" accept="image/*" required>
            </div>

            <div class="campo">
                <label>Mapas de avistamiento:</label>
                <div class="grid-checkboxes">
                    <?php foreach ($mapas as $mapa): ?>
                        <label class="checkbox-tag">
                            <input type="checkbox" name="mapas[]" value="<?php echo $mapa['id']; ?>">
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
                            <input type="checkbox" name="categorias[]" value="<?php echo $cat['id']; ?>">
                            <span><?php echo htmlspecialchars($cat['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="boton-insertar">Añadir a la base de datos</button>
        </form>
    <?php include '../includes/footer.php'; ?>