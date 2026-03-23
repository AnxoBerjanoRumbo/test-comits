<?php
session_start();
include '../../config/db.php';

// Seguridad: Solo superadmins
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: ../../index.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;

if ($id_usuario > 0) {
    // No permitir que el superadmin se borre a sí mismo por accidente
    if ($id_usuario === (int)$_SESSION['usuario_id']) {
        header("Location: ../../panel_superadmin.php?error=autoborrado&tab_usuarios=1");
        exit();
    }

    try {
        // En un sistema real, podrías querer borrar también sus fotos locales/Cloudinary, 
        // pero por ahora limpiamos el registro de la DB.
        
        // 1. Obtener datos de la foto por si queremos borrarla (opcional, por ahora solo DB)
        // 2. Ejecutar borrado (las claves foráneas en cascada deberían limpiar comentarios si están configuradas, si no, hay que hacerlo manual)
        
        // Borrado manual de comentarios por si no hay cascada en la DB
        $stmt_c = $conexion->prepare("DELETE FROM comentarios WHERE usuario_id = :id");
        $stmt_c->execute([':id' => $id_usuario]);

        // Borrado del usuario
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id_usuario]);

        header("Location: ../../panel_superadmin.php?status=usuario_borrado&tab_usuarios=1");
        exit();

    } catch (PDOException $e) {
        error_log("Error al borrar usuario: " . $e->getMessage());
        header("Location: ../../panel_superadmin.php?error=db_error&tab_usuarios=1");
        exit();
    }
} else {
    header("Location: ../../panel_superadmin.php?tab_usuarios=1");
    exit();
}
