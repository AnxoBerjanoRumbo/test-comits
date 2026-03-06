<?php
// Script para sincronizar fotos de perfil reales desde la DB a la sesión
session_start();
include 'config/db.php';

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
