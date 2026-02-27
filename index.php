<?php
include 'config/db.php';

$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$dieta = isset($_GET['dieta']) ? $_GET['dieta'] : '';

$sql = "SELECT * FROM dinosaurios WHERE 1=1";

// Si hay búsqueda por texto, añadimos la condición
if ($busqueda != '') {
    $sql .= " AND (nombre LIKE :busqueda OR especie LIKE :busqueda)";
}

// Si hay filtro por dieta, añadimos la condición
if ($dieta != '') {
    $sql .= " AND dieta = :dieta";
}

$stmt = $conexion->prepare($sql);

// Pasamos los parámetros solo si existen
if ($busqueda != '') {
    $termino = "%$busqueda%";
    $stmt->bindParam(':busqueda', $termino);
}
if ($dieta != '') {
    $stmt->bindParam(':dieta', $dieta);
}

$stmt->execute();
$dinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARK Survival Hub - Wiki</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <h1>ARK Survival Hub</h1>
    </header>
    <section class="buscador">
        <form action="index.php" method="GET">
            <input type="text" name="buscar" placeholder="Busca tu criatura..." 
                value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">

            <select name="dieta">
                <option value="">Todas las dietas</option>
                <option value="Carnívoro" <?php echo (isset($_GET['dieta']) && $_GET['dieta'] == 'Carnívoro') ? 'selected' : ''; ?>>Carnívoro</option>
                <option value="Herbívoro" <?php echo (isset($_GET['dieta']) && $_GET['dieta'] == 'Herbívoro') ? 'selected' : ''; ?>>Herbívoro</option>
                <option value="Omnívoro" <?php echo (isset($_GET['dieta']) && $_GET['dieta'] == 'Omnívoro') ? 'selected' : ''; ?>>Omnívoro</option>
            </select>

            <button type="submit">Filtrar</button>
            
            <?php if(isset($_GET['buscar']) || isset($_GET['dieta'])): ?>
                <a href="index.php" class="boton-limpiar">Limpiar</a>
            <?php endif; ?>
        </form>
    </section>
    <main>
        <h2>Diccionario de Criaturas</h2>
        <div class="contenedor-dinos">
            <?php if (count($dinos) > 0): ?>
                <?php foreach ($dinos as $dino): ?>
                    <div class="dino-card">
                        <h3>
                            <a href="detalle.php?id=<?php echo $dino['id']; ?>" class="enlace-dino">
                                <?php echo $dino['nombre']; ?>
                            </a>
                        </h3>
                        <p><strong>Especie:</strong> <?php echo $dino['especie']; ?></p>
                        <p><strong>Dieta:</strong> <?php echo $dino['dieta']; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: #888;">
                No se han encontrado criaturas que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
                </p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>