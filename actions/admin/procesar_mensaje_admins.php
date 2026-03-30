<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: ../../index.php");
    exit();
}

include '../../config/db.php';
include '../../config/notificaciones.php';
include '../../config/admin_logger.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $asunto = trim($_POST['asunto'] ?? 'Mensaje de la Dirección');
    $mensaje_completo = trim($_POST['mensaje']);

    if (empty($mensaje_completo)) {
        header("Location: ../../panel_superadmin.php?error=mensaje_vacio");
        exit();
    }

    $destinatario = $_POST['destinatario'] ?? 'todos';

    try {
        if ($destinatario === 'todos') {
            $sqlUsuarios = "SELECT id FROM usuarios WHERE rol = 'admin'";
            $usuarios = $conexion->query($sqlUsuarios)->fetchAll(PDO::FETCH_COLUMN);
            $log_detalle = "Enviado mensaje a todos los admins: " . htmlspecialchars($asunto);
        } else {
            // Validar que el destinatario sea realmente un admin
            $stmtVerif = $conexion->prepare("SELECT id, nick, email FROM usuarios WHERE id = :id AND rol = 'admin'");
            $stmtVerif->execute([':id' => $destinatario]);
            $admin_destino = $stmtVerif->fetch(PDO::FETCH_ASSOC);
            
            if ($admin_destino) {
                $usuarios = [$destinatario];
                $nombre_destino = $admin_destino['nick'] ?: $admin_destino['email'];
                $log_detalle = "Enviado mensaje privado directo al admin " . htmlspecialchars($nombre_destino) . ": " . htmlspecialchars($asunto);
            } else {
                header("Location: ../../panel_superadmin.php?error=interno");
                exit();
            }
        }
        
        $sqlNotif = "INSERT INTO notificaciones (id_usuario, mensaje, enlace) VALUES (:u, :m, '#')";
        $stmtNotif = $conexion->prepare($sqlNotif);
        $sqlUpdateEnlace = "UPDATE notificaciones SET enlace = :e WHERE id = :id";
        $stmtUpdate = $conexion->prepare($sqlUpdateEnlace);

        $remitente = $_SESSION['nick'] ?? 'Superadmin';
        
        $texto_notificacion = "Tienes un nuevo mensaje de " . $remitente;
        if (!empty($asunto) && $asunto !== 'Mensaje de la Dirección') {
            $texto_notificacion .= ": " . $asunto;
        }

        foreach ($usuarios as $u_id) {
            $stmtNotif->execute([
                ':u' => $u_id, 
                ':m' => "[Mensaje] " . $texto_notificacion . "\n\n" . $mensaje_completo
            ]);
            $notif_id = $conexion->lastInsertId();
            $stmtUpdate->execute([
                ':e' => "leer_notificacion.php?id=" . $notif_id,
                ':id' => $notif_id
            ]);
        }

        registrarAccionAdmin($conexion, $_SESSION['usuario_id'], 'Mensaje Global', $log_detalle);

        header("Location: ../../panel_superadmin.php?status=mensaje_enviado");
        exit();
    } catch (PDOException $e) {
        error_log("Error al enviar mensaje a admins: " . $e->getMessage());
        header("Location: ../../panel_superadmin.php?error=interno");
        exit();
    }
} else {
    header("Location: ../../panel_superadmin.php");
    exit();
}
?>
