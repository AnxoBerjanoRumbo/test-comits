<?php
// actions/obtener_notificaciones.php
session_start();
include '../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No logueado']);
    exit();
}

$u_id = $_SESSION['usuario_id'];
$sql = "SELECT id, mensaje, enlace, leida, fecha FROM notificaciones 
        WHERE id_usuario = :u 
        ORDER BY fecha DESC LIMIT 10";
$stmt = $conexion->prepare($sql);
$stmt->execute([':u' => $u_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notificaciones);
?>
