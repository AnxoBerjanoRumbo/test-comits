<?php
// Script para sincronizar fotos de perfil reales desde la DB a la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// La conexión $conexion ya debe estar disponible por el archivo que incluye este script.

if (isset($_SESSION['usuario_id'])) {
    $stmt = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $foto = $stmt->fetchColumn();
    
    if ($foto) {
        $_SESSION['foto_perfil'] = $foto;
    } else {
        $_SESSION['foto_perfil'] = 'default.png';
    }
}
?>
