<?php
session_start();
include 'config/db.php';
include 'config/sync_foto.php';

$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$dieta = isset($_GET['dieta']) ? $_GET['dieta'] : '';

$sql = "SELECT * FROM dinosaurios WHERE 1=1";
$sql_count = "SELECT COUNT(*) FROM dinosaurios WHERE 1=1";

// Si hay búsqueda por texto, añadimos la condición
if ($busqueda != '') {
    $sql .= " AND (nombre LIKE :busqueda OR especie LIKE :busqueda)";
    $sql_count .= " AND (nombre LIKE :busqueda OR especie LIKE :busqueda)";
}

// Si hay filtro por dieta, añadimos la condición
if ($dieta != '') {
    $sql .= " AND dieta = :dieta";
    $sql_count .= " AND dieta = :dieta";
}

// Paginación
$por_pagina = 9;
$pagina_actual = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Ejecutar count
$stmt_count = $conexion->prepare($sql_count);
if ($busqueda != '') {
    $termino = "%$busqueda%";
    $stmt_count->bindParam(':busqueda', $termino);
}
if ($dieta != '') {
    $stmt_count->bindParam(':dieta', $dieta);
}
$stmt_count->execute();
$total_dinos = $stmt_count->fetchColumn();
$total_paginas = ceil($total_dinos / $por_pagina);

// Añadir limits
$sql .= " LIMIT :limit OFFSET :offset";

$stmt = $conexion->prepare($sql);

// Pasamos los parámetros solo si existen
if ($busqueda != '') {
    $termino = "%$busqueda%";
    $stmt->bindParam(':busqueda', $termino);
}
if ($dieta != '') {
    $stmt->bindParam(':dieta', $dieta);
}
// PDO requiere bind explícito para INTs en LIMIT/OFFSET cuando emulation_prepare está ON
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

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

            <button type="submit">Filtrar</button>
            
            <?php if (isset($_GET['buscar']) || isset($_GET['dieta'])): ?>
                <a href="index.php" class="boton-limpiar">Limpiar</a>
            <?php
endif; ?>
        </form>
    </section>
    <main>
        <h2>Diccionario de Criaturas</h2>
        
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
                                ?>
                                <img src="<?php echo htmlspecialchars($src_dino); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" class="dino-img" onerror="this.src='assets/img/dinos/default_dino.jpg'">
                            </div>
                        <?php endif; ?>
                        <h3>
                            <a href="detalle.php?id=<?php echo $dino['id']; ?>" class="enlace-dino">
                                <?php echo htmlspecialchars($dino['nombre']); ?>
                            </a>
                        </h3>
                    </div>
                <?php
    endforeach; ?>
            <?php
else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: #888;">
                No se han encontrado criaturas que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
                </p>
            <?php
endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="paginacion" style="margin-top: 40px; text-align: center;">
            <?php
            $query_params = $_GET;
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