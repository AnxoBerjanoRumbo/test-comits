<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $conexion->query("SELECT archivo AS file, title FROM musica ORDER BY id ASC");
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $tracks]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
