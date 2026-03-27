<?php
// includes/header.php
// Detectar la profundidad para las rutas de los assets
$path_prefix = isset($is_admin_panel) && $is_admin_panel ? '../' : '';

$foto_perfil_h = $_SESSION['foto_perfil'] ?? 'default.png';
$src_foto_h = (strpos($foto_perfil_h, 'http') === 0) ? $foto_perfil_h : $path_prefix . "assets/img/perfil/" . $foto_perfil_h;

// Verificación de baneo en tiempo real (si está logueado)
if (isset($_SESSION['usuario_id'])) {
    if (!isset($conexion)) {
        include_once ($path_prefix ?: './') . 'config/db.php';
    }
    include_once ($path_prefix ?: './') . 'config/verificar_sesion.php';
    
    // Adaptar ruta de redirección según el contexto
    $login_path = $path_prefix . "login.php";
    check_user_active_status($conexion, $login_path);
}
?>
<header class="header-principal">
    <div class="logo-titulo">
        <a href="<?php echo $path_prefix; ?>index.php" class="no-decoration">
            <h1><?php echo $header_titulo ?? 'ARK Survival Hub'; ?></h1>
        </a>
    </div>
    
    <nav class="navegacion-usuario">
        <?php if (isset($_SESSION['nick'])): ?>
            <a href="<?php echo $path_prefix; ?>perfil.php" class="enlace-perfil-header">
                <img src="<?php echo htmlspecialchars($src_foto_h); ?>" 
                     alt="Perfil" 
                     class="avatar-header"
                     onerror="this.src='<?php echo $path_prefix; ?>assets/img/perfil/default.png'">
                <span class="bienvenida">Hola, <strong><?php echo htmlspecialchars($_SESSION['nick']); ?></strong></span>
            </a>
        <?php endif; ?>

        <?php if (isset($header_volver_link) && isset($header_volver_texto)): ?>
            <a href="<?php echo $header_volver_link; ?>" class="btn-nav"><?php echo $header_volver_texto; ?></a>
        <?php endif; ?>

        <?php if (isset($_SESSION['nick'])): ?>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin'): ?>
                <a href="<?php echo $path_prefix; ?>panel_superadmin.php" class="btn-nav btn-superadmin">Panel Superadmin</a>
            <?php endif; ?>

            <a href="<?php echo $path_prefix; ?>actions/logout.php" class="btn-nav">Cerrar Sesión</a>
        <?php else: ?>
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <?php if ($current_page !== 'login.php'): ?>
                <a href="<?php echo $path_prefix; ?>login.php" class="btn-nav">Loguearse</a>
            <?php endif; ?>
            
            <?php if ($current_page !== 'registro.php'): ?>
                <a href="<?php echo $path_prefix; ?>registro.php" class="btn-nav btn-registro">Registrarse</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</header>
