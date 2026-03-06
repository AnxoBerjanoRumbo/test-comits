<?php
session_start();
include 'config/db.php';

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
$por_pagina = 10;
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
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header class="header-principal">
        <div class="logo-titulo">
            <h1>ARK Survival Hub</h1>
        </div>
        
        <nav class="navegacion-usuario">
            <?php if (isset($_SESSION['nick'])): ?>
                <a href="perfil.php" class="enlace-perfil" style="color: white; text-decoration: none; margin-right: 15px; display: flex; align-items: center; gap: 10px;">
                    <img src="assets/img/perfil/<?php echo htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png'); ?>" alt="Perfil" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
                    <span class="bienvenida">Hola, <strong><?php echo htmlspecialchars($_SESSION['nick']); ?></strong></span>
                </a>
                
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin'): ?>
                    <a href="panel_superadmin.php" class="btn-nav" style="background-color: #ffcc00; color: #1a1a1a; border-color: #ffcc00;">Panel Superadmin</a>
                <?php
    endif; ?>

                <a href="logout.php" class="btn-nav">Cerrar Sesión</a>
            <?php
else: ?>
                <a href="login.php" class="btn-nav">Loguearse</a>
                <a href="registro.php" class="btn-nav btn-registro">Registrarse</a>
            <?php
endif; ?>
        </nav>
    </header>
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
                                <img src="assets/img/dinos/<?php echo htmlspecialchars($dino['imagen']); ?>" alt="<?php echo htmlspecialchars($dino['nombre']); ?>" class="dino-img">
                            </div>
                        <?php endif; ?>
                        <h3>
                            <a href="detalle.php?id=<?php echo $dino['id']; ?>" class="enlace-dino">
                                <?php echo htmlspecialchars($dino['nombre']); ?>
                            </a>
                        </h3>
                        <p><strong>Especie:</strong> <?php echo htmlspecialchars($dino['especie']); ?></p>
                        <p><strong>Dieta:</strong> <?php echo htmlspecialchars($dino['dieta']); ?></p>
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
        <div class="paginacion" style="margin-top: 30px; text-align: center;">
            <?php
            $query_params = $_GET;
            for ($i = 1; $i <= $total_paginas; $i++):
                $query_params['p'] = $i;
                $link = 'index.php?' . http_build_query($query_params);
            ?>
                <a href="<?php echo htmlspecialchars($link); ?>" class="btn-nav <?php echo ($i == $pagina_actual) ? 'btn-registro' : ''; ?>" style="margin: 0 5px; padding: 5px 10px;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </main>
    
    <button id="btnArriba" class="btn-arriba">
        ⬆
    </button>
    <script src="assets/js/main.js"></script>
</body>
</html>