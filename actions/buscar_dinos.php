<?php
// Endpoint JSON para el buscador del comparador
session_start();
include '../config/db.php';

// Solo usuarios autenticados pueden buscar
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$termino = '%' . $q . '%';
$stmt = $conexion->prepare(
    "SELECT id, nombre, especie, dieta,
            stat_health, stat_stamina, stat_oxygen, stat_food,
            stat_weight, stat_melee, stat_speed, stat_torpidity
     FROM dinosaurios
     WHERE nombre LIKE :q OR especie LIKE :q
     ORDER BY nombre ASC
     LIMIT 10"
);
$stmt->execute([':q' => $termino]);
$dinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($dinos);
