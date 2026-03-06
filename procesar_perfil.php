<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

$usuario_id = $_SESSION['usuario_id'];
$nueva_password = $_POST['nueva_password'];
$confirmar_password = $_POST['confirmar_password'];

try {
    $conexion->beginTransaction();

    // Actualizar contraseña si se proporcionó
    if (!empty($nueva_password)) {
        if ($nueva_password !== $confirmar_password) {
            header("Location: perfil.php?error=pass_no_coincide");
            exit();
        }
        if (strlen($nueva_password) < 4) {
            header("Location: perfil.php?error=pass_corta");
            exit();
        }
        
        $sqlPass = "UPDATE usuarios SET password = :p WHERE id = :id";
        $stmtPass = $conexion->prepare($sqlPass);
        $stmtPass->execute([':p' => $nueva_password, ':id' => $usuario_id]);
    }

    // Subida de foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $img_name = $_FILES['foto_perfil']['name'];
        $img_tmp = $_FILES['foto_perfil']['tmp_name'];
        
        $extension = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $validas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $validas)) {
            $nuevo_nombre = 'user_' . $usuario_id . '_' . uniqid() . '.' . $extension;
            $destino = 'assets/img/perfil/' . $nuevo_nombre;
            
            if (move_uploaded_file($img_tmp, $destino)) {
                $sqlImg = "UPDATE usuarios SET foto_perfil = :img WHERE id = :id";
                $stmtImg = $conexion->prepare($sqlImg);
                $stmtImg->execute([':img' => $nuevo_nombre, ':id' => $usuario_id]);
                $_SESSION['foto_perfil'] = $nuevo_nombre;
            } else {
                $conexion->rollBack();
                header("Location: perfil.php?error=upload");
                exit();
            }
        }
    }

    $conexion->commit();
    header("Location: perfil.php?status=success");
    exit();

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    header("Location: perfil.php?error=db");
    exit();
}
