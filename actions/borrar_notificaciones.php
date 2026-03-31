<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$u_id = $_SESSION['usuario_id'];
$n_id = isset($_POST['id']) ? $_POST['id'] : 'all';

try {
    if ($n_id === 'all') {
        $stmt = $conexion->prepare("DELETE FROM notificaciones WHERE id_usuario = :u");
        $stmt->execute([':u' => $u_id]);
    } else {
        $stmt = $conexion->prepare("DELETE FROM notificaciones WHERE id = :id AND id_usuario = :u");
        $stmt->execute([':id' => (int)$n_id, ':u' => $u_id]);
    }
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'db_error']);
}
?>
