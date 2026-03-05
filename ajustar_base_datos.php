<?php
// Script para ajustar la base de datos al nuevo sistema de seguridad y roles
include 'config/db.php';

try {
    echo "<h2>Ajustando datos para el nuevo sistema de seguridad...</h2>";

    // 1. Manejar Superadmin y Usuarios normales (Hashear contraseñas)
    $stmt = $conexion->query("SELECT id, nick, password, rol FROM usuarios WHERE rol != 'admin'");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $u) {
        $pwd = $u['password'];
        // Si no es un hash (no empieza por $), lo hasheamos
        if (strlen($pwd) > 0 && substr($pwd, 0, 1) !== '$') {
            $nuevo_hash = password_hash($pwd, PASSWORD_DEFAULT);
            $upd = $conexion->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
            $upd->execute([':hash' => $nuevo_hash, ':id' => $u['id']]);
            echo "Ajustado: <b>" . htmlspecialchars($u['nick']) . "</b> (" . $u['rol'] . ") ahora tiene contraseña segura.<br>";
        }
    }

    // 2. Manejar Admins (Poner contraseña en blanco para que aparezcan en Panel Superadmin)
    // El sistema espera que los admins pendientes tengan password = ''
    $upd_admins = $conexion->prepare("UPDATE usuarios SET password = '' WHERE rol = 'admin'");
    $upd_admins->execute();
    $filas_admins = $upd_admins->rowCount();
    
    if ($filas_admins > 0) {
        echo "Ajustado: <b>$filas_admins</b> administradores han sido marcados como 'pendientes' (contraseña borrada).<br>";
        echo "<i>Ahora puedes entrar como <b>Anxo</b> e ir al Panel Superadmin para activarlos.</i><br>";
    }

    echo "<h3>¡Base de datos sincronizada con el nuevo sistema!</h3>";
    echo "<p>Ya puedes borrar este archivo (`ajustar_base_datos.php`) por seguridad.</p>";

} catch (PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}
?>
