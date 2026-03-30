<?php
// actions/marcar_todas_leidas.php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    $stmt = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = :u AND leida = 0");
    $stmt->execute([':u' => $_SESSION['usuario_id']]);
    echo json_encode(['status' => 'success', 'updated' => $stmt->rowCount()]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'db_error']);
}
?>
