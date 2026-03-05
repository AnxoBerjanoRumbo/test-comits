<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || ($_SESSION['p_insertar'] ?? 0) == 0) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';


$sql_mapas = "SELECT * FROM mapas ORDER BY nombre_mapa ASC";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Añadir Criatura</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=1.2">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="admin-body">
    <header>
        <h1>Panel de Administración</h1>
        <a href="../index.php" class="boton-volver">Volver a la Wiki</a>
    </header>

    <main class="contenedor-formulario">
        <h2>Registrar nueva criatura</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>
            <div class="alerta-error">
                ⚠️ El dinosaurio <strong><?php echo htmlspecialchars($_GET['nombre']); ?></strong> ya existe en el sistema.
            </div>
        <?php
endif; ?>

        <form action="procesar_insertar.php" method="POST" enctype="multipart/form-data" class="form-ark">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="campo">
                <label>Nombre de la criatura:</label>
                <input type="text" name="nombre" required placeholder="Ej: Thylacoleo">
            </div>

            <div class="campo">
                <label>Especie:</label>
                <input type="text" name="especie" required placeholder="Ej: Thylacoleo furtimorsus">
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
                <textarea name="descripcion" required placeholder="Breve descripción de la criatura..." rows="4"></textarea>
            </div>

            <div class="campo">
                <label>Imagen de la criatura:</label>
                <input type="file" name="imagen" accept="image/*" required>
            </div>

            <div class="campo">
                <label>Mapa de avistamiento inicial:</label>
                <select name="mapa_id" required>
                    <option value="">Selecciona un mapa...</option>
                    <?php foreach ($mapas as $mapa): ?>
                        <option value="<?php echo $mapa['id']; ?>">
                            <?php echo $mapa['nombre_mapa']; ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>

            <button type="submit" class="boton-insertar">Añadir a la base de datos</button>
        </form>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>