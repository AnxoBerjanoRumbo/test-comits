<?php
include_once __DIR__ . '/db.php';

function registrarAccionAdmin($conexion, $id_usuario, $accion, $detalle = null) {
    $sql = "INSERT INTO admin_logs (id_usuario, accion, detalle) VALUES (:u, :a, :d)";
    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':u' => $id_usuario, ':a' => $accion, ':d' => $detalle]);
    } catch (PDOException $e) {
        error_log("Error al registrar accion de admin: " . $e->getMessage());
    }
}
?>
