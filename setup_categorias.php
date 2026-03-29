<?php
// Script de instalación de categorías - Ejecutar una sola vez y luego borrar
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    die("Acceso denegado. Solo el Superadmin puede ejecutar este script.");
}

include 'config/db.php';
$log = [];

try {
    // 1. Crear tabla categorias
    $conexion->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id   INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log[] = "✅ Tabla 'categorias' creada o ya existía.";

    // 2. Crear tabla intermedia dino_categorias
    $conexion->exec("
        CREATE TABLE IF NOT EXISTS dino_categorias (
            dino_id      INT NOT NULL,
            categoria_id INT NOT NULL,
            PRIMARY KEY (dino_id, categoria_id),
            FOREIGN KEY (dino_id)      REFERENCES dinosaurios(id) ON DELETE CASCADE,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id)  ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $log[] = "✅ Tabla 'dino_categorias' creada o ya existía.";

    // 3. Insertar categorías base (IGNORE si ya existen)
    $categorias = ['Terrestre', 'Volador', 'Acuático', 'Espacial', 'Alfa', 'Jefe', 'Titán'];
    $stmt = $conexion->prepare("INSERT IGNORE INTO categorias (nombre) VALUES (:nombre)");
    foreach ($categorias as $cat) {
        $stmt->execute([':nombre' => $cat]);
        $log[] = "➕ Categoría insertada (o ya existía): <strong>$cat</strong>";
    }

    // 4. Mostrar resultado
    $stmt_check = $conexion->query("SELECT * FROM categorias ORDER BY id ASC");
    $cats = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    echo "<style>body{font-family:monospace;padding:30px;background:#1a1a2e;color:#eee;} .ok{color:#00ffaa;} .info{color:#aaa;} table{border-collapse:collapse;margin-top:20px;} td,th{border:1px solid #444;padding:8px 16px;}</style>";
    echo "<h2 style='color:#00ffaa'>🦖 Setup de Categorías</h2>";
    foreach ($log as $l) echo "<p>$l</p>";
    echo "<h3 style='margin-top:20px;'>Categorías en la base de datos:</h3>";
    echo "<table><tr><th>ID</th><th>Nombre</th></tr>";
    foreach ($cats as $c) echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td></tr>";
    echo "</table>";
    echo "<p style='margin-top:30px;color:#ff9900;'>⚠️ <strong>Elimina este archivo del servidor ahora que ya lo has ejecutado.</strong></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
