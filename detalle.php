<?php
session_start();
include 'config/db.php';

// 1. Recogemos el ID del dinosaurio desde la URL (con validación básica)
$id = isset($_GET['id']) ? $_GET['id'] : 1;

// 2. Consulta del dinosaurio
$sql_dino = "SELECT * FROM dinosaurios WHERE id = :id";
$stmt = $conexion->prepare($sql_dino);
$stmt->bindParam(':id', $id);
$stmt->execute();
$dino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dino) {
    header("Location: index.php");
    exit();
}

// 3. Consulta de mapas con JOIN 
$sql_mapas = "SELECT m.nombre_mapa 
              FROM mapas m 
              INNER JOIN dino_mapas dm ON m.id = dm.mapa_id 
              WHERE dm.dino_id = :id";
$stmt_mapas = $conexion->prepare($sql_mapas);
$stmt_mapas->bindParam(':id', $id);
$stmt_mapas->execute();
$mapas = $stmt_mapas->fetchAll(PDO::FETCH_ASSOC);

// 3b. Categorias del dinosaurio
$sql_cats = "SELECT c.nombre FROM categorias c
             INNER JOIN dino_categorias dc ON c.id = dc.categoria_id
             WHERE dc.dino_id = :id
             ORDER BY c.orden ASC";
$stmt_cats = $conexion->prepare($sql_cats);
$stmt_cats->bindParam(':id', $id);
$stmt_cats->execute();
$cats_dino = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

// 4. Obtener características especiales del dinosaurio
$sql_features = "SELECT 
    es_tanque, es_buff, es_recolector, es_montura, es_volador, es_acuatico, es_subterraneo,
    buff_descripcion, buff_damage, buff_armor, buff_speed, buff_otro,
    tiene_formas, formas_descripcion,
    recolecta_carne, recolecta_pescado, recolecta_madera, recolecta_piedra, 
    recolecta_metal, recolecta_bayas, recolecta_paja, recolecta_fibra, recolecta_texugo,
    domable, metodo_domado, comida_favorita, nivel_max_salvaje,
    tiempo_incubacion, tiempo_madurez, ayuda_cria, ayuda_cria_descripcion
    FROM dinosaurios WHERE id = :id";
$stmt_features = $conexion->prepare($sql_features);
$stmt_features->bindParam(':id', $id);
$stmt_features->execute();
$features = $stmt_features->fetch(PDO::FETCH_ASSOC);

// 4. Paginación de Comentarios
$comentarios_por_pagina = 10;
$pagina_actual = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
if ($pagina_actual < 1)
    $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $comentarios_por_pagina;

// 4.1. Contar total de comentarios RAÍZ (los que no son respuesta)
$sql_count = "SELECT COUNT(*) FROM comentarios WHERE dino_id = :id AND respuesta_a IS NULL";
$stmt_count = $conexion->prepare($sql_count);
$stmt_count->bindParam(':id', $id);
$stmt_count->execute();
$total_comentarios = $stmt_count->fetchColumn();
$total_paginas = ceil($total_comentarios / $comentarios_por_pagina);

// 4.2. Consulta de comentarios RAÍZ con LIMIT y OFFSET
$sql_comments = "SELECT c.*, u.nick, u.rol, u.foto_perfil 
                 FROM comentarios c 
                 JOIN usuarios u ON c.usuario_id = u.id 
                 WHERE c.dino_id = :id AND c.respuesta_a IS NULL
                 ORDER BY c.id DESC 
                 LIMIT :limit OFFSET :offset";
$stmt_comments = $conexion->prepare($sql_comments);
$stmt_comments->bindParam(':id', $id, PDO::PARAM_INT);
$stmt_comments->bindParam(':limit', $comentarios_por_pagina, PDO::PARAM_INT);
$stmt_comments->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_comments->execute();
$comentarios = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

