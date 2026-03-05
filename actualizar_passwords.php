<?php
// Script para actualizar las contraseñas en texto plano a hashes seguros
include 'config/db.php';

try {
    echo "<h2>Actualizando contraseñas de la base de datos...</h2>";

    $stmt = $conexion->query("SELECT id, nick, password, rol FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actualizados = 0;
    $ya_seguros = 0;

    foreach ($usuarios as $u) {
        $pwd = $u['password'];
        
        // Verifica si la contraseña ya parece un hash de password_hash() (empiezan con $2y$ o similar)
        if (strlen($pwd) > 0 && substr($pwd, 0, 1) !== '$') {
            // Es una contraseña en texto plano, la hasheamos
            $nuevo_hash = password_hash($pwd, PASSWORD_DEFAULT);
            
            $upd = $conexion->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
            $upd->execute([':hash' => $nuevo_hash, ':id' => $u['id']]);
            $actualizados++;
            
            echo "Ejecutado: Usuario <b>" . htmlspecialchars($u['nick']) . "</b> (Rol: " . htmlspecialchars($u['rol']) . ") hash actualizado.<br>";
        } else {
            // Ya estaba hasheada o está vacía (como los admins pendientes si no se les asignó clave)
            $ya_seguros++;
        }
    }

    echo "<h3>Proceso terminado.</h3>";
    echo "<p>Contraseñas actualizadas a hash seguro: <b>$actualizados</b></p>";
    echo "<p>Contraseñas que ya estaban seguras (o vacías): <b>$ya_seguros</b></p>";
    echo "<p style='color:red;'><b>IMPORTANTE:</b> Una vez que hayas verificado que puedes iniciar sesión, por motivos de seguridad te recomiendo que elimines este archivo (`actualizar_passwords.php`).</p>";

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
