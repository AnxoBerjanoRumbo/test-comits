<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $usuario_id = $_POST['usuario_id'];
    $accion = $_POST['accion'];

    try {
        if ($accion === 'actualizar_permisos') {
            $p_insertar = isset($_POST['permiso_insertar_dino']) ? 1 : 0;
            $p_eliminar = isset($_POST['permiso_eliminar_comentario']) ? 1 : 0;

            $sql = "UPDATE usuarios SET permiso_insertar_dino = :p_i, permiso_eliminar_comentario = :p_e WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':p_i' => $p_insertar, ':p_e' => $p_eliminar, ':id' => $usuario_id]);
            
            header("Location: panel_superadmin.php?status=permisos_actualizados");
        } 
        elseif ($accion === 'quitar_admin') {
            // Al revocar admin, liberamos el nick de admin (ej: admin42) renombrándolo
            // para que otro usuario pueda volver a solicitar ese slot de admin en el futuro.
            $sql = "UPDATE usuarios SET rol = 'usuario', nick = CONCAT('usuario_', id), permiso_insertar_dino = 0, permiso_eliminar_comentario = 0 WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id' => $usuario_id]);
            
            header("Location: panel_superadmin.php?status=admin_quitado");
        }
        exit();

    } catch (PDOException $e) {
        error_log("Error en procesamiento de permisos: " . $e->getMessage());
        header("Location: panel_superadmin.php?error=db");
        exit();
    }
} else {
    header("Location: panel_superadmin.php");
    exit();
}