// 4.3. Recoger todas las respuestas para estos comentarios
$respuestas = [];
if (count($comentarios) > 0) {
    $ids_raiz = array_column($comentarios, 'id');
    $placeholders = implode(',', array_fill(0, count($ids_raiz), '?'));

    $sql_resp = "SELECT c.*, u.nick, u.rol, u.foto_perfil 
                 FROM comentarios c 
                 JOIN usuarios u ON c.usuario_id = u.id 
                 WHERE c.respuesta_a IN ($placeholders)
                 ORDER BY c.id ASC";
    $stmt_resp = $conexion->prepare($sql_resp);
    $stmt_resp->execute($ids_raiz);
    $all_resp = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_resp as $r) {
        $respuestas[$r['respuesta_a']][] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dino['nombre']); ?> - ARK Survival Hub</title>
    <meta name="description"
        content="Ficha completa de <?php echo htmlspecialchars($dino['nombre']); ?> en ARK. Stats, calculadora de niveles, consejos de crianza y más.">
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ── HERO ── */
        .dino-hero {
            position: relative;
            width: 100%;
            min-height: 420px;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            background: #0a0a0a;
        }

        .dino-hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center 30%;
            filter: brightness(0.35) saturate(1.2);
            transform: scale(1.05);
            transition: transform 8s ease;
        }

        .dino-hero:hover .dino-hero-bg {
            transform: scale(1.08);
        }

        .dino-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(8, 8, 8, 1) 0%, rgba(8, 8, 8, 0.5) 50%, transparent 100%);
        }

        .dino-hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px 50px;
        }

        .dino-hero-nombre {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 900;
            color: #fff;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.8);
            margin: 0 0 8px 0;
            line-height: 1.05;
        }

        .dino-hero-especie {
            font-size: 1rem;
            color: var(--accent);
            font-style: italic;
            margin: 0 0 20px 0;
            opacity: 0.9;
        }

        .dino-hero-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 600;
            backdrop-filter: blur(6px);
        }

        .hero-tag-dieta {
            background: rgba(var(--accent-rgb), 0.12);
            border: 1px solid rgba(var(--accent-rgb), 0.3);
            color: var(--accent);
        }

        .hero-tag-mapa {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ccc;
        }

        .hero-tag-cat {
            background: rgba(255, 152, 0, 0.12);
            border: 1px solid rgba(255, 152, 0, 0.3);
            color: #ff9800;
        }


        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── STAT CALCULATOR ── */
        .stat-slider-row {
            display: grid;
            grid-template-columns: 105px 1fr 105px 60px 80px;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-slider-row:last-child {
            border-bottom: none;
        }

        .stat-slider-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stat-slider-input {
            -webkit-appearance: none;
            appearance: none;
            height: 6px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            cursor: pointer;
        }

        .stat-slider-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            background: var(--thumb-color, var(--accent));
        }

        .stat-slider-input::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            background: var(--thumb-color, var(--accent));
        }

        .stat-level-num {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 4px 8px;
        }

        .stat-calc-value {
            text-align: right;
            font-size: 0.95rem;
            font-weight: 800;
        }

        /* ── BREEDING TIPS ── */
        .tip-card {
            display: flex;
            gap: 18px;
            padding: 22px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 15px;
            transition: border-color 0.2s;
        }

        .tip-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .tip-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 680px) {
            .stat-slider-row {
                grid-template-columns: 90px 1fr 85px 50px;
                gap: 8px;
            }

            .stat-calc-value {
                display: none;
            }

            .stats-grid-calc {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>

<body>
    <?php
    $params_volver = $_GET;
    unset($params_volver['id'], $params_volver['p'], $params_volver['status'], $params_volver['error']);
    $header_titulo = $dino['nombre'];
    $header_volver_link = "index.php" . (!empty($params_volver) ? '?' . http_build_query($params_volver) : '');
    $header_volver_texto = "Volver al listado";
    include 'includes/header.php';
    ?>

    <?php
    // Preparar datos para el hero
    $src_hero = '';
    if (!empty($dino['imagen'])) {
        $src_hero = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : "assets/img/dinos/" . $dino['imagen'];
        if (strpos($src_hero, 'res.cloudinary.com') !== false) {
            $src_hero = str_replace('/upload/', '/upload/f_auto,q_auto,w_1600,c_limit/', $src_hero);
        }
    }

    // Preparar stats
    $stats_data = [
        'health' => (int) ($dino['stat_health'] ?? 0),
        'stamina' => (int) ($dino['stat_stamina'] ?? 0),
        'oxygen' => (int) ($dino['stat_oxygen'] ?? 0),
        'food' => (int) ($dino['stat_food'] ?? 0),
        'weight' => (int) ($dino['stat_weight'] ?? 0),
        'melee' => (int) ($dino['stat_melee'] ?? 0),
        'speed' => (int) ($dino['stat_speed'] ?? 0),
        'torpidity' => (int) ($dino['stat_torpidity'] ?? 0),
    ];
    $tiene_stats = array_sum($stats_data) > 0;

    // Dieta para tips contextuales
    $esCarnivoro = in_array($dino['dieta'], ['Carnívoro', 'Piscívoro']);
    $esHerbivoro = $dino['dieta'] === 'Herbívoro';
    $esOmnivoro = $dino['dieta'] === 'Omnívoro';
    $esPoneHuevos = in_array(strtolower($dino['nombre']), ['pteranodon', 'quetzal', 'argentavis', 'rex', 'spino', 'raptor', 'brontosaurus', 'bronto', 'iguanodon', 'stego', 'ankylo', 'mammoth', 'pachy', 'carno', 'allosaurus', 'allosaurio', 'yutyrannus', 'diplocaulus', 'dimetrodon', 'oviraptor', 'archaeopteryx', 'lystrosaurus', 'hesperornis', 'kaprosuchus', 'pegomastax', 'microraptor', 'basilosaurus', 'ichthyornis', 'terror bird', 'gastornis', 'arthropluera', 'camelsaurus', 'gigantopithecus', 'diplodocus', 'diplodoco']);
    $nombreLower = strtolower($dino['nombre']);
    ?>

    <!-- HERO -->
    <div class="dino-hero">
        <?php if ($src_hero): ?>
            <div class="dino-hero-bg" style="background-image: url('<?php echo htmlspecialchars($src_hero); ?>');"></div>
        <?php endif; ?>
        <div class="dino-hero-overlay"></div>
        <div class="dino-hero-content">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'edit_success'): ?>
                <div class="alerta-exito mb-20" style="max-width:600px;">✓ Criatura actualizada correctamente.</div>
            <?php endif; ?>

            <h1 class="dino-hero-nombre"><?php echo htmlspecialchars($dino['nombre']); ?></h1>
            <p class="dino-hero-especie"><?php echo htmlspecialchars($dino['especie']); ?></p>

            <div class="dino-hero-tags">
                <span class="hero-tag hero-tag-dieta">
                    <span class="material-symbols-outlined" style="font-size:14px;">restaurant</span>
                    <?php echo htmlspecialchars($dino['dieta']); ?>
                </span>
                <?php foreach ($mapas as $mapa): ?>
                    <span class="hero-tag hero-tag-mapa">
                        <span class="material-symbols-outlined" style="font-size:14px;">map</span>
                        <?php echo htmlspecialchars($mapa['nombre_mapa']); ?>
                    </span>
                <?php endforeach; ?>
                <?php foreach ($cats_dino as $cat): ?>
                    <span class="hero-tag hero-tag-cat">
                        <span class="material-symbols-outlined" style="font-size:14px;">label</span>
                        <?php echo htmlspecialchars($cat); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- NAVEGACIÓN DE TABS -->
    <div class="dino-tabs-nav" id="dino-tabs-nav">
        <button class="dino-tab-btn active" onclick="switchDinoTab('info', this)">
            <span class="material-symbols-outlined">description</span> Info
        </button>
        <?php if ($tiene_stats): ?>
            <button class="dino-tab-btn" onclick="switchDinoTab('stats', this)">
                <span class="material-symbols-outlined">radar</span> Stats
            </button>
        <?php endif; ?>
        <button class="dino-tab-btn" onclick="switchDinoTab('habilidades', this)">
            <span class="material-symbols-outlined">stars</span> Roles y Utilidad
        </button>
        <button class="dino-tab-btn" onclick="switchDinoTab('comentarios', this)" id="tab-btn-comentarios">
            <span class="material-symbols-outlined">forum</span>
            Foro
            <?php if ($total_comentarios > 0): ?>
                <span style="background:var(--accent);color:var(--accent-text);border-radius:20px;padding:1px 7px;font-size:0.72rem; font-weight:800;"><?php echo $total_comentarios; ?></span>
            <?php endif; ?>
        </button>
        <?php if ($tiene_stats): ?>
        <button class="dino-tab-btn" onclick="switchDinoTab('comparar', this)">
            <span class="material-symbols-outlined">compare_arrows</span> Comparar
        </button>
        <?php endif; ?>
    </div>

    <main class="contenedor-detalle">

        <!-- ══════════════════════════════════════════
         TAB: INFO
    ══════════════════════════════════════════ -->
        <div id="tab-info" class="dino-tab-panel active">

            <?php if (!empty($dino['descripcion'])): ?>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                    <a href="admin/editar.php?id=<?php echo $dino['id']; ?>"
                        style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; background:rgba(var(--accent-rgb),0.1); color:var(--accent); font-weight:700; border-radius:8px; border:1px solid rgba(var(--accent-rgb),0.3); text-decoration:none; font-size:0.82rem; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(var(--accent-rgb),0.2)'" onmouseout="this.style.background='rgba(var(--accent-rgb),0.1)'">
                        <span class="material-symbols-outlined" style="font-size:0.95rem;">edit</span>
                        Editar Criatura
                    </a>
                </div>
                <?php endif; ?>
                <!-- CARD DE DESCRIPCIÓN -->
                <div style="background:linear-gradient(135deg, rgba(20,20,20,0.95) 0%, rgba(10,10,10,0.85) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:35px; margin-bottom:30px; box-shadow:0 15px 40px rgba(0,0,0,0.5); position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-60px; right:-60px; width:180px; height:180px; background:var(--accent); filter:blur(110px); opacity:0.15; z-index:0; border-radius:50%;"></div>
                    <h3 style="margin:0 0 22px; font-size:1.6rem; color:#fff; display:flex; align-items:center; gap:12px; position:relative; z-index:1; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:18px;">
                        <span class="material-symbols-outlined" style="font-size:1.9rem; color:var(--accent);">auto_stories</span>
                        Ficha Técnica y Descripción
                    </h3>
                    <div style="font-size:1.05rem; line-height:1.75; color:rgba(255,255,255,0.78); position:relative; z-index:1; text-align:justify; font-weight:400;">
                        <?php echo nl2br(htmlspecialchars($dino['descripcion'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1 && empty($dino['descripcion'])): ?>
                <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
                    <a href="admin/editar.php?id=<?php echo $dino['id']; ?>"
                        style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; background:rgba(var(--accent-rgb),0.1); color:var(--accent); font-weight:700; border-radius:8px; border:1px solid rgba(var(--accent-rgb),0.3); text-decoration:none; font-size:0.82rem; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(var(--accent-rgb),0.2)'" onmouseout="this.style.background='rgba(var(--accent-rgb),0.1)'">
                        <span class="material-symbols-outlined" style="font-size:0.95rem;">edit</span>
                        Editar Criatura
                    </a>
                </div>
            <?php endif; ?>

            <?php
            // Recoger regiones con datos
            $regiones = [];
            for ($r = 0; $r < 6; $r++) {
                $nombre  = trim($dino["region_{$r}_nombre"]  ?? '');
                $colores = trim($dino["region_{$r}_colores"] ?? '');
                if ($nombre !== '' || $colores !== '') {
                    $lista = array_filter(array_map('trim', explode(',', $colores)));
                    $regiones[$r] = ['nombre' => $nombre ?: "Región $r", 'colores' => $lista];
                }
            }
            if (!empty($regiones)): ?>
            <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:16px; padding:28px; margin-top:10px;">
                <h3 style="margin:0 0 20px; font-size:1.1rem; color:var(--accent); display:flex; align-items:center; gap:10px; text-transform:uppercase; letter-spacing:1px; font-weight:800;">
                    <span class="material-symbols-outlined" style="font-size:1.3rem;">palette</span>
                    Regiones de Color
                </h3>
                <p style="margin:0 0 20px; font-size:0.82rem; color:var(--text-muted);">
                    ARK permite personalizar el color de cada región de la criatura. Haz clic en un color para copiarlo.
                </p>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
                    <?php foreach ($regiones as $idx => $reg): ?>
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                            <span style="background:rgba(var(--accent-rgb),0.15); color:var(--accent); font-size:0.72rem; font-weight:800; padding:3px 8px; border-radius:20px; flex-shrink:0;">Región <?php echo $idx; ?></span>
                            <span style="font-size:0.88rem; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($reg['nombre']); ?></span>
                        </div>
                        <?php if (!empty($reg['colores'])): ?>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <?php foreach ($reg['colores'] as $hex):
                                if (!preg_match('/^#[0-9A-Fa-f]{3,6}$/', $hex)) continue;
                                // Calcular si el texto encima debe ser claro u oscuro
                                $r_val = hexdec(substr($hex, 1, 2));
                                $g_val = hexdec(substr($hex, 3, 2));
                                $b_val = hexdec(substr($hex, 5, 2));
                                $luminance = (0.299*$r_val + 0.587*$g_val + 0.114*$b_val) / 255;
                                $text_color = $luminance > 0.5 ? '#000' : '#fff';
                            ?>
                            <button type="button"
                                onclick="navigator.clipboard.writeText('<?php echo $hex; ?>').then(()=>{this.title='¡Copiado!';setTimeout(()=>this.title='<?php echo $hex; ?>',1500)})"
                                title="<?php echo $hex; ?>"
                                style="width:36px; height:36px; border-radius:8px; background:<?php echo $hex; ?>; border:2px solid rgba(255,255,255,0.15); cursor:pointer; transition:transform 0.15s, box-shadow 0.15s; position:relative;"
                                onmouseover="this.style.transform='scale(1.15)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.4)'"
                                onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <p style="margin:8px 0 0; font-size:0.7rem; color:var(--text-muted);"><?php echo count($reg['colores']); ?> color<?php echo count($reg['colores']) !== 1 ? 'es' : ''; ?> disponible<?php echo count($reg['colores']) !== 1 ? 's' : ''; ?> · Clic para copiar HEX</p>
                        <?php else: ?>
                        <p style="font-size:0.8rem; color:var(--text-muted); font-style:italic;">Sin colores registrados</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /tab-info -->


        <!-- ══════════════════════════════════════════
         TAB: STATS (solo si tiene datos)
    ══════════════════════════════════════════ -->
        <?php if ($tiene_stats):
        $stat_defs = [
            'health'    => ['Vida',      'favorite',   '#e74c3c', (float)($dino['iw_health']    ?? 0.2),  $stats_data['health']],
            'stamina'   => ['Energía',   'bolt',       '#f39c12', (float)($dino['iw_stamina']   ?? 0.1),  $stats_data['stamina']],
            'oxygen'    => ['Oxígeno',   'water_drop', '#3498db', (float)($dino['iw_oxygen']    ?? 0.1),  $stats_data['oxygen']],
            'food'      => ['Comida',    'restaurant', '#2ecc71', (float)($dino['iw_food']      ?? 0.15), $stats_data['food']],
            'weight'    => ['Peso',      'weight',     '#9b59b6', (float)($dino['iw_weight']    ?? 0.02), $stats_data['weight']],
            'melee'     => ['Melée',     'swords',     '#e67e22', (float)($dino['iw_melee']     ?? 0.05), $stats_data['melee']],
            'speed'     => ['Velocidad', 'speed',      '#1abc9c', (float)($dino['iw_speed']     ?? 0.0),  $stats_data['speed']],
            'torpidity' => ['Torpor',    'bedtime',    '#95a5a6', (float)($dino['iw_torpidity'] ?? 0.06), $stats_data['torpidity']],
        ];
        ?>
            <div id="tab-stats" class="dino-tab-panel">

                <!-- Aviso disclaimer -->
                <div style="display:flex; align-items:center; gap:10px; background:rgba(var(--accent-rgb),0.07); border:1px solid rgba(var(--accent-rgb),0.25); border-radius:8px; padding:14px 18px; margin-bottom:30px;">
                    <span class="material-symbols-outlined" style="color:var(--accent); flex-shrink:0;">info</span>
                    <p style="margin:0; font-size:0.83rem; color:var(--text-muted);">Stats para <strong style="color:var(--text-main);">servidores vanilla sin multiplicadores</strong>.
                    Simula las 3 fases del juego: <strong style="color:#95a5a6;">Wild</strong> → <strong style="color:var(--accent);">Tamed</strong> → <strong style="color:#ff9800;">Bred</strong>.</p>
                </div>

                <div class="stats-grid-calc" style="display:grid; grid-template-columns:1fr 1.2fr; gap:40px; align-items:start;">

                    <!-- Radar Chart -->
                    <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:var(--radius); padding:25px; position:sticky; top:80px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                            <h4 style="margin:0; color:var(--text-muted); font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">Gráfico Radar · Valores calculados</h4>
                            <button onclick="abrirRadarModal('statsRadar')" title="Ampliar gráfico"
                                style="background:rgba(var(--accent-rgb),0.1); border:1px solid rgba(var(--accent-rgb),0.3); color:var(--accent); border-radius:6px; padding:5px 8px; cursor:pointer; display:flex; align-items:center; transition:0.2s;"
                                onmouseover="this.style.background='rgba(var(--accent-rgb),0.2)'" onmouseout="this.style.background='rgba(var(--accent-rgb),0.1)'">
                                <span class="material-symbols-outlined" style="font-size:1.1rem;">zoom_in</span>
                            </button>
                        </div>
                        <canvas id="statsRadar" style="max-height:320px;"></canvas>
                        <div style="text-align:center; margin-top:16px;">
                            <span id="radar-mode-label" style="font-size:0.78rem; color:var(--accent); font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Nivel Salvaje (Wild)</span>
                        </div>
                    </div>

                    <div>

                        <!-- ══ FASE 1: WILD ══ -->
                        <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:20px; margin-bottom:20px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:10px;">
                                <h4 style="margin:0; font-size:0.9rem; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:1px;">
                                    <span class="material-symbols-outlined" style="color:#95a5a6; font-size:1.2rem;">travel_explore</span>
                                    Fase 1 — Salvaje (Wild)
                                </h4>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span style="color:var(--text-muted); font-size:0.82rem;">Nivel:</span>
                                        <input type="number" id="gen-level" value="150" min="1" max="450"
                                            style="width:70px; padding:5px 8px; border-radius:6px; background:rgba(0,0,0,0.4); border:1px solid var(--border-color); color:#fff; font-family:inherit; font-weight:800; font-size:0.95rem; outline:none; text-align:center;">
                                    </div>
                                    <button onclick="rollWildStats()" style="background:var(--accent); color:var(--accent-text); font-weight:700; border:none; border-radius:6px; padding:6px 14px; display:flex; align-items:center; gap:4px; cursor:pointer; font-family:inherit; font-size:0.8rem; text-transform:uppercase; transition:0.3s;" onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter='none'">
                                        <span class="material-symbols-outlined" style="font-size:0.95rem;">casino</span> Rolear
                                    </button>
                                    <button onclick="resetSliders()" style="background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-muted); border-radius:6px; padding:6px 12px; cursor:pointer; font-size:0.8rem; font-family:inherit; transition:0.3s; display:flex; align-items:center; gap:4px;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                                        <span class="material-symbols-outlined" style="font-size:0.9rem;">refresh</span> Reset
                                    </button>
                                </div>
                            </div>
                            <p style="margin:0 0 14px; font-size:0.76rem; color:var(--text-muted);">Cada punto de nivel sube el stat un <code style="background:rgba(255,255,255,0.06); padding:1px 4px; border-radius:3px;">Iw</code>. Las mutaciones cuentan como +2 niveles wild por stat.</p>

                            <?php foreach ($stat_defs as $key => [$label, $icon, $color, $iw, $base]):
                                if ($base <= 0) continue; ?>
                            <div class="stat-slider-row">
                                <div class="stat-slider-label" style="color:<?php echo $color; ?>;">
                                    <span class="material-symbols-outlined" style="font-size:1rem;"><?php echo $icon; ?></span>
                                    <?php echo $label; ?>
                                </div>
                                <input type="range" min="0" max="150" value="0" class="stat-slider-input"
                                    id="slider-<?php echo $key; ?>" data-base="<?php echo $base; ?>"
                                    data-iw="<?php echo $iw; ?>" data-stat="<?php echo $key; ?>"
                                    style="--thumb-color:<?php echo $color; ?>;" oninput="updateStats()"
                                    <?php if ($iw == 0) echo 'disabled title="Este stat no sube en estado salvaje"'; ?>>
                                <div style="display:flex; align-items:center; gap:4px; background:rgba(255,255,255,0.05); padding:3px 6px; border-radius:6px;">
                                    <span style="color:#aaa; font-size:0.8rem;">+</span>
                                    <input type="number" id="mut-<?php echo $key; ?>" value="0" min="0" max="254" oninput="updateStats()"
                                        style="width:36px; background:transparent; border:none; color:var(--accent); font-weight:800; font-family:inherit; text-align:center; outline:none;"
                                        <?php if($iw==0) echo 'disabled'; ?>>
                                    <span style="color:#aaa; font-size:0.7rem; font-weight:700;">Mut</span>
                                </div>
                                <div class="stat-level-num" id="level-<?php echo $key; ?>">Lv 0</div>
                                <div class="stat-calc-value" id="val-<?php echo $key; ?>" style="color:<?php echo $color; ?>;">
                                    <?php echo number_format($base, 1); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div style="margin-top:14px; padding:10px 14px; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid var(--border-color); display:flex; align-items:center; gap:10px;">
                                <span style="font-size:0.78rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Nivel wild total:</span>
                                <span id="nivel-total" style="font-size:1.5rem; font-weight:900; color:var(--accent);">0</span>
                                <span style="font-size:0.8rem; color:var(--text-muted);">/ 150 puntos</span>
                            </div>
                        </div>

                        <!-- ══ FASE 2: TAMED ══ -->
                        <div style="background:rgba(var(--accent-rgb),0.04); border:1px solid rgba(var(--accent-rgb),0.25); border-radius:12px; padding:20px; margin-bottom:20px;">
                            <h4 style="margin:0 0 6px; font-size:0.9rem; font-weight:800; color:var(--accent); display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:1px;">
                                <span class="material-symbols-outlined" style="font-size:1.2rem;">pets</span>
                                Fase 2 — Domesticado (Tamed)
                            </h4>
                            <p style="margin:0 0 14px; font-size:0.76rem; color:var(--text-muted);">
                                Al domar, el dino recibe un bonus en todos los stats según la eficiencia (100% = sin daño recibido). Además puedes invertir hasta 73 niveles extra en el stat que quieras.
                            </p>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                                <div>
                                    <label style="display:flex; align-items:center; justify-content:space-between; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:600;">
                                        Eficiencia de Taming <span id="tej-val" style="color:var(--accent); font-weight:800;">100%</span>
                                    </label>
                                    <input type="range" id="taming-slider" min="0" max="100" value="100" class="stat-slider-input" style="--thumb-color:var(--accent);" oninput="updateStats()">
                                    <p style="margin:5px 0 0; font-size:0.71rem; color:var(--text-muted);">100% = sin daño. Afecta al bonus de todos los stats.</p>
                                </div>
                                <div>
                                    <label style="display:flex; align-items:center; justify-content:space-between; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:600;">
                                        Niveles domesticados <span id="tamed-levels-val" style="color:var(--accent); font-weight:800;">0</span>
                                    </label>
                                    <input type="range" id="tamed-levels-slider" min="0" max="73" value="0" class="stat-slider-input" style="--thumb-color:var(--accent);" oninput="updateStats()">
                                    <p style="margin:5px 0 0; font-size:0.71rem; color:var(--text-muted);">Puntos extra tras domarlo (máx. 73 en vanilla).</p>
                                </div>
                            </div>
                            <div>
                                <p style="margin:0 0 7px; font-size:0.76rem; color:var(--text-muted); font-weight:600;">¿En qué stat inviertes los niveles domesticados?</p>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;" id="tamed-stat-selector">
                                    <?php foreach ($stat_defs as $key => [$label, $icon, $color, $iw, $base]):
                                        if ($base <= 0) continue; ?>
                                    <button type="button" class="tamed-stat-btn <?php echo $key === 'health' ? 'active' : ''; ?>"
                                        data-stat="<?php echo $key; ?>" data-color="<?php echo $color; ?>"
                                        style="background:<?php echo $key === 'health' ? $color : 'rgba(255,255,255,0.05)'; ?>; color:<?php echo $key === 'health' ? '#fff' : 'var(--text-muted)'; ?>; border:1px solid <?php echo $key === 'health' ? $color : 'var(--border-color)'; ?>; border-radius:20px; padding:4px 10px; font-size:0.76rem; font-weight:600; cursor:pointer; font-family:inherit; transition:0.2s; display:flex; align-items:center; gap:4px;"
                                        onclick="selectTamedStat(this, '<?php echo $key; ?>', '<?php echo $color; ?>')">
                                        <span class="material-symbols-outlined" style="font-size:0.85rem;"><?php echo $icon; ?></span>
                                        <?php echo $label; ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ══ FASE 3: BRED ══ -->
                        <div style="background:rgba(255,152,0,0.04); border:1px solid rgba(255,152,0,0.25); border-radius:12px; padding:20px;">
                            <h4 style="margin:0 0 6px; font-size:0.9rem; font-weight:800; color:#ff9800; display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:1px;">
                                <span class="material-symbols-outlined" style="font-size:1.2rem;">egg</span>
                                Fase 3 — Criado (Bred)
                            </h4>
                            <p style="margin:0 0 14px; font-size:0.76rem; color:var(--text-muted);">
                                La cría hereda los stats de los padres. Con impronta al 100% recibe +20% en todos los stats aplicables. Las mutaciones añaden +2 niveles wild en un stat y cambian el color.
                            </p>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div>
                                    <label style="display:flex; align-items:center; justify-content:space-between; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:600;">
                                        Impronta <span id="imp-val" style="color:#ff9800; font-weight:800;">0%</span>
                                    </label>
                                    <input type="range" id="imprint-slider" min="0" max="100" value="0" class="stat-slider-input" style="--thumb-color:#ff9800;" oninput="updateStats()">
                                    <p style="margin:5px 0 0; font-size:0.71rem; color:var(--text-muted);">100% = impronta perfecta. +20% en stats aplicables.</p>
                                </div>
                                <div style="background:rgba(255,255,255,0.03); border-radius:8px; padding:10px; border:1px solid rgba(255,255,255,0.06);">
                                    <p style="margin:0 0 6px; font-size:0.76rem; color:var(--text-muted); font-weight:600;">Stats afectados por impronta:</p>
                                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                        <?php foreach ($stat_defs as $key => [$label, $icon, $color, $iw, $base]):
                                            if ($base <= 0) continue;
                                            $afectado = !in_array($key, ['stamina', 'oxygen', 'speed']); ?>
                                        <span style="font-size:0.71rem; padding:2px 7px; border-radius:10px; background:<?php echo $afectado ? 'rgba(255,152,0,0.15)' : 'rgba(255,255,255,0.04)'; ?>; color:<?php echo $afectado ? '#ff9800' : 'var(--text-muted)'; ?>; border:1px solid <?php echo $afectado ? 'rgba(255,152,0,0.3)' : 'rgba(255,255,255,0.06)'; ?>;">
                                            <?php echo $label; ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div><!-- /tab-stats -->
        <?php endif; ?>


        <!-- ══════════════════════════════════════════
         TAB: HABILIDADES Y UTILIDAD
    ══════════════════════════════════════════ -->
        <div id="tab-habilidades" class="dino-tab-panel">

            <div style="margin-bottom:28px;">
                <h3 style="margin:0 0 6px; font-size:1.4rem;">Roles y Utilidad</h3>
                <p style="margin:0; color:var(--text-muted); font-size:0.9rem;">Descubre para qué sirve <strong style="color:var(--text-main);"><?php echo htmlspecialchars($dino['nombre']); ?></strong> en tu tribu.</p>
            </div>

            <!-- BADGES DE ROLES RÁPIDOS -->
            <?php
            $roles_activos = [];
            if ($features['es_tanque'])     $roles_activos[] = ['🛡️','Tanque','#3498db'];
            if ($features['es_buff'])       $roles_activos[] = ['📈','Soporte','#e74c3c'];
            if ($features['es_recolector']) $roles_activos[] = ['📦','Recolector','#2ecc71'];
            if ($features['es_montura'])    $roles_activos[] = ['🐴','Montura','#f39c12'];
            if ($features['es_volador'])    $roles_activos[] = ['🦅','Volador','#00bcd4'];
            if ($features['es_acuatico'])   $roles_activos[] = ['🐳','Acuático','#2196f3'];
            if ($features['es_subterraneo'])$roles_activos[] = ['🦇','Cueva','#795548'];
            ?>
            <?php if (!empty($roles_activos)): ?>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:30px;">
                <?php foreach ($roles_activos as [$emoji, $nombre, $color]): ?>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 16px; border-radius:30px; background:<?php echo $color; ?>18; border:1px solid <?php echo $color; ?>44;">
                    <span style="font-size:1.1rem;"><?php echo $emoji; ?></span>
                    <span style="font-size:0.85rem; font-weight:700; color:<?php echo $color; ?>;"><?php echo $nombre; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">

                <?php if ($features['es_tanque']): ?>
                <div class="rol-card" style="--rol-color:#3498db;">
                    <div class="rol-card-header">
                        <div class="rol-card-icon" style="background:rgba(52,152,219,0.15);">
                            <span class="material-symbols-outlined" style="color:#3498db;">shield</span>
                        </div>
                        <div>
                            <h4 class="rol-card-title">Tanque</h4>
                            <p class="rol-card-sub">Absorbe daño y protege a la tribu</p>
                        </div>
                    </div>
                    <?php if ($features['buff_armor'] > 0): ?>
                    <div class="rol-stat-bar">
                        <span class="rol-stat-label">Reducción de daño</span>
                        <div class="rol-bar-wrap">
                            <div class="rol-bar-fill" style="width:<?php echo min($features['buff_armor'],100); ?>%; background:#3498db;"></div>
                        </div>
                        <span class="rol-stat-val" style="color:#3498db;"><?php echo $features['buff_armor']; ?>%</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($features['tiene_formas'] && !empty($features['formas_descripcion'])): ?>
                    <p class="rol-card-desc"><?php echo nl2br(htmlspecialchars($features['formas_descripcion'])); ?></p>
                    <?php else: ?>
                    <p class="rol-card-desc">Puede absorber grandes cantidades de daño en combate.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($features['es_buff']): ?>
                <div class="rol-card" style="--rol-color:#e74c3c;">
                    <div class="rol-card-header">
                        <div class="rol-card-icon" style="background:rgba(231,76,60,0.15);">
                            <span class="material-symbols-outlined" style="color:#e74c3c;">volume_up</span>
                        </div>
                        <div>
                            <h4 class="rol-card-title">Soporte / Buff</h4>
                            <p class="rol-card-sub">Potencia a los aliados cercanos</p>
                        </div>
                    </div>
                    <?php if (!empty($features['buff_descripcion'])): ?>
                    <p class="rol-card-desc"><?php echo nl2br(htmlspecialchars($features['buff_descripcion'])); ?></p>
                    <?php endif; ?>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                        <?php if ($features['buff_damage'] > 0): ?>
                        <div class="rol-stat-bar" style="width:100%;">
                            <span class="rol-stat-label">⚔️ Daño</span>
                            <div class="rol-bar-wrap"><div class="rol-bar-fill" style="width:<?php echo min($features['buff_damage'],100); ?>%; background:#e74c3c;"></div></div>
                            <span class="rol-stat-val" style="color:#e74c3c;">+<?php echo $features['buff_damage']; ?>%</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($features['buff_armor'] > 0): ?>
                        <div class="rol-stat-bar" style="width:100%;">
                            <span class="rol-stat-label">🛡️ Armadura</span>
                            <div class="rol-bar-wrap"><div class="rol-bar-fill" style="width:<?php echo min($features['buff_armor'],100); ?>%; background:#3498db;"></div></div>
                            <span class="rol-stat-val" style="color:#3498db;">+<?php echo $features['buff_armor']; ?>%</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($features['buff_speed'] > 0): ?>
                        <div class="rol-stat-bar" style="width:100%;">
                            <span class="rol-stat-label">⚡ Velocidad</span>
                            <div class="rol-bar-wrap"><div class="rol-bar-fill" style="width:<?php echo min($features['buff_speed'],100); ?>%; background:#1abc9c;"></div></div>
                            <span class="rol-stat-val" style="color:#1abc9c;">+<?php echo $features['buff_speed']; ?>%</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($features['buff_otro'])): ?>
                        <span style="font-size:0.8rem; color:#f1c40f; background:rgba(241,196,15,0.1); border:1px solid rgba(241,196,15,0.3); padding:4px 10px; border-radius:20px;"><?php echo htmlspecialchars($features['buff_otro']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($features['es_recolector']): ?>
                <?php
                $recursos = [];
                if ($features['recolecta_carne'])  $recursos[] = ['🥩','Carne',   '#e74c3c'];
                if ($features['recolecta_pescado']) $recursos[] = ['🐟','Pescado', '#3498db'];
                if ($features['recolecta_madera'])  $recursos[] = ['🪵','Madera',  '#8b6914'];
                if ($features['recolecta_piedra'])  $recursos[] = ['🪨','Piedra',  '#95a5a6'];
                if ($features['recolecta_metal'])   $recursos[] = ['⛏️','Metal',   '#7f8c8d'];
                if ($features['recolecta_bayas'])   $recursos[] = ['🫐','Bayas',   '#9b59b6'];
                if ($features['recolecta_paja'])    $recursos[] = ['🌾','Paja',    '#f1c40f'];
                if ($features['recolecta_fibra'])   $recursos[] = ['🌿','Fibra',   '#2ecc71'];
                if ($features['recolecta_texugo'])  $recursos[] = ['🐾','Texugo',  '#e67e22'];
                ?>
                <div class="rol-card" style="--rol-color:#2ecc71;">
                    <div class="rol-card-header">
                        <div class="rol-card-icon" style="background:rgba(46,204,113,0.15);">
                            <span class="material-symbols-outlined" style="color:#2ecc71;">inventory_2</span>
                        </div>
                        <div>
                            <h4 class="rol-card-title">Recolección</h4>
                            <p class="rol-card-sub"><?php echo count($recursos); ?> recurso<?php echo count($recursos)!==1?'s':''; ?> disponible<?php echo count($recursos)!==1?'s':''; ?></p>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(90px,1fr)); gap:8px; margin-top:4px;">
                        <?php foreach ($recursos as [$emoji, $nombre, $color]): ?>
                        <div style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 6px; border-radius:10px; background:<?php echo $color; ?>12; border:1px solid <?php echo $color; ?>30;">
                            <span style="font-size:1.4rem;"><?php echo $emoji; ?></span>
                            <span style="font-size:0.72rem; font-weight:700; color:<?php echo $color; ?>;"><?php echo $nombre; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($features['es_volador'] || $features['es_acuatico'] || $features['es_subterraneo'] || $features['es_montura']): ?>
                <div class="rol-card" style="--rol-color:#00bcd4;">
                    <div class="rol-card-header">
                        <div class="rol-card-icon" style="background:rgba(0,188,212,0.15);">
                            <span class="material-symbols-outlined" style="color:#00bcd4;">category</span>
                        </div>
                        <div>
                            <h4 class="rol-card-title">Características</h4>
                            <p class="rol-card-sub">Habilidades especiales de movimiento</p>
                        </div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;">
                        <?php if ($features['es_volador']): ?>
                        <div style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:20px; background:rgba(0,188,212,0.1); border:1px solid rgba(0,188,212,0.3);">
                            <span>🦅</span><span style="font-size:0.82rem; font-weight:700; color:#00bcd4;">Volador</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($features['es_acuatico']): ?>
                        <div style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:20px; background:rgba(33,150,243,0.1); border:1px solid rgba(33,150,243,0.3);">
                            <span>🐳</span><span style="font-size:0.82rem; font-weight:700; color:#2196f3;">Acuático</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($features['es_subterraneo']): ?>
                        <div style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:20px; background:rgba(121,85,72,0.1); border:1px solid rgba(121,85,72,0.3);">
                            <span>🦇</span><span style="font-size:0.82rem; font-weight:700; color:#a1887f;">Subterráneo</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($features['es_montura']): ?>
                        <div style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:20px; background:rgba(156,39,176,0.1); border:1px solid rgba(156,39,176,0.3);">
                            <span>🐴</span><span style="font-size:0.82rem; font-weight:700; color:#ce93d8;">Montura</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($features['tiene_formas'] && !empty($features['formas_descripcion'])): ?>
                <div class="rol-card" style="--rol-color:#f39c12;">
                    <div class="rol-card-header">
                        <div class="rol-card-icon" style="background:rgba(243,156,18,0.15);">
                            <span class="material-symbols-outlined" style="color:#f39c12;">transform</span>
                        </div>
                        <div>
                            <h4 class="rol-card-title">Formas / Modos</h4>
                            <p class="rol-card-sub">Múltiples configuraciones</p>
                        </div>
                    </div>
                    <p class="rol-card-desc"><?php echo nl2br(htmlspecialchars($features['formas_descripcion'])); ?></p>
                </div>
                <?php endif; ?>

            </div><!-- /grid roles -->

            <?php if ($features['domable']): ?>
            <?php
            $nivel_max   = (int)($features['nivel_max_salvaje'] ?? 150);
            $metodo      = $features['metodo_domado'] ?? 'Knockout';
            $torpor_base = (int)($stats_data['torpidity'] ?? 500);
            $iw_torpor   = (float)($dino['iw_torpidity'] ?? 0.06);
            $t_incubacion = (int)($features['tiempo_incubacion'] ?? 0);
            $t_madurez    = (int)($features['tiempo_madurez']    ?? 0);

            // Formatear minutos a texto legible
            function formatMinutos($min) {
                if ($min <= 0) return null;
                if ($min < 60)   return $min . ' min';
                if ($min < 1440) return round($min / 60, 1) . ' h';
                return round($min / 1440, 1) . ' días';
            }
            $str_incubacion = formatMinutos($t_incubacion);
            $str_madurez    = formatMinutos($t_madurez);
            ?>
            <?php if ($str_incubacion || $str_madurez): ?>
            <!-- BLOQUE CRIANZA -->
            <div style="margin-top:20px; background:rgba(241,196,15,0.04); border:1px solid rgba(241,196,15,0.2); border-radius:14px; padding:22px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                    <div style="background:rgba(241,196,15,0.15); border-radius:10px; padding:10px; display:flex; flex-shrink:0;">
                        <span class="material-symbols-outlined" style="color:#f1c40f; font-size:1.3rem;">egg</span>
                    </div>
                    <h4 style="margin:0; font-size:1rem; font-weight:800; color:#fff;">Crianza</h4>
                </div>
                <div style="display:grid; grid-template-columns:repeat(<?php echo ($str_incubacion && $str_madurez) ? '3' : '2'; ?>, 1fr); gap:10px; margin-bottom:12px;">
                    <?php if ($str_incubacion): ?>
                    <div style="background:rgba(241,196,15,0.07); border:1px solid rgba(241,196,15,0.2); border-radius:10px; padding:14px; text-align:center;">
                        <span class="material-symbols-outlined" style="color:#f1c40f; font-size:1.4rem; display:block; margin-bottom:5px;">egg</span>
                        <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Incubación</div>
                        <div style="font-size:1.2rem; font-weight:900; color:#f1c40f;"><?php echo $str_incubacion; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($str_madurez): ?>
                    <div style="background:rgba(230,126,34,0.07); border:1px solid rgba(230,126,34,0.2); border-radius:10px; padding:14px; text-align:center;">
                        <span class="material-symbols-outlined" style="color:#e67e22; font-size:1.4rem; display:block; margin-bottom:5px;">child_care</span>
                        <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Madurez</div>
                        <div style="font-size:1.2rem; font-weight:900; color:#e67e22;"><?php echo $str_madurez; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($str_incubacion && $str_madurez): ?>
                    <div style="background:rgba(46,204,113,0.07); border:1px solid rgba(46,204,113,0.2); border-radius:10px; padding:14px; text-align:center;">
                        <span class="material-symbols-outlined" style="color:#2ecc71; font-size:1.4rem; display:block; margin-bottom:5px;">timer</span>
                        <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Total cría</div>
                        <div style="font-size:1.2rem; font-weight:900; color:#2ecc71;"><?php echo formatMinutos($t_incubacion + $t_madurez); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($str_incubacion): ?>
                <div style="background:rgba(241,196,15,0.05); border:1px solid rgba(241,196,15,0.15); border-radius:8px; padding:10px 14px; display:flex; gap:8px; align-items:flex-start;">
                    <span class="material-symbols-outlined" style="color:#f1c40f; font-size:0.95rem; flex-shrink:0; margin-top:1px;">lightbulb</span>
                    <p style="margin:0; font-size:0.73rem; color:var(--text-muted); line-height:1.6;">
                        Usa una <strong style="color:#f1c40f;">Incubadora de Huevos</strong> para control exacto de temperatura.
                        Sin ella: <strong style="color:#3498db;">ACs</strong> (~18°C c/u) para enfriar · <strong style="color:#e74c3c;">Antorchas</strong> (~3°C c/u) para calentar.
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- BLOQUE DOMESTICACIÓN -->
            <div style="margin-top:20px; background:rgba(155,89,182,0.04); border:1px solid rgba(155,89,182,0.2); border-radius:14px; padding:22px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                    <div style="background:rgba(155,89,182,0.15); border-radius:10px; padding:10px; display:flex; flex-shrink:0;">
                        <span class="material-symbols-outlined" style="color:#9b59b6; font-size:1.3rem;">pets</span>
                    </div>
                    <h4 style="margin:0; font-size:1rem; font-weight:800; color:#fff;">Domesticación</h4>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; padding:12px 16px; background:rgba(255,255,255,0.03); border-radius:10px; border:1px solid rgba(255,255,255,0.06);">
                    <?php if (!empty($metodo)): ?>
                    <span style="display:inline-flex; align-items:center; gap:5px; font-size:0.82rem; color:var(--text-muted);">
                        <span class="material-symbols-outlined" style="font-size:0.9rem; color:#9b59b6;">sports_kabaddi</span>
                        Método: <strong style="color:#9b59b6;"><?php echo htmlspecialchars($metodo); ?></strong>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($features['comida_favorita'])): ?>
                    <span style="color:rgba(255,255,255,0.2);">·</span>
                    <span style="display:inline-flex; align-items:center; gap:5px; font-size:0.82rem; color:var(--text-muted);">
                        <span class="material-symbols-outlined" style="font-size:0.9rem; color:#2ecc71;">restaurant</span>
                        Comida: <strong style="color:var(--text-main);"><?php echo htmlspecialchars($features['comida_favorita']); ?></strong>
                    </span>
                    <?php endif; ?>
                    <span style="color:rgba(255,255,255,0.2);">·</span>
                    <span style="display:inline-flex; align-items:center; gap:5px; font-size:0.82rem; color:var(--text-muted);">
                        <span class="material-symbols-outlined" style="font-size:0.9rem; color:var(--accent);">trending_up</span>
                        Nivel máx: <strong style="color:var(--accent);"><?php echo $nivel_max; ?></strong>
                    </span>
                </div>

                <!-- Calculadora de taming -->
                <div style="background:rgba(var(--accent-rgb),0.05); border:1px solid rgba(var(--accent-rgb),0.15); border-radius:10px; padding:16px; margin-top:16px;">
                    <p style="margin:0 0 12px; font-size:0.75rem; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; gap:5px;">
                        <span class="material-symbols-outlined" style="font-size:0.95rem;">calculate</span>
                        Calculadora de Taming
                    </p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div>
                            <label style="display:block; font-size:0.73rem; color:var(--text-muted); margin-bottom:5px; font-weight:600;">Nivel del dino</label>
                            <input type="number" id="taming-nivel" min="1" max="<?php echo $nivel_max; ?>" value="150"
                                style="width:100%; padding:8px 12px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); font-family:inherit; font-size:0.95rem; font-weight:700; outline:none; transition:border-color 0.2s;"
                                oninput="calcularTaming()" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.73rem; color:var(--text-muted); margin-bottom:5px; font-weight:600;">Comida</label>
                            <select id="taming-comida" onchange="calcularTaming()"
                                style="width:100%; padding:8px 12px; border-radius:8px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); font-family:inherit; font-size:0.85rem; outline:none; cursor:pointer; transition:border-color 0.2s;"
                                onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                                <option value="kibble_exc"    data-food="80"  data-interval="60">🥚 Kibble Excepcional</option>
                                <option value="kibble_sup"    data-food="50"  data-interval="60">🥚 Kibble Superior</option>
                                <option value="kibble_reg"    data-food="35"  data-interval="60">🥚 Kibble Regular</option>
                                <option value="kibble_sim"    data-food="25"  data-interval="60">🥚 Kibble Simple</option>
                                <option value="carne_prima"   data-food="20"  data-interval="30">🥩 Carne Prima</option>
                                <option value="carne_cruda"   data-food="10"  data-interval="30">🥩 Carne Cruda</option>
                                <option value="carne_cocinada"data-food="7"   data-interval="30">🍖 Carne Cocinada</option>
                                <option value="pescado_prima" data-food="15"  data-interval="30">🐟 Pescado Prima</option>
                                <option value="pescado_crudo" data-food="7.5" data-interval="30">🐟 Pescado Crudo</option>
                                <option value="mejobayas"     data-food="8"   data-interval="30">🫐 Mejobayas</option>
                                <option value="verduras"      data-food="5"   data-interval="30">🥕 Verduras</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; text-align:center;">
                        <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:10px;">
                            <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px;">Comida necesaria</div>
                            <div id="taming-cantidad" style="font-size:1.2rem; font-weight:900; color:var(--accent);">—</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:10px;">
                            <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px;">Tiempo estimado</div>
                            <div id="taming-tiempo" style="font-size:1.2rem; font-weight:900; color:var(--accent);">—</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:10px;">
                            <div style="font-size:0.68rem; color:var(--text-muted); margin-bottom:4px;">Nivel final</div>
                            <div id="taming-nivel-final" style="font-size:1.2rem; font-weight:900; color:#2ecc71;">—</div>
                        </div>
                    </div>
                    <p style="margin:8px 0 0; font-size:0.67rem; color:var(--text-muted);">* Estimación vanilla sin multiplicadores. Con eficiencia 100% (sin daño recibido).</p>
                </div>
            </div>
            <script>
            (function() {
                const TAMING_METODO  = <?php echo json_encode($metodo); ?>;
                const TORPOR_BASE    = <?php echo $torpor_base; ?>;
                const IW_TORPOR      = <?php echo $iw_torpor; ?>;
                window.calcularTaming = function() {
                    const nivel    = parseInt(document.getElementById('taming-nivel').value) || 1;
                    const sel      = document.getElementById('taming-comida');
                    const opt      = sel.options[sel.selectedIndex];
                    const foodVal  = parseFloat(opt.dataset.food)     || 10;
                    const interval = parseFloat(opt.dataset.interval) || 30;
                    const torpor   = TORPOR_BASE * (1 + nivel * IW_TORPOR);
                    let cantidad   = TAMING_METODO === 'Pasivo'
                        ? Math.ceil(nivel * 3 / foodVal)
                        : Math.ceil(torpor / (foodVal * 10));
                    cantidad = Math.max(1, cantidad);
                    const segs = cantidad * interval;
                    const tiempoStr = segs < 60 ? segs + 's' : segs < 3600 ? Math.round(segs/60) + ' min' : (segs/3600).toFixed(1) + ' h';
                    document.getElementById('taming-cantidad').textContent    = cantidad + ' uds';
                    document.getElementById('taming-tiempo').textContent      = tiempoStr;
                    document.getElementById('taming-nivel-final').textContent = 'Lv ' + Math.floor(nivel * 1.5);
                };
                window.calcularTaming();
            })();
            </script>
            <?php endif; ?>

            <?php if ($features['ayuda_cria']): ?>
            <div style="margin-top:20px; background:rgba(241,196,15,0.05); border:1px solid rgba(241,196,15,0.2); border-radius:14px; padding:20px; display:flex; gap:16px; align-items:flex-start;">
                <div style="background:rgba(241,196,15,0.15); border-radius:10px; padding:10px; flex-shrink:0; display:flex;">
                    <span class="material-symbols-outlined" style="color:#f1c40f; font-size:1.4rem;">egg</span>
                </div>
                <div>
                    <h4 style="margin:0 0 6px; font-size:1rem; color:#f1c40f; font-weight:800;">Ayuda a Cría</h4>
                    <p style="margin:0; font-size:0.88rem; color:var(--text-muted); line-height:1.6;">
                        <?php echo !empty($features['ayuda_cria_descripcion'])
                            ? nl2br(htmlspecialchars($features['ayuda_cria_descripcion']))
                            : 'Este dinosaurio ayuda en la cría de otros, aumentando la efectividad de la impronta.'; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /tab-habilidades -->

        <!-- ══════════════════════════════════════════
         TAB: COMENTARIOS
    ══════════════════════════════════════════ -->
        <div id="tab-comentarios" class="dino-tab-panel">

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form action="actions/procesar_comentario.php" method="POST" class="form-ark" style="margin-bottom: 30px;"
                    id="form-comentario">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                    <input type="hidden" name="respuesta_a" id="input_respuesta_a" value="">
                    <div style="display:none !important;"><input type="text" name="website_url" value=""></div>

                    <div id="indicador-respuesta"
                        style="display:none; background:rgba(var(--accent-rgb),0.1); padding:10px; border-radius:8px; margin-bottom:10px; border:1px dashed var(--accent);">
                        <span class="f-09">Respondiendo a <strong id="nick-respuesta">@usuario</strong></span>
                        <button type="button" onclick="cancelarRespuesta()"
                            style="background:none; border:none; color:#ff5555; cursor:pointer; float:right; font-weight:bold;">[X]
                            Cancelar</button>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom:10px;">
                        <?php
                        $foto = $_SESSION['foto_perfil'] ?? 'default.png';
                        $src_foto = (strpos($foto, 'http') === 0) ? $foto : "assets/img/perfil/" . $foto;
                        ?>
                        <img src="<?php echo htmlspecialchars($src_foto); ?>" alt="Mi Perfil" class="avatar-comentario"
                            style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0;"
                            onerror="this.src='assets/img/perfil/default.png'">
                        <textarea name="texto" required
                            placeholder="Añade tu experiencia con <?php echo htmlspecialchars($dino['nombre']); ?>: estrategias de taming, uso en PvP/PvE..."
                            rows="4" style="width:100%;border-radius:var(--radius);"></textarea>
                    </div>
                    <button type="submit" class="boton-insertar">Publicar comentario</button>
                </form>
            <?php else: ?>
                <div
                    style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:var(--radius); padding:25px; text-align:center; margin-bottom:25px;">
                    <span class="material-symbols-outlined"
                        style="font-size:2rem; color:var(--text-muted); display:block; margin-bottom:10px;">lock</span>
                    <p style="margin:0; color:var(--text-muted);">Debes <a href="login.php"
                            style="color:var(--accent);">iniciar sesión</a> para dejar un comentario.</p>
                </div>
            <?php endif; ?>

            <div class="comentarios-lista">
                <?php if (count($comentarios) > 0): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div
                            class="comentario <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'comentario-admin' : ''; ?>">
                            <div class="comentario-header">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php
                                    $foto_c = $c['foto_perfil'] ?? 'default.png';
                                    $src_c = (strpos($foto_c, 'http') === 0) ? $foto_c : "assets/img/perfil/" . $foto_c;
                                    $can_moderate = isset($_SESSION['p_moderar']) && $_SESSION['p_moderar'] == 1 && $_SESSION['usuario_id'] != $c['usuario_id'] && $c['rol'] !== 'superadmin';
                                    ?>
                                    <?php if ($can_moderate): ?>
                                        <a href="admin/moderar_usuario.php?id=<?php echo $c['usuario_id']; ?>"
                                            style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
                                        <?php endif; ?>
                                        <img src="<?php echo htmlspecialchars($src_c); ?>" alt="Avatar"
                                            class="avatar-comentario" onerror="this.src='assets/img/perfil/default.png'">
                                        <strong
                                            class="comentario-nick <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? 'nick-admin' : ''; ?>">
                                            <?php echo htmlspecialchars($c['nick']); ?>
                                            <?php echo ($c['rol'] === 'admin' || $c['rol'] === 'superadmin') ? '(Admin)' : ''; ?>
                                        </strong>
                                        <?php if ($can_moderate): ?></a><?php endif; ?>
                                </div>
                                <div class="d-flex align-center gap-10">
                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                                        <button type="button" class="btn-nav f-08"
                                            style="padding:4px 10px; border-color:var(--accent); color:var(--accent);"
                                            onclick="prepararRespuesta(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nick'])); ?>')">Contestar</button>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['usuario_id']) && (($_SESSION['is_admin'] ?? false) === true || $_SESSION['usuario_id'] == $c['usuario_id'])): ?>
                                        <form action="actions/borrar_comentario.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="comentario_id" value="<?php echo $c['id']; ?>">
                                            <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                            <button type="submit" onclick="return confirm('¿Borrar este comentario?');"
                                                class="btn-borrar-comentario">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                        </div>

                        <?php if (isset($respuestas[$c['id']])): ?>
                            <?php foreach ($respuestas[$c['id']] as $r): ?>
                                <div class="comentario comentario-admin comentario-respuesta">
                                    <div class="comentario-header">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <?php
                                            $foto_r = $r['foto_perfil'] ?? 'default.png';
                                            $src_r = (strpos($foto_r, 'http') === 0) ? $foto_r : "assets/img/perfil/" . $foto_r;
                                            ?>
                                            <img src="<?php echo htmlspecialchars($src_r); ?>" alt="Avatar" class="avatar-comentario"
                                                onerror="this.src='assets/img/perfil/default.png'">
                                            <strong class="comentario-nick nick-admin"><?php echo htmlspecialchars($r['nick']); ?>
                                                (Admin)</strong>
                                            <span class="f-08 text-muted">ha respondido</span>
                                        </div>
                                        <?php if (isset($_SESSION['usuario_id']) && (($_SESSION['is_admin'] ?? false) === true || $_SESSION['usuario_id'] == $r['usuario_id'])): ?>
                                            <form action="actions/borrar_comentario.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="comentario_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="dino_id" value="<?php echo $dino['id']; ?>">
                                                <button type="submit" onclick="return confirm('¿Borrar?');"
                                                    class="btn-borrar-comentario">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($r['texto'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($total_paginas > 1): ?>
                        <div class="paginacion-container">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $pagina_actual - 1; ?>#tab-comentarios"
                                    class="btn-pag prev">‹</a>
                            <?php endif; ?>
                            <?php 
                            // Mostrar máximo 5 páginas con la actual en el centro
                            $inicio = max(1, $pagina_actual - 2);
                            $fin = min($total_paginas, $pagina_actual + 2);
                            if ($fin - $inicio < 4) {
                                if ($inicio == 1) $fin = min($total_paginas, 5);
                                else $inicio = max(1, $fin - 4);
                            }
                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $i; ?>#tab-comentarios"
                                    class="btn-pag <?php echo ($i === $pagina_actual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="detalle.php?id=<?php echo $id; ?>&p=<?php echo $pagina_actual + 1; ?>#tab-comentarios"
                                    class="btn-pag next">›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="sin-datos" style="text-align:center; padding:40px 0;">No hay comentarios todavía. ¡Sé el
                        primero en aportar info sobre <?php echo htmlspecialchars($dino['nombre']); ?>!</p>
                <?php endif; ?>
            </div>

        </div><!-- /tab-comentarios -->

        <?php if ($tiene_stats): ?>
        <!-- ══════════════════════════════════════════
             TAB: COMPARADOR
        ══════════════════════════════════════════ -->
        <div id="tab-comparar" class="dino-tab-panel">
            <div style="margin-bottom:24px;">
                <h3 style="margin:0 0 6px; font-size:1.4rem;">Comparar Criaturas</h3>
                <p style="margin:0; color:var(--text-muted); font-size:0.9rem;">Compara los stats de <strong style="color:var(--text-main);"><?php echo htmlspecialchars($dino['nombre']); ?></strong> con otra criatura de la wiki.</p>
            </div>

            <!-- Buscador de criatura a comparar -->
            <div style="background:rgba(var(--accent-rgb),0.05); border:1px solid rgba(var(--accent-rgb),0.2); border-radius:12px; padding:20px; margin-bottom:28px;">
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px; position:relative;">
                        <span class="material-symbols-outlined" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1.1rem;pointer-events:none;">search</span>
                        <input type="text" id="comparar-buscar" placeholder="Escribe el nombre de otra criatura..."
                            style="width:100%; padding:11px 14px 11px 38px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); border-radius:8px; font-family:inherit; font-size:0.95rem; outline:none; transition:border-color 0.2s;"
                            oninput="buscarParaComparar(this.value)"
                            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                        <div id="comparar-sugerencias" style="display:none; position:absolute; top:100%; left:0; right:0; background:var(--bg-card); border:1px solid var(--border-color); border-radius:8px; margin-top:4px; z-index:100; max-height:220px; overflow-y:auto; box-shadow:0 8px 24px rgba(0,0,0,0.4);"></div>
                    </div>
                    <button onclick="limpiarComparador()" style="background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-muted); border-radius:8px; padding:11px 16px; cursor:pointer; font-family:inherit; font-size:0.85rem; transition:0.2s; white-space:nowrap; display:flex; align-items:center; gap:5px;"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                        <span class="material-symbols-outlined" style="font-size:1rem;">refresh</span> Limpiar
                    </button>
                </div>
                <div id="comparar-seleccionado" style="display:none; margin-top:12px; padding:10px 14px; background:rgba(var(--accent-rgb),0.08); border-radius:8px; border:1px solid rgba(var(--accent-rgb),0.2); font-size:0.88rem; color:var(--accent); font-weight:700; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:1rem;">check_circle</span>
                    <span id="comparar-nombre-sel">—</span>
                </div>
            </div>

            <!-- Layout 3 columnas: stats A | radar | stats B -->
            <?php
            $stat_compare = [
                'health'    => ['Vida',      'favorite',   '#e74c3c'],
                'stamina'   => ['Energía',   'bolt',       '#f39c12'],
                'oxygen'    => ['Oxígeno',   'water_drop', '#3498db'],
                'food'      => ['Comida',    'restaurant', '#2ecc71'],
                'weight'    => ['Peso',      'weight',     '#9b59b6'],
                'melee'     => ['Melée',     'swords',     '#e67e22'],
                'torpidity' => ['Torpor',    'bedtime',    '#95a5a6'],
            ];
            ?>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; align-items:start;">

                <!-- COLUMNA IZQUIERDA: stats dino actual -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:18px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="width:10px;height:10px;border-radius:50%;background:var(--accent);display:inline-block;flex-shrink:0;"></span>
                        <span style="font-size:0.85rem; font-weight:800; color:var(--accent); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($dino['nombre']); ?></span>
                    </div>
                    <?php foreach ($stat_compare as $key => [$label, $icon, $color]):
                        $val_a = (float)($stats_data[$key] ?? 0);
                        if ($val_a <= 0) continue; ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span class="material-symbols-outlined" style="font-size:0.85rem; color:<?php echo $color; ?>;"><?php echo $icon; ?></span>
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $label; ?></span>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.88rem; font-weight:800; color:var(--text-main);"><?php echo number_format($val_a); ?></div>
                            <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:2px; margin-top:3px; width:80px;">
                                <div style="height:100%; background:<?php echo $color; ?>; border-radius:2px; width:100%;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- COLUMNA CENTRAL: radar -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:18px; position:sticky; top:80px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <h4 style="margin:0; color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Radar Comparativo</h4>
                        <button onclick="abrirRadarModal('radarComparar')" title="Ampliar gráfico"
                            style="background:rgba(var(--accent-rgb),0.1); border:1px solid rgba(var(--accent-rgb),0.3); color:var(--accent); border-radius:6px; padding:5px 8px; cursor:pointer; display:flex; align-items:center; transition:0.2s;"
                            onmouseover="this.style.background='rgba(var(--accent-rgb),0.2)'" onmouseout="this.style.background='rgba(var(--accent-rgb),0.1)'">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;">zoom_in</span>
                        </button>
                    </div>
                    <canvas id="radarComparar" style="max-height:280px;"></canvas>
                    <div style="display:flex; gap:12px; justify-content:center; margin-top:12px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:5px; font-size:0.75rem; color:var(--text-muted);">
                            <span style="width:10px;height:3px;background:var(--accent);display:inline-block;border-radius:2px;"></span>
                            <?php echo htmlspecialchars($dino['nombre']); ?>
                        </div>
                        <div id="comparar-leyenda-b" style="display:flex; align-items:center; gap:5px; font-size:0.75rem; color:var(--text-muted);">
                            <span style="width:10px;height:3px;background:#ff9800;display:inline-block;border-radius:2px; border-top:2px dashed #ff9800; height:0;"></span>
                            <span id="comparar-leyenda-nombre">—</span>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: stats dino comparado -->
                <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:18px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ff9800;display:inline-block;flex-shrink:0;"></span>
                        <span id="col-b-nombre" style="font-size:0.85rem; font-weight:800; color:#ff9800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Selecciona una criatura</span>
                    </div>
                    <?php foreach ($stat_compare as $key => [$label, $icon, $color]):
                        $val_a = (float)($stats_data[$key] ?? 0);
                        if ($val_a <= 0) continue; ?>
                    <div class="comparar-stat-row" data-stat="<?php echo $key; ?>" data-val-a="<?php echo $val_a; ?>" style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span class="material-symbols-outlined" style="font-size:0.85rem; color:<?php echo $color; ?>;"><?php echo $icon; ?></span>
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $label; ?></span>
                        </div>
                        <div style="text-align:right;">
                            <div style="display:flex; align-items:center; gap:6px; justify-content:flex-end;">
                                <span class="comparar-diff" style="font-size:0.72rem; font-weight:800;">—</span>
                                <span class="comparar-val-b" style="font-size:0.88rem; font-weight:800; color:var(--text-muted);">—</span>
                            </div>
                            <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:2px; margin-top:3px; width:80px;">
                                <div class="comparar-bar-b" style="height:100%; background:rgba(255,152,0,0.8); border-radius:2px; width:0%; transition:width 0.5s;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <p id="comparar-hint" style="margin:10px 0 0; font-size:0.75rem; color:var(--text-muted); text-align:center; font-style:italic;">Busca arriba para comparar</p>
                </div>

            </div>
        </div><!-- /tab-comparar -->
        <?php endif; ?>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && ($_SESSION['p_insertar'] ?? 0) == 1): ?>
            <div style="margin-top:40px; text-align:center; border-top:1px solid #333; padding-top:25px;">
                <form action="actions/admin/procesar_eliminar.php" method="POST" style="display:inline;"
                    onsubmit="return confirm('¿Extinguir a <?php echo htmlspecialchars($dino['nombre']); ?>? Esta acción es irreversible.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $dino['id']; ?>">
                    <button type="submit" class="boton-eliminar" style="border:none; cursor:pointer;">Eliminar Criatura</button>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <script>
        // ── TABS ──────────────────────────────────────────
        function switchDinoTab(name, btn) {
            document.querySelectorAll('.dino-tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.dino-tab-btn').forEach(b => b.classList.remove('active'));
            const panel = document.getElementById('tab-' + name);
            if (panel) panel.classList.add('active');
            btn.classList.add('active');

            // Init chart on first visit to stats tab
            if (name === 'stats' && !window.radarInitialized) initRadar();
        }

        // ── COMENTARIOS ───────────────────────────────────
        function prepararRespuesta(id, nick) {
            document.getElementById('input_respuesta_a').value = id;
            document.getElementById('nick-respuesta').innerText = '@' + nick;
            document.getElementById('indicador-respuesta').style.display = 'block';
            // Ir a la tab de comentarios
            document.querySelectorAll('.dino-tab-btn').forEach(b => { if (b.textContent.includes('Foro')) { switchDinoTab('comentarios', b); } });
            setTimeout(() => { document.getElementById('form-comentario').scrollIntoView({ behavior: 'smooth' }); document.querySelector('#form-comentario textarea').focus(); }, 200);
        }
        function cancelarRespuesta() {
            document.getElementById('input_respuesta_a').value = '';
            document.getElementById('indicador-respuesta').style.display = 'none';
        }

        <?php if ($tiene_stats): ?>
            // ── CALCULADORA WILD ──────────────────────────────
            const BASE_STATS = {
                health: <?php echo $stats_data['health']; ?>,
                stamina: <?php echo $stats_data['stamina']; ?>,
                oxygen: <?php echo $stats_data['oxygen']; ?>,
                food: <?php echo $stats_data['food']; ?>,
                weight: <?php echo $stats_data['weight']; ?>,
                melee: <?php echo $stats_data['melee']; ?>,
                speed: <?php echo $stats_data['speed']; ?>,
                torpidity: <?php echo $stats_data['torpidity']; ?>,
            };
            // Multiplicadores Iw reales de la BD (incremento por nivel salvaje)
            const IW = {
                health:   <?php echo (float)($dino['iw_health']    ?? 0.2);  ?>,
                stamina:  <?php echo (float)($dino['iw_stamina']   ?? 0.1);  ?>,
                oxygen:   <?php echo (float)($dino['iw_oxygen']    ?? 0.1);  ?>,
                food:     <?php echo (float)($dino['iw_food']      ?? 0.15); ?>,
                weight:   <?php echo (float)($dino['iw_weight']    ?? 0.02); ?>,
                melee:    <?php echo (float)($dino['iw_melee']     ?? 0.05); ?>,
                speed:    <?php echo (float)($dino['iw_speed']     ?? 0.0);  ?>,
                torpidity:<?php echo (float)($dino['iw_torpidity'] ?? 0.06); ?>,
            };
            const STAT_LABELS = ['Vida', 'Energía', 'Oxígeno', 'Comida', 'Peso', 'Melée', 'Velocidad', 'Torpor'];
            const STAT_KEYS = ['health', 'stamina', 'oxygen', 'food', 'weight', 'melee', 'speed', 'torpidity'];
            const STAT_COLORS = ['#e74c3c', '#f39c12', '#3498db', '#2ecc71', '#9b59b6', '#e67e22', '#1abc9c', '#95a5a6'];

            let radarChart = null;
            window.radarChart = null;
            window.radarInitialized = false;

            function calcV(key, lw) {
                const B = BASE_STATS[key] || 0;
                const impSlider = document.getElementById('imprint-slider');
                const tamSlider = document.getElementById('taming-slider');
                const tamedLvSlider = document.getElementById('tamed-levels-slider');

                const impronta   = impSlider    ? parseInt(impSlider.value)    / 100 : 0;
                const tamingEff  = tamSlider    ? parseInt(tamSlider.value)    / 100 : 1;
                const tamedLevels= tamedLvSlider? parseInt(tamedLvSlider.value)      : 0;

                const mutInput = document.getElementById('mut-' + key);
                const muts = mutInput ? parseInt(mutInput.value) || 0 : 0;

                // Stat seleccionado para los niveles domesticados
                const selectedTamedStat = document.querySelector('.tamed-stat-btn.active')?.dataset.stat || 'health';

                // ── FASE 1: WILD ──────────────────────────────────────────
                // V_wild = B × (1 + (Lw + muts×2) × Iw)
                const totalLw = lw + (muts * 2);
                let v = B * (1 + totalLw * IW[key]);

                // ── FASE 2: TAMED ─────────────────────────────────────────
                // Bonus de taming: afecta a todos los stats excepto torpor
                // Ta (bonus aditivo) = B × 0.5 × tamingEff  (aproximación vanilla genérica)
                if (key !== 'torpidity') {
                    const Ta = B * 0.5 * tamingEff;
                    v = v + Ta;
                }

                // Niveles domesticados: solo al stat seleccionado
                // Cada nivel domesticado sube el stat un Id (mismo Iw que wild en vanilla)
                if (key === selectedTamedStat && tamedLevels > 0) {
                    v = v * (1 + tamedLevels * IW[key]);
                }

                // ── FASE 3: BRED ──────────────────────────────────────────
                // Impronta: +20% en stats aplicables (no stamina, oxygen, speed)
                if (impronta > 0 && key !== 'stamina' && key !== 'oxygen' && key !== 'speed' && key !== 'torpidity') {
                    v = v * (1 + impronta * 0.2);
                }

                return v;
            }

            let selectedTamedStatKey = 'health';
            let selectedTamedStatColor = '#e74c3c';

            function selectTamedStat(btn, key, color) {
                document.querySelectorAll('.tamed-stat-btn').forEach(b => {
                    b.style.background = 'rgba(255,255,255,0.05)';
                    b.style.color = 'var(--text-muted)';
                    b.style.borderColor = 'var(--border-color)';
                    b.classList.remove('active');
                });
                btn.style.background = color;
                btn.style.color = '#fff';
                btn.style.borderColor = color;
                btn.classList.add('active');
                selectedTamedStatKey = key;
                selectedTamedStatColor = color;
                updateStats();
            }

            function updateStats() {
                let totalLevels = 0;
                const impSlider      = document.getElementById('imprint-slider');
                const tamSlider      = document.getElementById('taming-slider');
                const tamedLvSlider  = document.getElementById('tamed-levels-slider');

                if (document.getElementById('imp-val')         && impSlider)     document.getElementById('imp-val').textContent         = impSlider.value + '%';
                if (document.getElementById('tej-val')         && tamSlider)     document.getElementById('tej-val').textContent         = tamSlider.value + '%';
                if (document.getElementById('tamed-levels-val')&& tamedLvSlider) document.getElementById('tamed-levels-val').textContent = tamedLvSlider.value;

                // Actualizar visual del slider de tamed-levels
                if (tamedLvSlider) {
                    const accent = getAccentColor();
                    const pct = (tamedLvSlider.value / 73 * 100);
                    tamedLvSlider.style.background = `linear-gradient(to right, ${accent} 0%, ${accent} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`;
                }

                const newData = STAT_KEYS.map(key => {
                    const slider = document.getElementById('slider-' + key);
                    if (!slider || slider.disabled) return calcV(key, 0);
                    const lw = parseInt(slider.value);
                    totalLevels += lw;
                    const v = calcV(key, lw);
                    const levelEl = document.getElementById('level-' + key);
                    const valEl   = document.getElementById('val-'   + key);
                    if (levelEl) levelEl.textContent = 'Lv ' + lw;
                    if (valEl)   valEl.textContent   = v >= 10 ? Math.round(v).toLocaleString('es-ES') : v.toFixed(2);
                    return v;
                });

                document.getElementById('nivel-total').textContent = totalLevels;

                // Etiqueta del radar según fases activas
                const imp    = impSlider    ? parseInt(impSlider.value)    : 0;
                const tam    = tamSlider    ? parseInt(tamSlider.value)    : 100;
                const tamedL = tamedLvSlider? parseInt(tamedLvSlider.value): 0;
                let label = 'Nivel Salvaje (Wild)';
                if (imp > 0)              label = 'Criado · Impronta ' + imp + '%';
                else if (tamedL > 0)      label = 'Domesticado · ' + tamedL + ' niveles extra';
                else if (tam < 100)       label = 'Domesticado · Eficiencia ' + tam + '%';
                const labelEl = document.getElementById('radar-mode-label');
                if (labelEl) labelEl.textContent = label;

                if (radarChart) {
                    radarChart.data.datasets[0].data = newData;
                    radarChart.update('none');
                }
            }

            function getAccentColor() {
                return getComputedStyle(document.body).getPropertyValue('--accent').trim() || '#00ffcc';
            }

            function getAccentRgb() {
                return getComputedStyle(document.body).getPropertyValue('--accent-rgb').trim() || '0,255,204';
            }

            function initRadar() {
                if (window.radarInitialized) return;
                const ctx = document.getElementById('statsRadar').getContext('2d');
                const accentRgb = getAccentRgb();
                radarChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: STAT_LABELS,
                        datasets: [{
                            label: '<?php echo addslashes($dino["nombre"]); ?>',
                            data: STAT_KEYS.map(k => BASE_STATS[k]),
                            backgroundColor: `rgba(${accentRgb},0.10)`,
                            borderColor: `rgba(${accentRgb},0.85)`,
                            pointBackgroundColor: STAT_COLORS,
                            pointBorderColor: '#fff',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        animation: { duration: 150 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ' ' + Math.round(ctx.raw).toLocaleString('es-ES')
                                }
                            }
                        },
                        scales: {
                            r: {
                                angleLines: { color: 'rgba(255,255,255,0.08)' },
                                grid: { color: 'rgba(255,255,255,0.08)' },
                                pointLabels: { color: '#aaa', font: { size: 11, family: 'inherit' } },
                                ticks: { display: false, backdropColor: 'transparent' },
                                suggestedMin: 0,
                            }
                        }
                    }
                });
                window.radarInitialized = true;
                window.radarChart = radarChart;

                // Estilo dinámico para los thumbs de los sliders de stats
                document.querySelectorAll('.stat-slider-input').forEach(s => {
                    if (s.id === 'imprint-slider' || s.id === 'taming-slider') return;
                    const color = s.style.getPropertyValue('--thumb-color') || getAccentColor();
                    const max = parseInt(s.max) || 150;
                    s.style.setProperty('background', `linear-gradient(to right, ${color} 0%, ${color} ${(s.value / max * 100)}%, rgba(255,255,255,0.1) ${(s.value / max * 100)}%, rgba(255,255,255,0.1) 100%)`);
                    s.addEventListener('input', function () {
                        const pct = (this.value / max * 100);
                        this.style.setProperty('background', `linear-gradient(to right, ${color} 0%, ${color} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`);
                    });
                });

                // Inicializar sliders de Impronta y Taming con el color del acento
                const accent = getAccentColor();
                const impSlider = document.getElementById('imprint-slider');
                const tamSlider = document.getElementById('taming-slider');
                if (impSlider) {
                    impSlider.style.setProperty('--thumb-color', accent);
                    const pct = (impSlider.value / 100 * 100);
                    impSlider.style.background = `linear-gradient(to right, ${accent} 0%, ${accent} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`;
                    impSlider.addEventListener('input', function() {
                        const c = getAccentColor();
                        const p = (this.value / 100 * 100);
                        this.style.background = `linear-gradient(to right, ${c} 0%, ${c} ${p}%, rgba(255,255,255,0.1) ${p}%, rgba(255,255,255,0.1) 100%)`;
                    });
                }
                if (tamSlider) {
                    tamSlider.style.setProperty('--thumb-color', accent);
                    const pct = (tamSlider.value / 100 * 100);
                    tamSlider.style.background = `linear-gradient(to right, ${accent} 0%, ${accent} ${pct}%, rgba(255,255,255,0.1) ${pct}%, rgba(255,255,255,0.1) 100%)`;
                    tamSlider.addEventListener('input', function() {
                        const c = getAccentColor();
                        const p = (this.value / 100 * 100);
                        this.style.background = `linear-gradient(to right, ${c} 0%, ${c} ${p}%, rgba(255,255,255,0.1) ${p}%, rgba(255,255,255,0.1) 100%)`;
                    });
                }

                if (!document.getElementById('slider-thumb-style')) {
                    const style = document.createElement('style');
                    style.id = 'slider-thumb-style';
                    style.textContent = STAT_COLORS.map((c, i) =>
                        `#slider-${STAT_KEYS[i]}::-webkit-slider-thumb { background: ${c}; } #slider-${STAT_KEYS[i]}::-moz-range-thumb { background: ${c}; border: 2px solid #fff; width:16px; height:16px; border-radius:50%; cursor:pointer; }`
                    ).join('\n');
                    document.head.appendChild(style);
                }
            }

            function rollWildStats() {
                const targetLevel = parseInt(document.getElementById('gen-level').value);
                if(isNaN(targetLevel) || targetLevel < 1) return;
                
                // (Lv 150 -> 149 puntos base salvajes a repartir).
                const pointsToSpend = targetLevel - 1;
                
                const activeKeys = STAT_KEYS.filter(k => {
                    const slider = document.getElementById('slider-' + k);
                    return slider && !slider.disabled;
                });
                
                let rolls = {};
                activeKeys.forEach(k => rolls[k] = 0);
                
                // Distribución pseudoaleatoria al estilo del juego vanilla
                for (let i = 0; i < pointsToSpend; i++) {
                    const randomKey = activeKeys[Math.floor(Math.random() * activeKeys.length)];
                    rolls[randomKey]++;
                }
                
                // Aplicar a los selectores y forzar visual
                activeKeys.forEach(k => {
                    const slider = document.getElementById('slider-' + k);
                    if(slider) {
                        slider.value = rolls[k];
                        slider.dispatchEvent(new Event('input')); // Dispara la actualización del fondo del input
                    }
                });
                
                updateStats(); // Actualización final del radar
            }

            function resetSliders() {
                document.querySelectorAll('.stat-slider-input').forEach(s => {
                    if (s.id === 'taming-slider') { s.value = 100; }
                    else if (s.id === 'imprint-slider' || s.id === 'tamed-levels-slider') { s.value = 0; }
                    else { s.value = 0; }
                    s.dispatchEvent(new Event('input'));
                });
                // Resetear selector de stat domesticado a health
                const healthBtn = document.querySelector('.tamed-stat-btn[data-stat="health"]');
                if (healthBtn) selectTamedStat(healthBtn, 'health', '#e74c3c');
                updateStats();
            }

            // Si el hash apunta a stats, abrir esa tab
            if (window.location.hash === '#stats') {
                const btn = [...document.querySelectorAll('.dino-tab-btn')].find(b => b.textContent.includes('Stats'));
                if (btn) switchDinoTab('stats', btn);
            }
        <?php endif; ?>

        // Ir a comentarios si hay hash
        if (window.location.hash === '#comentarios') {
            const btn = [...document.querySelectorAll('.dino-tab-btn')].find(b => b.textContent.includes('Foro'));
            if (btn) switchDinoTab('comentarios', btn);
        }

        <?php if ($tiene_stats): ?>
        // ── COMPARADOR ────────────────────────────────────
        let radarComparar = null;
        let comparTimer   = null;

        const BASE_A = {
            health:   <?php echo (float)($stats_data['health']   ?? 0); ?>,
            stamina:  <?php echo (float)($stats_data['stamina']  ?? 0); ?>,
            oxygen:   <?php echo (float)($stats_data['oxygen']   ?? 0); ?>,
            food:     <?php echo (float)($stats_data['food']     ?? 0); ?>,
            weight:   <?php echo (float)($stats_data['weight']   ?? 0); ?>,
            melee:    <?php echo (float)($stats_data['melee']    ?? 0); ?>,
            torpidity:<?php echo (float)($stats_data['torpidity']?? 0); ?>,
        };
        const STAT_KEYS_C  = ['health','stamina','oxygen','food','weight','melee','torpidity'];
        const STAT_LABELS_C= ['Vida','Energía','Oxígeno','Comida','Peso','Melée','Torpor'];

        function initRadarComparar() {
            if (radarComparar) return;
            const ctx = document.getElementById('radarComparar').getContext('2d');
            const accentRgb = getComputedStyle(document.body).getPropertyValue('--accent-rgb').trim() || '0,255,204';
            radarComparar = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: STAT_LABELS_C,
                    datasets: [
                        {
                            label: '<?php echo addslashes($dino["nombre"]); ?>',
                            data: STAT_KEYS_C.map(k => BASE_A[k]),
                            backgroundColor: `rgba(${accentRgb},0.15)`,
                            borderColor: `rgba(${accentRgb},1)`,
                            pointBackgroundColor: `rgba(${accentRgb},1)`,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderWidth: 3,
                        },
                        {
                            label: 'Comparar',
                            data: STAT_KEYS_C.map(() => 0),
                            backgroundColor: 'rgba(255,152,0,0.15)',
                            borderColor: 'rgba(255,152,0,1)',
                            pointBackgroundColor: 'rgba(255,152,0,1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderWidth: 3,
                            borderDash: [5, 3],
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    animation: { duration: 400 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${Math.round(ctx.raw).toLocaleString('es-ES')}`
                            }
                        }
                    },
                    scales: {
                        r: {
                            angleLines: { color: 'rgba(255,255,255,0.1)' },
                            grid: { color: 'rgba(255,255,255,0.1)' },
                            pointLabels: { color: '#ccc', font: { size: 11, family: 'inherit', weight: '600' } },
                            ticks: { display: false, backdropColor: 'transparent' },
                            suggestedMin: 0,
                        }
                    }
                }
            });
        }

        function buscarParaComparar(q) {
            clearTimeout(comparTimer);
            const sug = document.getElementById('comparar-sugerencias');
            if (q.length < 2) { sug.style.display = 'none'; return; }
            comparTimer = setTimeout(() => {
                fetch(`actions/buscar_dinos.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(dinos => {
                        sug.innerHTML = '';
                        if (!dinos.length) {
                            sug.innerHTML = '<div style="padding:12px 16px;color:var(--text-muted);font-size:0.85rem;">Sin resultados</div>';
                        } else {
                            dinos.forEach(d => {
                                // No mostrar el dino actual
                                if (d.id == <?php echo (int)$dino['id']; ?>) return;
                                const item = document.createElement('div');
                                item.style.cssText = 'padding:10px 16px;cursor:pointer;font-size:0.88rem;color:var(--text-main);transition:background 0.15s;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;gap:10px;';
                                item.innerHTML = `<span style="font-weight:700;">${d.nombre}</span><span style="font-size:0.75rem;color:var(--text-muted);">${d.dieta || ''}</span>`;
                                item.onmouseover = () => item.style.background = 'rgba(var(--accent-rgb),0.08)';
                                item.onmouseout  = () => item.style.background = '';
                                item.onclick = () => {
                                    // Usar los stats directamente del JSON, sin fetch adicional
                                    const statsB = {
                                        health:   parseFloat(d.stat_health)    || 0,
                                        stamina:  parseFloat(d.stat_stamina)   || 0,
                                        oxygen:   parseFloat(d.stat_oxygen)    || 0,
                                        food:     parseFloat(d.stat_food)      || 0,
                                        weight:   parseFloat(d.stat_weight)    || 0,
                                        melee:    parseFloat(d.stat_melee)     || 0,
                                        torpidity:parseFloat(d.stat_torpidity) || 0,
                                    };
                                    document.getElementById('comparar-buscar').value = d.nombre;
                                    sug.style.display = 'none';
                                    const sel = document.getElementById('comparar-seleccionado');
                                    document.getElementById('comparar-nombre-sel').textContent = d.nombre;
                                    sel.style.display = 'flex';
                                    actualizarComparador(statsB, d.nombre);
                                };
                                sug.appendChild(item);
                            });
                            if (!sug.children.length) {
                                sug.innerHTML = '<div style="padding:12px 16px;color:var(--text-muted);font-size:0.85rem;">Sin resultados</div>';
                            }
                        }
                        sug.style.display = 'block';
                    })
                    .catch(() => { sug.style.display = 'none'; });
            }, 250);
        }

        function actualizarComparador(statsB, nombre) {
            if (!radarComparar) initRadarComparar();

            // Actualizar radar
            radarComparar.data.datasets[1].data = STAT_KEYS_C.map(k => statsB[k] || 0);
            radarComparar.data.datasets[1].label = nombre;
            radarComparar.update();

            // Actualizar nombre columna derecha
            document.getElementById('comparar-leyenda-nombre').textContent = nombre;
            const colBNombre = document.getElementById('col-b-nombre');
            if (colBNombre) colBNombre.textContent = nombre;

            // Actualizar stats columna derecha
            const maxVals = {};
            STAT_KEYS_C.forEach(k => maxVals[k] = Math.max(BASE_A[k] || 0, statsB[k] || 0));

            document.querySelectorAll('.comparar-stat-row').forEach(row => {
                const k   = row.dataset.stat;
                const vA  = BASE_A[k] || 0;
                const vB  = statsB[k] || 0;
                const max = maxVals[k] || 1;
                const diff = vB - vA;

                const valB = row.querySelector('.comparar-val-b');
                const diffEl = row.querySelector('.comparar-diff');
                const barB = row.querySelector('.comparar-bar-b');

                if (valB) valB.textContent = vB >= 10 ? Math.round(vB).toLocaleString('es-ES') : vB.toFixed(1);
                if (diffEl) {
                    diffEl.textContent = (diff >= 0 ? '+' : '') + Math.round(diff).toLocaleString('es-ES');
                    diffEl.style.color = diff > 0 ? '#2ecc71' : diff < 0 ? '#e74c3c' : 'var(--text-muted)';
                }
                if (barB) barB.style.width = (vB / max * 100) + '%';
            });

            document.getElementById('comparar-hint').style.display = 'none';
        }

        function limpiarComparador() {
            document.getElementById('comparar-buscar').value = '';
            document.getElementById('comparar-sugerencias').style.display = 'none';
            document.getElementById('comparar-seleccionado').style.display = 'none';
            document.getElementById('comparar-hint').style.display = 'block';
            document.getElementById('comparar-leyenda-nombre').textContent = '—';
            const colBNombre = document.getElementById('col-b-nombre');
            if (colBNombre) colBNombre.textContent = 'Selecciona una criatura';
            if (radarComparar) {
                radarComparar.data.datasets[1].data = STAT_KEYS_C.map(() => 0);
                radarComparar.update();
            }
            document.querySelectorAll('.comparar-val-b').forEach(el => el.textContent = '—');
            document.querySelectorAll('.comparar-diff').forEach(el => { el.textContent = '—'; el.style.color = ''; });
            document.querySelectorAll('.comparar-bar-b').forEach(el => el.style.width = '0%');
        }

        // Cerrar sugerencias al clicar fuera
        document.addEventListener('click', e => {
            if (!e.target.closest('#comparar-buscar') && !e.target.closest('#comparar-sugerencias')) {
                document.getElementById('comparar-sugerencias').style.display = 'none';
            }
        });

        // Inicializar radar al abrir el tab
        const _origSwitch = window.switchDinoTab || function(){};
        window.switchDinoTab = function(name, btn) {
            _origSwitch(name, btn);
            if (name === 'comparar' && !radarComparar) initRadarComparar();
        };
        <?php endif; ?>

        // ── MODAL RADAR ───────────────────────────────────
        (function() {
            const modal = document.createElement('div');
            modal.id = 'radar-modal';
            modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:20px;';
            modal.innerHTML = '<div id="radar-modal-inner" style="background:#1e1e1e;border:1px solid #333;border-radius:16px;padding:28px;max-width:680px;width:100%;position:relative;box-shadow:0 30px 80px rgba(0,0,0,0.8);"><button id="radar-modal-close" style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,0.08);border:none;color:#aaa;border-radius:8px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:0.2s;" onmouseover="this.style.background=\'rgba(255,255,255,0.15)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.08)\'"><span class="material-symbols-outlined" style="font-size:1.2rem;">close</span></button><h4 id="radar-modal-title" style="margin:0 0 20px;font-size:0.85rem;color:#aaa;text-transform:uppercase;letter-spacing:1px;font-weight:700;"></h4><div style="width:100%;height:440px;position:relative;"><canvas id="radar-modal-canvas"></canvas></div></div>';
            document.body.appendChild(modal);

            let modalChart = null;

            function cerrarModal() {
                modal.style.display = 'none';
                if (modalChart) { modalChart.destroy(); modalChart = null; }
            }

            document.getElementById('radar-modal-close').addEventListener('click', cerrarModal);
            modal.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

            window.abrirRadarModal = function(canvasId) {
                const srcChart = canvasId === 'statsRadar' ? window.radarChart : window.radarComparar;
                if (!srcChart) return;

                document.getElementById('radar-modal-title').textContent =
                    canvasId === 'statsRadar' ? 'Grafico Radar - Valores calculados' : 'Radar Comparativo';

                if (modalChart) { modalChart.destroy(); modalChart = null; }

                modal.style.display = 'flex';

                // Doble rAF: primer frame pinta el modal, segundo frame tiene dimensiones reales
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    const canvas = document.getElementById('radar-modal-canvas');
                    const container = canvas.parentElement;

                    // Forzar dimensiones explícitas
                    const w = container.offsetWidth  || 600;
                    const h = container.offsetHeight || 440;
                    canvas.style.width  = w + 'px';
                    canvas.style.height = h + 'px';
                    canvas.width  = w;
                    canvas.height = h;

                    const accentRgb = getComputedStyle(document.body).getPropertyValue('--accent-rgb').trim() || '0,255,204';

                    // Copiar solo los datos numéricos, no el objeto completo (tiene funciones no serializables)
                    const srcData = {
                        labels: srcChart.data.labels.slice(),
                        datasets: srcChart.data.datasets.map(function(ds, i) {
                            return {
                                label: ds.label || '',
                                data: ds.data ? ds.data.slice() : [],
                                backgroundColor: i === 0 ? 'rgba(' + accentRgb + ',0.15)' : ds.backgroundColor,
                                borderColor:     i === 0 ? 'rgba(' + accentRgb + ',1)'    : ds.borderColor,
                                pointBackgroundColor: i === 0 ? 'rgba(' + accentRgb + ',1)' : ds.pointBackgroundColor,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                borderWidth: i === 0 ? 3 : 3,
                                borderDash: ds.borderDash || [],
                            };
                        })
                    };

                    modalChart = new Chart(canvas.getContext('2d'), {
                        type: 'radar',
                        data: srcData,
                        options: {
                            responsive: false,
                            animation: { duration: 300 },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: { color: '#ccc', font: { size: 12, family: 'inherit' }, padding: 16 }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(c) { return ' ' + c.dataset.label + ': ' + Math.round(c.raw).toLocaleString('es-ES'); }
                                    }
                                }
                            },
                            scales: {
                                r: {
                                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                                    grid: { color: 'rgba(255,255,255,0.1)' },
                                    pointLabels: { color: '#ddd', font: { size: 13, family: 'inherit', weight: '600' } },
                                    ticks: { display: false, backdropColor: 'transparent' },
                                    suggestedMin: 0,
                                }
                            }
                        }
                    });
                }));
            };
        })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>

</html>