<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_notif = (int)$_GET['id'];
$u_id = $_SESSION['usuario_id'];

$sql = "SELECT * FROM notificaciones WHERE id = :id AND id_usuario = :u_id";
$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id_notif, ':u_id' => $u_id]);
$notif = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notif) {
    header("Location: index.php");
    exit();
}

// Marcar como leída
if ($notif['leida'] == 0) {
    $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE id = :id")->execute([':id' => $id_notif]);
}

// Separar asunto del cuerpo si empieza por [Mensaje]
$asunto = 'Notificación del Sistema';
$cuerpo = $notif['mensaje'];
if (strpos($notif['mensaje'], '[Mensaje] ') === 0) {
    $sin_prefijo = substr($notif['mensaje'], strlen('[Mensaje] '));
    $pos_salto = strpos($sin_prefijo, "\n\n");
    if ($pos_salto !== false) {
        $asunto = substr($sin_prefijo, 0, $pos_salto);
        $cuerpo = substr($sin_prefijo, $pos_salto + 2);
    }
}

$fecha_formateada = date("d/m/Y H:i", strtotime($notif['fecha']));
$header_titulo = "Notificación";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asunto); ?> - ARK Hub</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.0">
    <style>
        .notif-card {
            background: linear-gradient(135deg, rgba(0,255,204,0.04), rgba(0,0,0,0));
            border: 1px solid var(--border-color);
            border-top: 3px solid var(--accent);
            border-radius: var(--radius);
            padding: 35px 40px;
            max-width: 700px;
            margin: 40px auto;
        }
        .notif-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .notif-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0,255,204,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .notif-icon .material-symbols-outlined {
            color: var(--accent);
            font-size: 1.4rem;
        }
        .notif-asunto {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0 0 3px 0;
        }
        .notif-fecha-text {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .notif-cuerpo {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text-main);
            white-space: pre-wrap;
            word-break: break-word;
            padding: 20px 0 5px;
        }
        .notif-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-back:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="contenedor-formulario">
        <div class="notif-card">
            <div class="notif-meta">
                <div class="notif-icon">
                    <span class="material-symbols-outlined">mail</span>
                </div>
                <div>
                    <p class="notif-asunto"><?php echo htmlspecialchars($asunto); ?></p>
                    <p class="notif-fecha-text">Recibida el <?php echo $fecha_formateada; ?></p>
                </div>
            </div>

            <div class="notif-cuerpo">
                <?php echo htmlspecialchars($cuerpo); ?>
            </div>

            <div class="notif-actions">
                <a href="index.php" class="btn-back">
                    <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span>
                    Volver a la wiki
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
