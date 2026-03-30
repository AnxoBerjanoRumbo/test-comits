<?php
session_start();
include 'config/db.php';

$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$dieta    = isset($_GET['dieta'])  ? $_GET['dieta']  : '';
$mapa_id  = (isset($_GET['mapa']) && is_numeric($_GET['mapa'])) ? (int)$_GET['mapa'] : 0;

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
    <section class="buscador">
        <form action="index.php" method="GET">
            <input type="text" name="buscar" placeholder="Busca tu criatura..." 
                value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">

            <select name="dieta">
                <option value="">Todas las dietas</option>
                <option value="Carnívoro" <?php echo(isset($_GET['dieta']) && $_GET['dieta'] == 'Carnívoro') ? 'selected' : ''; ?>>Carnívoro</option>
                <option value="Herbívoro" <?php echo(isset($_GET['dieta']) && $_GET['dieta'] == 'Herbívoro') ? 'selected' : ''; ?>>Herbívoro</option>
                <option value="Omnívoro" <?php echo(isset($_GET['dieta']) && $_GET['dieta'] == 'Omnívoro') ? 'selected' : ''; ?>>Omnívoro</option>
            </select>

            <select name="mapa">
                <option value="">Todos los mapas</option>
                <?php foreach ($todos_mapas as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo ($mapa_id == $m['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m['nombre_mapa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="cat">
                <option value="">Todas las categorías</option>
                <?php foreach ($todos_cats as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($cat_id == $c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Filtrar</button>
            
            <?php if ($busqueda != '' || $dieta != '' || $mapa_id > 0 || $cat_id > 0): ?>
                <a href="index.php" class="boton-limpiar">Limpiar</a>
            <?php endif; ?>
        </form>
    </section>
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
                            <?php
                            $query_detalle = $_GET;
                            unset($query_detalle['status'], $query_detalle['error']);
                            $query_detalle['id'] = $dino['id'];
                            $link_detalle = 'detalle.php?' . http_build_query($query_detalle);
                            ?>
                            <a href="<?php echo htmlspecialchars($link_detalle); ?>" class="enlace-dino">
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