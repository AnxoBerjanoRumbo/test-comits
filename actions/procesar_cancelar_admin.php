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

    try {
        // Obtener el email del usuario antes de borrarlo
        $stmt_u = $conexion->prepare("SELECT email, nick FROM usuarios WHERE id = :id");
        $stmt_u->execute([':id' => $id_usuario]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Mandar el correo de aviso de rechazo
            include_once '../config/mailer.php';
            $cuerpo = "<h3>Hola " . htmlspecialchars($user['nick']) . ",</h3>
                       <p>Lamentamos informarte que tu solicitud de acceso como Administrador en ARK Hub ha sido denegada.</p>
                       <p>Tu registro provisional ha sido eliminado.</p>";
            sendArkEmail($user['email'], "Solicitud de Admin denegada - ARK Hub", $cuerpo);

            $sql = "DELETE FROM usuarios WHERE id = :id AND rol = 'admin'";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);
        }

        header("Location: ../panel_superadmin.php?status=cancelado");
        exit();

    } catch (PDOException $e) {
        error_log("Error al cancelar la solicitud: " . $e->getMessage());
        header("Location: ../panel_superadmin.php?error=interno");
        exit();
    }
} else {
    header("Location: ../panel_superadmin.php");
    exit();
}
?>
