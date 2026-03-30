<?php
include_once __DIR__ . '/db.php';

/**
 * Añade una notificación a un usuario.
 */
function añadirNotificacion($conexion, $id_usuario, $mensaje, $enlace = null) {
    if (!$id_usuario) return;
    $sql = "INSERT INTO notificaciones (id_usuario, mensaje, enlace) VALUES (:u, :m, :e)";
    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':u' => $id_usuario, ':m' => $mensaje, ':e' => $enlace]);
    } catch (PDOException $e) {
        error_log("Error al añadir notificación: " . $e->getMessage());
    }
}

/**
 * Notifica a todos los usuarios, excluyendo opcionalmente a uno.
 */
function notificarATodos($conexion, $mensaje, $enlace = null, $exclude_id = null) {
    try {
        $sqlU = "SELECT id FROM usuarios" . ($exclude_id ? " WHERE id != :ex" : "");
        $stmtU = $conexion->prepare($sqlU);
        if ($exclude_id) $stmtU->execute([':ex' => $exclude_id]);
        else $stmtU->execute();
        $usuarios = $stmtU->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($usuarios)) return;

        $placeholders = [];
        $params = [];
        foreach ($usuarios as $u_id) {
            $placeholders[] = "(?, ?, ?)";
            array_push($params, $u_id, $mensaje, $enlace);
        }

        $sql = "INSERT INTO notificaciones (id_usuario, mensaje, enlace) VALUES " . implode(',', $placeholders);
        $conexion->prepare($sql)->execute($params);
    } catch (PDOException $e) {
        error_log("Error en notificarATodos (Bulk): " . $e->getMessage());
    }
}

/**
 * Notifica a usuarios con ciertos roles. (Optimizado)
 */
function notificarPorRol($conexion, $roles, $mensaje, $enlace = null, $exclude_id = null) {
    if (empty($roles)) return;
    
    $in = str_repeat('?,', count($roles) - 1) . '?';
    $sqlU = "SELECT id FROM usuarios WHERE rol IN ($in)" . ($exclude_id ? " AND id != ?" : "");
    
    try {
        $stmtU = $conexion->prepare($sqlU);
        $paramsU = $roles;
        if ($exclude_id) $paramsU[] = $exclude_id;
        $stmtU->execute($paramsU);
        $usuarios = $stmtU->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($usuarios)) return;

        $placeholders = [];
        $params = [];
        foreach ($usuarios as $u_id) {
            $placeholders[] = "(?, ?, ?)";
            array_push($params, $u_id, $mensaje, $enlace);
        }

        $sql = "INSERT INTO notificaciones (id_usuario, mensaje, enlace) VALUES " . implode(',', $placeholders);
        $conexion->prepare($sql)->execute($params);
    } catch (PDOException $e) {
        error_log("Error en notificarPorRol (Bulk): " . $e->getMessage());
    }
}
?>
