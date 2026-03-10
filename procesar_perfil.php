<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

// Validación CSRF obligatoria
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error de validación CSRF.");
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // Subida de foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $img_name = $_FILES['foto_perfil']['name'];
        $img_tmp = $_FILES['foto_perfil']['tmp_name'];
        
        $extension = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $validas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $validas)) {
            // Verificar si es una imagen real
            if (@getimagesize($img_tmp)) {
                
                // INTENTO DE SUBIDA A CLOUDINARY
                include_once 'config/cloudinary_helper.php';
                $url_cloudinary = subirImagenACloudinary($img_tmp, 'perfiles');

                if ($url_cloudinary) {
                    $nuevo_valor_db = $url_cloudinary;
                } else {
                    // FALLBACK: Almacenamiento local si Cloudinary no está configurado o falla
                    $nuevo_nombre = 'user_' . $usuario_id . '_' . uniqid() . '.' . $extension;
                    $destino = 'assets/img/perfil/' . $nuevo_nombre;
                    if (move_uploaded_file($img_tmp, $destino)) {
                        $nuevo_valor_db = $nuevo_nombre;
                    } else {
                        header("Location: perfil.php?error=upload");
                        exit();
                    }
                }

                // Borrar foto anterior LOCAL si no es la default
                $stmt_old = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id = :id");
                $stmt_old->execute([':id' => $usuario_id]);
                $old_photo = $stmt_old->fetchColumn();
                
                // Si la foto vieja era local y no default, la borramos
                if ($old_photo && $old_photo !== 'default.png' && strpos($old_photo, 'http') === false) {
                    $old_path = 'assets/img/perfil/' . $old_photo;
                    if (file_exists($old_path)) unlink($old_path);
                }

                $sqlImg = "UPDATE usuarios SET foto_perfil = :img WHERE id = :id";
                $stmtImg = $conexion->prepare($sqlImg);
                $stmtImg->execute([':img' => $nuevo_valor_db, ':id' => $usuario_id]);
                $_SESSION['foto_perfil'] = $nuevo_valor_db;
                
                header("Location: perfil.php?status=success");
                exit();

            } else {
                header("Location: perfil.php?error=upload");
                exit();
            }
        } else {
            header("Location: perfil.php?error=upload");
            exit();
        }
    } else {
        header("Location: perfil.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error al subir foto: " . $e->getMessage());
    header("Location: perfil.php?error=db");
    exit();
}
?>
