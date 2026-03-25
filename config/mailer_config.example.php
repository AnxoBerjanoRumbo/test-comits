<?php
// ============================================================
// CONFIGURACIÓN DE PHPMAILER (SMTP) - ARCHIVO DE PLANTILLA
// ============================================================
// 1. Copia este archivo y renombralo a: mailer_config.php
// 2. Rellena los valores con tu cuenta de correo SMTP.
//
//    SI USAS GMAIL:
//    - Username: tu dirección @gmail.com
//    - Password: genera una "Contraseña de aplicación" en:
//      https://myaccount.google.com → Seguridad → Contraseñas de aplicación
//    - Host: smtp.gmail.com  |  Port: 587
//
//    SI USAS OTRO PROVEEDOR:
//    - Consulta los datos SMTP de tu hosting/proveedor de email.
//
// 3. ¡NO subas mailer_config.php a Git!
// ============================================================

define('MAILER_HOST', 'smtp.gmail.com');
define('MAILER_USER', 'TU_CORREO@gmail.com');
define('MAILER_PASS', 'TU_CONTRASENA_DE_APLICACION');
define('MAILER_PORT', 587);
?>
