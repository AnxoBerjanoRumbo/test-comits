<?php
session_start();
include 'config/db.php';

$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$dieta    = isset($_GET['dieta'])  ? $_GET['dieta']  : '';
$mapa_id  = (isset($_GET['mapa']) && is_numeric($_GET['mapa'])) ? (int)$_GET['mapa'] : 0;
$rol_filtro = isset($_GET['rol']) ? $_GET['rol'] : '';

// Filtro por stat
$stat_filtro = isset($_GET['stat']) ? $_GET['stat'] : '';
$stat_min    = (isset($_GET['stat_min']) && is_numeric($_GET['stat_min'])) ? (int)$_GET['stat_min'] : 0;
$stat_max    = (isset($_GET['stat_max']) && is_numeric($_GET['stat_max'])) ? (int)$_GET['stat_max'] : 0;
$stats_filtrables = ['stat_health','stat_stamina','stat_weight','stat_melee','stat_food','stat_torpidity'];

// Cargar todos los mapas para el selector del formulario
$stmt_mapas_lista = $conexion->prepare("SELECT * FROM mapas ORDER BY nombre_mapa ASC");
$stmt_mapas_lista->execute();
$todos_mapas = $stmt_mapas_lista->fetchAll(PDO::FETCH_ASSOC);

// Cargar categorias para el selector
$stmt_cats_lista = $conexion->prepare("SELECT * FROM categorias ORDER BY orden ASC");
$stmt_cats_lista->execute();
$todos_cats = $stmt_cats_lista->fetchAll(PDO::FETCH_ASSOC);

$cat_id = (isset($_GET['cat']) && is_numeric($_GET['cat'])) ? (int)$_GET['cat'] : 0;

// Etiqueta de la categoria seleccionada
$nombre_cat_sel = '';
if ($cat_id > 0) {
    foreach ($todos_cats as $c) {
        if ($c['id'] == $cat_id) { $nombre_cat_sel = $c['nombre']; break; }
    }
}

// Etiqueta del mapa seleccionado para el título dinámico
$nombre_mapa_sel = '';
if ($mapa_id > 0) {
    foreach ($todos_mapas as $m) {
        if ($m['id'] == $mapa_id) { $nombre_mapa_sel = $m['nombre_mapa']; break; }
    }
}

// Construir la consulta base (JOIN si hay filtro de mapa y/o categoria)
if ($mapa_id > 0 && $cat_id > 0) {
    $base      = "FROM dinosaurios d
                  INNER JOIN dino_mapas dm       ON d.id = dm.dino_id AND dm.mapa_id = :mapa_id
                  INNER JOIN dino_categorias dc  ON d.id = dc.dino_id AND dc.categoria_id = :cat_id
                  WHERE 1=1";
    $sql       = "SELECT DISTINCT d.* $base";
    $sql_count = "SELECT COUNT(DISTINCT d.id) $base";
} elseif ($mapa_id > 0) {
    $base      = "FROM dinosaurios d INNER JOIN dino_mapas dm ON d.id = dm.dino_id WHERE dm.mapa_id = :mapa_id";
    $sql       = "SELECT DISTINCT d.* $base";
    $sql_count = "SELECT COUNT(DISTINCT d.id) $base";
} elseif ($cat_id > 0) {
    $base      = "FROM dinosaurios d INNER JOIN dino_categorias dc ON d.id = dc.dino_id WHERE dc.categoria_id = :cat_id";
    $sql       = "SELECT DISTINCT d.* $base";
    $sql_count = "SELECT COUNT(DISTINCT d.id) $base";
} else {
    $sql       = "SELECT * FROM dinosaurios WHERE 1=1";
    $sql_count = "SELECT COUNT(*) FROM dinosaurios WHERE 1=1";
}

// Alias correcto dependiendo de si hay JOIN
$alias = ($mapa_id > 0 || $cat_id > 0) ? 'd.' : '';

