<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$id_bloqueo = isset($_POST['id_bloqueo']) ? (int)$_POST['id_bloqueo'] : 0;

if ($id_bloqueo > 0) {
    try {
        $stmt = $conexion->prepare("DELETE FROM emails_bloqueados WHERE id = :id");
        $stmt->execute([':id' => $id_bloqueo]);
        header("Location: ../../panel_superadmin.php?tab=blacklist&status=desbloqueado");
    } catch (PDOException $e) {
        error_log("Error al desbloquear email: " . $e->getMessage());
        header("Location: ../../panel_superadmin.php?tab=blacklist&error=db_error");
    }
} else {
    header("Location: ../../panel_superadmin.php?tab=blacklist");
}
exit();
?>
