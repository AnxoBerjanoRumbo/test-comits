<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error de validación CSRF.");
    }

    $id_usuario = (int)$_POST['id_usuario'];
    $nueva_password = $_POST['nueva_password'];

    if (empty($nueva_password)) {
        header("Location: ../panel_superadmin.php?error=vacio#gestion-equipo");
        exit();
    }

    try {
        // Obtener datos del usuario
        $stmt_u = $conexion->prepare("SELECT email, nick FROM usuarios WHERE id = :id");
        $stmt_u->execute([':id' => $id_usuario]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: ../panel_superadmin.php?error=no_user#gestion-equipo");
            exit();
        }

        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizamos la contraseña y nos aseguramos de que el rol sea admin y este verificado
        $sql = "UPDATE usuarios SET password = :pass, rol = 'admin', verificado = 1 WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':pass' => $hash,
            ':id'   => $id_usuario
        ]);

        // Enviar email al nuevo admin
        include_once '../config/mailer.php';
        $cuerpo = "<h3>¡Bienvenido al equipo, " . htmlspecialchars($user['nick']) . "!</h3>
                   <p>El Superadmin ha activado tu cuenta de Administrador en ARK Hub.</p>
                   <p>Ya puedes acceder con las siguientes credenciales:</p>
                   <p><strong>Contraseña asignada:</strong> $nueva_password</p>
                   <p><em>(Te recomendamos cambiarla desde tu perfil una vez entres).</em></p>";
        sendArkEmail($user['email'], "Tu cuenta de Admin ha sido activada - ARK Hub", $cuerpo);

        header("Location: ../panel_superadmin.php?status=actualizado#gestion-equipo");
        exit();

    } catch (PDOException $e) {
        error_log("Error al activar admin: " . $e->getMessage());
        header("Location: ../panel_superadmin.php?error=db#gestion-equipo");
        exit();
    }
} else {
    header("Location: ../panel_superadmin.php#gestion-equipo");
    exit();
}
?>
