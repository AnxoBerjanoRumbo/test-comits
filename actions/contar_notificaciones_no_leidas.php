<?php
// actions/contar_notificaciones_no_leidas.php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $u_id = $_SESSION['usuario_id'];
    $sql = "SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :u AND leida = 0";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':u' => $u_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['count' => (int)$count]);
} catch (PDOException $e) {
    echo json_encode(['count' => 0, 'error' => 'db_error']);
}
?>
