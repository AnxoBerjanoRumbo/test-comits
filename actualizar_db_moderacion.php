<?php
/**
 * Script de actualización de Base de Datos para el Sistema de Moderación
 */
include 'config/db.php';

echo "<h1>Actualizando Base de Datos para Sistema de Moderación...</h1>";

try {
    // 1. Añadir columnas a la tabla usuarios
    $sql_usuarios = [
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS permiso_moderar_usuarios TINYINT(1) DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS baneado_hasta DATETIME DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS motivo_ban TEXT DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ban_permanente TINYINT(1) DEFAULT 0"
    ];

    foreach ($sql_usuarios as $sql) {
        $conexion->exec($sql);
        echo "Ejecutado: $sql <br>";
    }

    // 2. Crear tabla de emails bloqueados (para expulsiones totales)
    $sql_emails = "CREATE TABLE IF NOT EXISTS emails_bloqueados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        motivo TEXT,
        fecha_bloqueo DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conexion->exec($sql_emails);
    echo "Tabla 'emails_bloqueados' verificada/creada.<br>";

    echo "<h2 style='color:green;'>¡Base de datos actualizada correctamente!</h2>";
    echo "<p>Ya puedes borrar este archivo y continuar con la implementación.</p>";
    echo "<a href='index.php'>Volver al inicio</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>ERROR al actualizar:</h2> " . $e->getMessage();
}
?>