if ($busqueda != '') {
    $sql       .= " AND ({$alias}nombre LIKE :busqueda OR {$alias}especie LIKE :busqueda)";
    $sql_count .= " AND ({$alias}nombre LIKE :busqueda OR {$alias}especie LIKE :busqueda)";
}
if ($dieta != '') {
    $sql       .= " AND {$alias}dieta = :dieta";
    $sql_count .= " AND {$alias}dieta = :dieta";
}

// Filtro por rol/utilidad
$roles_validos = ['es_tanque','es_buff','es_recolector','es_montura','es_volador','es_acuatico','es_subterraneo'];
if ($rol_filtro !== '' && in_array($rol_filtro, $roles_validos)) {
    $sql       .= " AND {$alias}{$rol_filtro} = 1";
    $sql_count .= " AND {$alias}{$rol_filtro} = 1";
}

// Filtro por stat con rango
if ($stat_filtro !== '' && in_array($stat_filtro, $stats_filtrables)) {
    if ($stat_min > 0) {
        $sql       .= " AND {$alias}{$stat_filtro} >= :stat_min";
        $sql_count .= " AND {$alias}{$stat_filtro} >= :stat_min";
    }
    if ($stat_max > 0) {
        $sql       .= " AND {$alias}{$stat_filtro} <= :stat_max";
        $sql_count .= " AND {$alias}{$stat_filtro} <= :stat_max";
    }
}

// Paginación
$por_pagina    = 9;
$pagina_actual = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Ejecutar COUNT
$stmt_count = $conexion->prepare($sql_count);
if ($mapa_id > 0)    $stmt_count->bindValue(':mapa_id', $mapa_id, PDO::PARAM_INT);
if ($cat_id  > 0)    $stmt_count->bindValue(':cat_id',  $cat_id,  PDO::PARAM_INT);
if ($busqueda != '') { $termino = "%$busqueda%"; $stmt_count->bindParam(':busqueda', $termino); }
if ($dieta != '')    $stmt_count->bindParam(':dieta', $dieta);
if ($stat_filtro !== '' && in_array($stat_filtro, $stats_filtrables)) {
    if ($stat_min > 0) $stmt_count->bindValue(':stat_min', $stat_min, PDO::PARAM_INT);
    if ($stat_max > 0) $stmt_count->bindValue(':stat_max', $stat_max, PDO::PARAM_INT);
}
$stmt_count->execute();
$total_dinos   = $stmt_count->fetchColumn();
$total_paginas = (int)ceil($total_dinos / $por_pagina);

