<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}
include '../config/db.php';
include_once '../config/verificar_sesion.php';
check_user_active_status($conexion);

// Validación CSRF obligatoria
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // Subida de foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        include_once '../config/cloudinary_helper.php';
        $resultado = gestionarSubidaImagen($_FILES['foto_perfil'], 'perfiles', '../assets/img/perfil/', 'user_' . $usuario_id . '_');

        if ($resultado) {
            // Borrar foto anterior (local o Cloudinary) si no es la default
            $stmt_old = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
            $stmt_old->execute([':id' => $usuario_id]);
            $old_photo = $stmt_old->fetchColumn();

            if ($old_photo && $old_photo !== 'default.png') {
                if (strpos($old_photo, 'http') !== false) {
                    eliminarImagenDeCloudinary($old_photo);
                }
                else {
                    $old_path = '../assets/img/perfil/' . $old_photo;
                    if (file_exists($old_path)) unlink($old_path);
                }
            }

            $sqlImg = "UPDATE usuarios SET foto_perfil = :img WHERE id = :id";
            $stmtImg = $conexion->prepare($sqlImg);
            $stmtImg->execute([':img' => $resultado, ':id' => $usuario_id]);
            $_SESSION['foto_perfil'] = $resultado;

            header("Location: ../perfil.php?status=success");
            exit();
        } else {
            header("Location: ../perfil.php?error=upload");
            exit();
        }
    }

    else {
        header("Location: ../perfil.php");
        exit();
    }

}
catch (PDOException $e) {
    error_log("Error al subir foto: " . $e->getMessage());
    header("Location: ../perfil.php?error=db");
    exit();
}
?>
