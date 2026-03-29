<?php
// Script de instalación/actualización de categorías
// Ejecutar una sola vez desde el navegador como Superadmin y luego borrar
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    die("Acceso denegado.");
}
include 'config/db.php';
$log = [];

try {
    // 1. Crear tabla categorias con columna de orden
    $conexion->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id     INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL UNIQUE,
            orden  TINYINT UNSIGNED NOT NULL DEFAULT 99
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Añadir columna orden si ya existía la tabla sin ella
    try { $conexion->exec("ALTER TABLE categorias ADD COLUMN orden TINYINT UNSIGNED NOT NULL DEFAULT 99"); } catch(Exception $e) {}
    $log[] = "✅ Tabla 'categorias' lista.";

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
    $log[] = "✅ Tabla 'dino_categorias' lista.";

    // 3. Insertar/actualizar categorías con orden definido
    $categorias = [
        ['Acuático',   1],
        ['Terrestre',  2],
        ['Volador',    3],
        ['Espacial',   4],
        ['Alfa',       5],
        ['Titán',      6],
        ['Jefe',       7],
        ['Teck',       8],
    ];

    $stmt = $conexion->prepare("
        INSERT INTO categorias (nombre, orden) VALUES (:nombre, :orden)
        ON DUPLICATE KEY UPDATE orden = VALUES(orden)
    ");
    foreach ($categorias as [$nombre, $orden]) {
        $stmt->execute([':nombre' => $nombre, ':orden' => $orden]);
        $log[] = "➕ Categoría: <strong>$nombre</strong> (orden $orden)";
    }

    // 4. Mostrar resultado
    $cats = $conexion->query("SELECT * FROM categorias ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<style>body{font-family:monospace;padding:30px;background:#1a1a2e;color:#eee;} table{border-collapse:collapse;margin-top:20px;} td,th{border:1px solid #444;padding:8px 16px;} .ok{color:#00ffaa;}</style>";
    echo "<h2 style='color:#00ffaa'>🦖 Categorías instaladas</h2>";
    foreach ($log as $l) echo "<p>$l</p>";
    echo "<table><tr><th>ID</th><th>Orden</th><th>Nombre</th></tr>";
    foreach ($cats as $c) echo "<tr><td>{$c['id']}</td><td>{$c['orden']}</td><td>{$c['nombre']}</td></tr>";
    echo "</table>";
    echo "<p style='margin-top:30px;color:#ff9900;'>⚠️ <strong>Borra este archivo ahora que ya lo ejecutaste.</strong></p>";
    echo "</body></html>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
