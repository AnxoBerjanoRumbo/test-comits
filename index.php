<?php
include 'config/db.php';

// Preparamos la consulta para traer los dinos
$sql = "SELECT nombre, especie, dieta FROM dinosaurios";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$dinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARK Survival Hub - Wiki</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <header>
        <h1>ARK Survival Hub</h1>
    </header>

    <main>
        <h2>Diccionario de Criaturas</h2>
        <div class="contenedor-dinos">
            <?php foreach ($dinos as $dino): ?>
                <div class="dino-card">
                    <h3><?php echo $dino['nombre']; ?></h3>
                    <p><strong>Especie:</strong> <?php echo $dino['especie']; ?></p>
                    <p><strong>Dieta:</strong> <?php echo $dino['dieta']; ?></p>
                </div>
            <?php <?php endforeach; ?>
        </div>
    </main>
</body>
</html>