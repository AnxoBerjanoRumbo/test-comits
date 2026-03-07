<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];

    try {
        $sql = "DELETE FROM usuarios WHERE id = :id AND rol = 'admin' AND password = ''";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id' => $id_usuario]);

        header("Location: panel_superadmin.php?status=cancelado");
        exit();

    } catch (PDOException $e) {
        echo "Error al cancelar la solicitud: " . $e->getMessage();
    }
} else {
    header("Location: panel_superadmin.php");
    exit();
}
?>
