<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include '../config/db.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'CSRF inválido']);
    exit();
}

$dino_id    = isset($_POST['dino_id']) ? (int)$_POST['dino_id'] : 0;
$usuario_id = (int)$_SESSION['usuario_id'];

if ($dino_id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit();
}

try {
    // Verificar si ya es favorito
    $stmt = $conexion->prepare("SELECT id FROM favoritos WHERE usuario_id = :u AND dino_id = :d");
    $stmt->execute([':u' => $usuario_id, ':d' => $dino_id]);
    $existe = $stmt->fetch();

    if ($existe) {
        $conexion->prepare("DELETE FROM favoritos WHERE usuario_id = :u AND dino_id = :d")
                 ->execute([':u' => $usuario_id, ':d' => $dino_id]);
        echo json_encode(['status' => 'removed']);
    } else {
        $conexion->prepare("INSERT INTO favoritos (usuario_id, dino_id) VALUES (:u, :d)")
                 ->execute([':u' => $usuario_id, ':d' => $dino_id]);
        echo json_encode(['status' => 'added']);
    }
} catch (PDOException $e) {
    error_log("Error en favoritos: " . $e->getMessage());
    // Si la tabla no existe, dar instrucciones claras
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "no existe") !== false) {
        echo json_encode(['error' => 'Ejecuta database/migracion_favoritos.sql en phpMyAdmin primero']);
    } else {
        echo json_encode(['error' => 'Error interno']);
    }
}
