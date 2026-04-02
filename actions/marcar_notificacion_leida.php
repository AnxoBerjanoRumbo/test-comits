<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$u_id = $_SESSION['usuario_id'];
$n_id = (int)($_POST['id'] ?? 0);

if ($n_id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit();
}

$sql = "UPDATE notificaciones SET leida = 1 WHERE id = :id AND id_usuario = :u";
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $n_id, ':u' => $u_id]);

echo json_encode(['status' => 'success']);
?>
