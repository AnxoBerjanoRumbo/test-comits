<?php
// includes/header.php
// Detectar la profundidad para las rutas de los assets
$path_prefix = isset($is_admin_panel) && $is_admin_panel ? '../' : '';

$foto_perfil_h = $_SESSION['foto_perfil'] ?? 'default.png';
$src_foto_h = (strpos($foto_perfil_h, 'http') === 0) ? $foto_perfil_h : $path_prefix . "assets/img/perfil/" . $foto_perfil_h;
?>
<header class="header-principal">
    <div class="logo-titulo">
        <a href="<?php echo $path_prefix; ?>index.php" style="text-decoration: none;">
            <h1><?php echo $header_titulo ?? 'ARK Survival Hub'; ?></h1>
        </a>
    </div>
    
    <nav class="navegacion-usuario">
        <?php if (isset($header_volver_link) && isset($header_volver_texto)): ?>
            <a href="<?php echo $header_volver_link; ?>" class="btn-nav"><?php echo $header_volver_texto; ?></a>
        <?php endif; ?>

        <?php if (isset($_SESSION['nick'])): ?>
            <a href="<?php echo $path_prefix; ?>perfil.php" class="enlace-perfil" style="color: white; text-decoration: none; margin-right: 15px; display: flex; align-items: center; gap: 10px;">
                <img src="<?php echo htmlspecialchars($src_foto_h); ?>" 
                     alt="Perfil" 
                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);"
                     onerror="this.src='<?php echo $path_prefix; ?>assets/img/perfil/default.png'">
                <span class="bienvenida">Hola, <strong><?php echo htmlspecialchars($_SESSION['nick']); ?></strong></span>
            </a>
            
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin'): ?>
                <a href="<?php echo $path_prefix; ?>panel_superadmin.php" class="btn-nav" style="background-color: #ffcc00; color: #1a1a1a; border-color: #ffcc00;">Panel Superadmin</a>
            <?php endif; ?>

            <a href="<?php echo $path_prefix; ?>actions/logout.php" class="btn-nav">Cerrar Sesión</a>
        <?php else: ?>
            <a href="<?php echo $path_prefix; ?>login.php" class="btn-nav">Loguearse</a>
            <a href="<?php echo $path_prefix; ?>registro.php" class="btn-nav btn-registro">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>