// Ejecutar SELECT con LIMIT
$sql .= " LIMIT :limit OFFSET :offset";
$stmt = $conexion->prepare($sql);
if ($mapa_id > 0)    $stmt->bindValue(':mapa_id', $mapa_id, PDO::PARAM_INT);
if ($cat_id  > 0)    $stmt->bindValue(':cat_id',  $cat_id,  PDO::PARAM_INT);
if ($busqueda != '') { $termino = "%$busqueda%"; $stmt->bindParam(':busqueda', $termino); }
if ($dieta != '')    $stmt->bindParam(':dieta', $dieta);
if ($stat_filtro !== '' && in_array($stat_filtro, $stats_filtrables)) {
    if ($stat_min > 0) $stmt->bindValue(':stat_min', $stat_min, PDO::PARAM_INT);
    if ($stat_max > 0) $stmt->bindValue(':stat_max', $stat_max, PDO::PARAM_INT);
}
$stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
$stmt->execute();
$dinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARK Survival Hub - Wiki</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.3">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- Overlay oscuro detrás del sidebar -->
    <div id="filter-overlay" class="filter-overlay" onclick="closeFilterSidebar()"></div>

    <!-- Sidebar de Filtros -->
    <div id="filters-sidebar" class="filters-sidebar">
        <div class="filters-sidebar-header">
            <h2 style="margin:0; display:flex; align-items:center; gap:10px; color:var(--text-main); font-size:1.2rem;">
                <span class="material-symbols-outlined">menu</span>
                Filtros Avanzados
            </h2>
            <button class="filters-sidebar-close" onclick="closeFilterSidebar()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="index.php" method="GET" id="form-buscador" class="filters-sidebar-content">
            <!-- Búsqueda principal -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Buscar Criatura</label>
                <div class="buscador-input-wrap" style="margin-top:6px;">
                    <span class="material-symbols-outlined buscador-icon">search</span>
                    <input type="text" name="buscar" placeholder="Nombre o especie..."
                        value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" style="border-radius:8px;">
                </div>
            </div>

            <!-- Dieta -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Dieta</label>
                <select name="dieta" class="buscador-select" style="margin-top:6px; width:100%;">
                    <option value="">— Todas —</option>
                    <option value="Carnívoro"  <?php echo($dieta=='Carnívoro') ?'selected':''; ?>>🥩 Carnívoro</option>
                    <option value="Herbívoro"  <?php echo($dieta=='Herbívoro') ?'selected':''; ?>>🌿 Herbívoro</option>
                    <option value="Omnívoro"   <?php echo($dieta=='Omnívoro')  ?'selected':''; ?>>🍽️ Omnívoro</option>
                    <option value="Piscívoro"  <?php echo($dieta=='Piscívoro') ?'selected':''; ?>>🐟 Piscívoro</option>
                </select>
            </div>

            <!-- Rol -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Rol / Utilidad</label>
                <select name="rol" class="buscador-select" style="margin-top:6px; width:100%;">
                    <option value="">— Todos —</option>
                    <option value="es_tanque"      <?php echo $rol_filtro==='es_tanque'      ?'selected':''; ?>>🛡️ Tanque</option>
                    <option value="es_buff"        <?php echo $rol_filtro==='es_buff'        ?'selected':''; ?>>📈 Buff</option>
                    <option value="es_recolector"  <?php echo $rol_filtro==='es_recolector'  ?'selected':''; ?>>📦 Recolector</option>
                    <option value="es_montura"     <?php echo $rol_filtro==='es_montura'     ?'selected':''; ?>>🐴 Montura</option>
                    <option value="es_volador"     <?php echo $rol_filtro==='es_volador'     ?'selected':''; ?>>🦅 Volador</option>
                    <option value="es_acuatico"    <?php echo $rol_filtro==='es_acuatico'    ?'selected':''; ?>>🐳 Acuático</option>
                    <option value="es_subterraneo" <?php echo $rol_filtro==='es_subterraneo' ?'selected':''; ?>>🦇 Cueva</option>
                </select>
            </div>

            <!-- Mapa -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Mapa</label>
                <select name="mapa" class="buscador-select" style="margin-top:6px; width:100%;">
                    <option value="">— Todos —</option>
                    <?php foreach ($todos_mapas as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo ($mapa_id==$m['id'])?'selected':''; ?>>
                            🗺️ <?php echo htmlspecialchars($m['nombre_mapa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Categoría -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Categoría</label>
                <select name="cat" class="buscador-select" style="margin-top:6px; width:100%;">
                    <option value="">— Todas —</option>
                    <?php foreach ($todos_cats as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($cat_id==$c['id'])?'selected':''; ?>>
                            🏷️ <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Stat -->
            <div class="filter-group">
                <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Filtrar por Stat</label>
                <select name="stat" class="buscador-select" style="margin-top:6px; width:100%;">
                    <option value="">— Selecciona stat —</option>
                    <?php
                    $stat_opts = ['stat_health'=>'❤️ Vida','stat_stamina'=>'⚡ Energía','stat_weight'=>'⚖️ Peso','stat_melee'=>'⚔️ Melée','stat_food'=>'🍖 Comida','stat_torpidity'=>'😴 Torpor'];
                    foreach ($stat_opts as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo $stat_filtro===$val?'selected':''; ?>><?php echo $lbl; ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
                    <div>
                        <label style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:4px;">Mínimo</label>
                        <input type="number" name="stat_min" min="0" value="<?php echo $stat_min ?: ''; ?>" placeholder="0"
                            style="width:100%; padding:10px 12px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); border-radius:8px; font-family:inherit; font-size:0.9rem; outline:none; transition:border 0.2s;"
                            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                    </div>
                    <div>
                        <label style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:4px;">Máximo</label>
                        <input type="number" name="stat_max" min="0" value="<?php echo $stat_max ?: ''; ?>" placeholder="∞"
                            style="width:100%; padding:10px 12px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--input-text); border-radius:8px; font-family:inherit; font-size:0.9rem; outline:none; transition:border 0.2s;"
                            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-color)'">
                    </div>
                </div>
                <p style="font-size:0.7rem; color:var(--text-muted); margin-top:6px;">Filtra por valor base a nivel 1 salvaje.</p>
            </div>

            <!-- Botones de acción -->
            <div style="display:flex; gap:10px; margin-top:20px; padding-top:15px; border-top:1px solid var(--border-color);">
                <button type="submit" class="btn-apply-filters" style="flex:1;">
                    <span class="material-symbols-outlined">search</span>
                    Buscar
                </button>
                <?php if ($busqueda!='' || $dieta!='' || $mapa_id>0 || $cat_id>0 || $rol_filtro!='' || $stat_filtro!=''): ?>
                    <a href="index.php" class="btn-clear-filters" style="flex:1; text-align:center; display:flex; align-items:center; justify-content:center;">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Chips de filtros activos (solo mostrar si hay filtros activos) -->
    <?php
    $filtros_activos = [];
    if ($busqueda != '') $filtros_activos[] = [htmlspecialchars("Búsqueda: $busqueda"), 'buscar'];
    if ($mapa_id > 0) {
        foreach ($todos_mapas as $m) {
            if ($m['id'] == $mapa_id) {
                $filtros_activos[] = [$m['nombre_mapa'], 'mapa'];
                break;
            }
        }
    }
    if ($cat_id > 0) {
        foreach ($todos_cats as $c) {
            if ($c['id'] == $cat_id) {
                $filtros_activos[] = [$c['nombre'], 'cat'];
                break;
            }
        }
    }
    if ($rol_filtro != '') {
        $rol_nombres = ['es_tanque'=>'Tanque','es_buff'=>'Buff','es_recolector'=>'Recolector','es_montura'=>'Montura','es_volador'=>'Volador','es_acuatico'=>'Acuático','es_subterraneo'=>'Cueva'];
        $filtros_activos[] = [$rol_nombres[$rol_filtro] ?? $rol_filtro, 'rol'];
    }
    if ($dieta != '') $filtros_activos[] = [$dieta, 'dieta'];
    if ($stat_filtro !== '' && in_array($stat_filtro, $stats_filtrables)) {
        $stat_nombres = ['stat_health'=>'Vida','stat_stamina'=>'Energía','stat_weight'=>'Peso','stat_melee'=>'Melée','stat_food'=>'Comida','stat_torpidity'=>'Torpor'];
        $label_stat = ($stat_nombres[$stat_filtro] ?? $stat_filtro);
        $s_min = (int)$stat_min;
        $s_max = (int)$stat_max;
        if ($s_min > 0 && $s_max > 0) $label_stat .= ": " . $s_min . "-" . $s_max;
        elseif ($s_min > 0) $label_stat .= " mayor o igual a " . $s_min;
        elseif ($s_max > 0) $label_stat .= " menor o igual a " . $s_max;
        $filtros_activos[] = [$label_stat, 'stat'];
    }
    ?>
    <?php if (!empty($filtros_activos)): ?>
    <section class="buscador">
        <div class="buscador-activos" id="filtros-activos-container">
            <div id="filtros-activos" style="display:flex; flex-wrap:wrap; gap:6px; padding:10px; background:rgba(var(--accent-rgb),0.05); border-radius:8px; border:1px solid rgba(var(--accent-rgb),0.1);">
                <?php foreach ($filtros_activos as [$label, $param]): ?>
                <a href="index.php?<?php echo http_build_query(array_diff_key($_GET, [$param=>'','p'=>''])); ?>" class="filtro-activo-chip">
                    <?php echo $label; ?> <span class="material-symbols-outlined" style="font-size:0.8rem;">close</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
    function toggleFilterSidebar() {
        const sidebar = document.getElementById('filters-sidebar');
        const overlay = document.getElementById('filter-overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    
    function closeFilterSidebar() {
        const sidebar = document.getElementById('filters-sidebar');
        const overlay = document.getElementById('filter-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }
    
    function toggleStatFilter() {
        const panel = document.getElementById('filtro-stat-panel');
        const btn   = document.getElementById('btn-toggle-stat');
        const visible = panel.style.display !== 'none';
        panel.style.display = visible ? 'none' : 'block';
        if (btn.querySelector('span:last-child')) {
            btn.lastChild.textContent = visible ? ' Filtrar por stat base' : ' Ocultar filtro por stat';
        }
    }
    </script>

    <main>
        <h2 class="titulo-seccion">
            <?php if ($nombre_mapa_sel): ?>
                Criaturas en <span class="accent-text"><?php echo htmlspecialchars($nombre_mapa_sel); ?></span>
            <?php else: ?>
                Diccionario de Criaturas
            <?php endif; ?>
        </h2>
        
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <div style="text-align: center; margin-bottom: 25px;">
                <a href="admin/insertar.php" class="btn-nav btn-registro" style="padding: 10px 20px; font-size: 1.1rem; text-decoration: none; display: inline-block;">
                    Añadir Nueva Criatura
                </a>
            </div>
        <?php
endif; ?>

        <div class="contenedor-dinos">
            <?php if (count($dinos) > 0): ?>
                <?php foreach ($dinos as $dino): ?>
                    <?php
                    $query_detalle = $_GET;
                    unset($query_detalle['status'], $query_detalle['error']);
                    $query_detalle['id'] = $dino['id'];
                    $link_detalle = 'detalle.php?' . http_build_query($query_detalle);
                    ?>
                    <div class="dino-card">
                        <?php if(!empty($dino['imagen'])): ?>
                            <div class="dino-img-container">
                                <?php 
                                $src_dino = (strpos($dino['imagen'], 'http') === 0) ? $dino['imagen'] : "assets/img/dinos/" . $dino['imagen'];
                                
                                // Optimización de Cloudinary para el listado principal
                                if (strpos($src_dino, 'res.cloudinary.com') !== false) {
                                    $src_dino = str_replace('/upload/', '/upload/w_400,c_fill,g_auto,f_auto,q_auto/', $src_dino);
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($src_dino); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" class="dino-img" onerror="this.src='assets/img/dinos/default_dino.jpg'">
                            </div>
                        <?php endif; ?>
                        <h3>
                            <a href="<?php echo htmlspecialchars($link_detalle); ?>" class="enlace-dino card-stretched-link">
                                <?php echo htmlspecialchars($dino['nombre']); ?>
                            </a>
                        </h3>
                    </div>
                <?php
    endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: #888;">
                    <?php if ($nombre_mapa_sel): ?>
                        No hay criaturas registradas en <strong><?php echo htmlspecialchars($nombre_mapa_sel); ?></strong> todavía.
                    <?php else: ?>
                        No se han encontrado criaturas que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="paginacion" style="margin-top: 40px; text-align: center;">
            <?php
            $query_params = $_GET;
            unset($query_params['status'], $query_params['error']);
            for ($i = 1; $i <= $total_paginas; $i++):
                $query_params['p'] = $i;
                $link = 'index.php?' . http_build_query($query_params);
            ?>
                <a href="<?php echo htmlspecialchars($link); ?>" class="btn-pag <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    <?php include 'includes/footer.php'; ?>