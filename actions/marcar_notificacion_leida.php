<?php
// actions/marcar_notificacion_leida.php
session_start();
include '../config/db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_POST['id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$u_id = $_SESSION['usuario_id'];
$n_id = (int)$_POST['id'];

$sql = "UPDATE notificaciones SET leida = 1 WHERE id = :id AND id_usuario = :u";
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $n_id, ':u' => $u_id]);

echo json_encode(['status' => 'success']);
?>
