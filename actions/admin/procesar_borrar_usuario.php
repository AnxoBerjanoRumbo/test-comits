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
    if ($id_usuario === (int)$_SESSION['usuario_id']) {
        header("Location: ../../panel_superadmin.php?error=autoborrado&tab_usuarios=1#buscador-usuarios");
        exit();
    }

    try {

        $stmt_c = $conexion->prepare("DELETE FROM comentarios WHERE usuario_id = :id");
        $stmt_c->execute([':id' => $id_usuario]);

        // Borrado del usuario
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id_usuario]);

        header("Location: ../../panel_superadmin.php?status=usuario_borrado&tab_usuarios=1#buscador-usuarios");
        exit();

    }
    catch (PDOException $e) {
        error_log("Error al borrar usuario: " . $e->getMessage());
        header("Location: ../../panel_superadmin.php?error=db_error&tab_usuarios=1#buscador-usuarios");
        exit();
    }
}
else {
    header("Location: ../../panel_superadmin.php?tab_usuarios=1#buscador-usuarios");
    exit();
}
