<?php
session_start();
include 'config/db.php';

$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$dieta = isset($_GET['dieta']) ? $_GET['dieta'] : '';

$sql = "SELECT * FROM dinosaurios WHERE 1=1";

// Si hay búsqueda por texto, añadimos la condición
if ($busqueda != '') {
    $sql .= " AND (nombre LIKE :busqueda OR especie LIKE :busqueda)";
}

// Si hay filtro por dieta, añadimos la condición
if ($dieta != '') {
    $sql .= " AND dieta = :dieta";
}

$stmt = $conexion->prepare($sql);

// Pasamos los parámetros solo si existen
if ($busqueda != '') {
    $termino = "%$busqueda%";
    $stmt->bindParam(':busqueda', $termino);
}
if ($dieta != '') {
    $stmt->bindParam(':dieta', $dieta);
}

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
                <span class="bienvenida">Hola, <strong><?php echo $_SESSION['nick']; ?></strong></span>
                
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
    </main>
</body>
</html>