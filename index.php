<?php
include 'config/db.php';

// 1. Miramos si el usuario ha escrito algo en el buscador
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// 2. Preparamos la consulta SQL con filtro o sin él
if ($busqueda != '') {
    $sql = "SELECT id, nombre, especie, dieta FROM dinosaurios 
            WHERE nombre LIKE :busqueda OR especie LIKE :busqueda";
    $stmt = $conexion->prepare($sql);
    $termino = "%$busqueda%";
    $stmt->bindParam(':busqueda', $termino);
} else {
    $sql = "SELECT id, nombre, especie, dieta FROM dinosaurios";
    $stmt = $conexion->prepare($sql);
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
</head>
<body>
    <header>
        <h1>ARK Survival Hub</h1>
    </header>
    <section class="buscador">
    <form action="index.php" method="GET">
        <input type="text" name="buscar" placeholder="Busca tu criatura..." 
               value="<?php echo isset($_GET['buscar']) ? $_GET['buscar'] : ''; ?>">
        <button type="submit">Buscar</button>
        <?php if(isset($_GET['buscar'])): ?>
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