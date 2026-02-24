<?php include 'config/db.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ARK Hub</title>
</head>
<body>
    <h1>Proyecto ARK de Anxo</h1>
    
    <?php
    if (isset($conexion)) {
        echo "<p style='color: green;'>✅ Conexión con la base de datos establecida con éxito.</p>";
    }
    ?>
</body>
</html>