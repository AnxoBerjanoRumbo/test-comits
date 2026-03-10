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
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background: var(--bg-header); border-radius: var(--radius); border: 1px solid var(--border-color); margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 1.8rem;">Panel de Administración</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php 
                $foto_admin_i = $_SESSION['foto_perfil'] ?? 'default.png';
                $src_admin_i = (strpos($foto_admin_i, 'http') === 0) ? $foto_admin_i : "../assets/img/perfil/" . $foto_admin_i;
                ?>
                <img src="<?php echo htmlspecialchars($src_admin_i); ?>" 
                     alt="Perfil" 
                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);"
                     onerror="this.src='../assets/img/perfil/default.png'">
                <span class="bienvenida"><strong><?php echo htmlspecialchars($_SESSION['nick']); ?></strong></span>
            </div>
            <a href="../index.php" class="boton-volver" style="margin: 0;">Volver a la Wiki</a>
        </div>
    </header>

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