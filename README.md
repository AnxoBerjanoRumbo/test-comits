# ARK Survival Hub - Digital Wiki Encyclopedia

Documentación técnica completa y guía de despliegue.

---

## Índice
1. [Introducción](#1-introducción)
2. [Funcionalidades](#2-funcionalidades)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Requisitos del Sistema](#4-requisitos-del-sistema)
5. [Guía de Instalación](#5-guía-de-instalación)
6. [Configuración de Credenciales Externas](#6-configuración-de-credenciales-externas)
7. [Variables de Entorno (Producción)](#7-variables-de-entorno-producción)
8. [Credenciales de Acceso](#8-credenciales-de-acceso)
9. [Seguridad](#9-seguridad)
10. [Estructura de Archivos](#10-estructura-de-archivos)

---

## 1. Introducción

ARK Survival Hub es una wiki interactiva desarrollada en PHP nativo sobre el universo de ARK: Survival Evolved / Ascended. Permite gestionar fichas completas de criaturas con calculadora de stats, sistema de usuarios con roles, panel de administración y moderación, notificaciones en tiempo real y temas visuales personalizables.

---

## 2. Funcionalidades

### Wiki de Criaturas
- Listado con búsqueda por nombre, filtro por mapa y por categoría
- Paginación del listado
- Fichas detalladas con 4 tabs: Info, Stats, Roles y Utilidad, Foro

### Calculadora de Stats (Tab Stats)
- Stats base reales por criatura almacenados en BD (valores `B`)
- Multiplicadores `Iw` por stat y criatura (incremento por nivel salvaje)
- Sliders por stat para simular distribución de niveles wild
- Mutaciones por stat (campo `Mut`)
- Slider de Impronta (0–100%) con bonus del 20% en stats aplicables
- Slider de Taming / Eficiencia de domesticación (0–100%)
- Botón "Rolear Stats" para simular un encuentro salvaje a nivel X
- Gráfico radar Chart.js que se actualiza en tiempo real
- Todos los colores del gráfico y sliders se adaptan al tema activo

### Sistema de Usuarios
- Registro con verificación por email (código de 6 dígitos)
- Login con nick o email, rate limiting (bloqueo tras 5 intentos en 60s)
- Recuperación de contraseña por email con token de expiración
- Cambio de nick, contraseña y foto de perfil
- Verificación de baneo en tiempo real en cada petición

### Roles y Permisos
- `usuario`: puede comentar y gestionar su perfil
- `admin`: puede insertar/editar criaturas, borrar comentarios, moderar usuarios (según permisos asignados)
- `superadmin`: acceso total, gestión de admins y permisos, panel completo

### Panel de Administración
- Insertar y editar criaturas con todos los campos (stats, Iw, roles, recolección, buffs, domesticación, cría)
- Tablón de referencia de stats con 30 criaturas precargadas desde `assets/data/ark_creatures.json`
- Al hacer clic en una criatura del tablón se autorrellenan todos los campos del formulario
- Eliminar criaturas
- Moderar usuarios: ban temporal (10min a 1 año), ban permanente, expulsión total con bloqueo de email
- Gestión de permisos de admins (insertar, eliminar comentarios, moderar)
- Panel superadmin con búsqueda de usuarios, gestión de equipo y comunicados

### Notificaciones
- Sistema en tiempo real con polling cada 5 segundos
- Dropdown con lista de notificaciones, marcado de leídas, borrado individual y borrado total
- Página de detalle de notificación (`leer_notificacion.php`)
- Notificaciones automáticas al añadir/editar criaturas, sanciones, levantamiento de sanciones

### Temas Visuales
- 5 temas: Ragnarok (verde/cian), Aberration (morado), Extinction (amarillo), Scorched Earth (naranja), Daltónico (amarillo alto contraste)
- Todos los colores, sombras, gráficos y sliders se adaptan al tema activo mediante variables CSS `--accent` y `--accent-rgb`
- Tema guardado en `localStorage`, se aplica antes del render para evitar parpadeo

### Comentarios y Foro
- Comentarios por criatura con paginación
- Respuestas de admins a comentarios de usuarios
- Borrado por el propio autor o por admins con permiso
- Honeypot anti-spam

---

## 3. Arquitectura del Sistema

```
ark-survival-hub/
├── index.php                  # Listado principal con búsqueda y filtros
├── detalle.php                # Ficha completa de criatura (tabs, calculadora, foro)
├── login.php / registro.php   # Autenticación
├── perfil.php                 # Gestión de perfil de usuario
├── verificar.php              # Verificación de cuenta por email
├── reset_password.php         # Restablecimiento de contraseña
├── leer_notificacion.php      # Vista de notificación individual
├── panel_superadmin.php       # Panel de administración completo
│
├── actions/                   # Procesadores de lógica (POST)
│   ├── admin/                 # Acciones exclusivas de admin/superadmin
│   └── *.php                  # Login, registro, comentarios, notificaciones...
│
├── admin/                     # Vistas de administración
│   ├── insertar.php           # Formulario de nueva criatura
│   ├── editar.php             # Formulario de edición de criatura
│   └── moderar_usuario.php    # Panel de moderación de usuario
│
├── config/
│   ├── db.php                 # Conexión PDO (soporta variables de entorno)
│   ├── verificar_sesion.php   # Verificación de baneo en tiempo real
│   ├── notificaciones.php     # Funciones de notificaciones
│   ├── admin_logger.php       # Registro de acciones de admin
│   ├── mailer.php             # Envío de emails con PHPMailer
│   ├── cloudinary_helper.php  # Subida/borrado de imágenes (Cloudinary + fallback local)
│   ├── mailer_config.php      # Credenciales SMTP (no en repo, usar .example)
│   └── cloudinary_config.php  # Credenciales Cloudinary (no en repo, usar .example)
│
├── includes/
│   ├── header.php             # Cabecera, nav, notificaciones, selector de temas
│   └── footer.php             # Pie de página y botón scroll-to-top
│
├── assets/
│   ├── css/estilos.css        # Estilos globales con variables CSS por tema
│   ├── js/main.js             # Temas, notificaciones, scroll, validaciones
│   ├── js/stats_reference.js  # Tablón de referencia de stats (carga JSON)
│   ├── js/perfil.js           # Preview de foto de perfil
│   ├── js/registro.js         # Validación de formulario de registro
│   ├── js/reset_password.js   # Validación de formulario de reset
│   ├── data/ark_creatures.json # Datos de referencia de 30 criaturas ARK
│   └── img/                   # Imágenes de criaturas y perfiles
│
├── database/
│   └── ark_hub.sql            # Script completo de BD (10 tablas)
│
└── libs/PHPMailer/            # Librería PHPMailer
```

### Base de Datos (10 tablas)

| Tabla | Descripción |
|---|---|
| `mapas` | Mapas del juego disponibles |
| `usuarios` | Usuarios con roles, permisos, baneo y verificación |
| `dinosaurios` | Criaturas con stats base, Iw, roles, recolección, domesticación |
| `dino_mapas` | Relación N:M criaturas ↔ mapas |
| `categorias` | Categorías de criaturas (Terrestre, Volador, etc.) |
| `dino_categorias` | Relación N:M criaturas ↔ categorías |
| `comentarios` | Comentarios y respuestas por criatura |
| `notificaciones` | Notificaciones por usuario con estado leída/no leída |
| `admin_logs` | Registro de acciones administrativas |
| `emails_bloqueados` | Emails bloqueados permanentemente |

---

## 4. Requisitos del Sistema

- Servidor web: Apache 2.4+
- PHP: **8.0 o superior**
- Extensiones PHP: `pdo_mysql`, `mbstring`, `curl`, `gd`
- Base de datos: MySQL 5.7+ / MariaDB 10.4+

---

## 5. Guía de Instalación

### XAMPP (desarrollo local)

1. Copia la carpeta del proyecto en `C:\xampp\htdocs\ark-survival-hub\`
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP
3. Abre `http://localhost/phpmyadmin`, crea una base de datos llamada `ark_hub`
4. Selecciona la BD, ve a **Importar** y sube `database/ark_hub.sql`
5. Accede a `http://localhost/ark-survival-hub/`

### Credenciales por defecto en XAMPP
`config/db.php` usa `getenv()` con fallback a `localhost / root / (sin contraseña)`, por lo que funciona sin configuración adicional en XAMPP estándar.

---

## 6. Configuración de Credenciales Externas

Los archivos con claves reales no están en el repositorio. Usa los `.example` como plantilla:

**Imágenes (Cloudinary):**
```
config/cloudinary_config.example.php  →  config/cloudinary_config.php
```
Rellena `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY` y `CLOUDINARY_API_SECRET`.

**Email SMTP (PHPMailer):**
```
config/mailer_config.example.php  →  config/mailer_config.php
```
Rellena host SMTP, puerto, usuario y contraseña (compatible con Gmail con Contraseña de Aplicación).

> Si no se configuran, las imágenes se guardan en local y los emails no se envían, pero la navegación sigue siendo completamente funcional.

---

## 7. Variables de Entorno (Producción)

En producción, define estas variables de entorno en lugar de editar `config/db.php`:

| Variable | Descripción | Valor por defecto |
|---|---|---|
| `DB_HOST` | Host de la base de datos | `localhost` |
| `DB_NAME` | Nombre de la base de datos | `ark_hub` |
| `DB_USER` | Usuario de MySQL | `root` |
| `DB_PASS` | Contraseña de MySQL | *(vacío)* |

En Apache puedes definirlas en `.htaccess` o `httpd.conf`:
```apache
SetEnv DB_HOST tu_host
SetEnv DB_NAME ark_hub
SetEnv DB_USER tu_usuario
SetEnv DB_PASS tu_contraseña
```

---

## 8. Credenciales de Acceso

| Rol | Nick | Contraseña |
|---|---|---|
| Superadmin | `Anxo` | `Admin1234` |

URL de acceso: `http://localhost/ark-survival-hub/login.php`

---

## 9. Seguridad

| Vector | Mitigación |
|---|---|
| SQL Injection | PDO Prepared Statements en todas las consultas |
| XSS | `htmlspecialchars()` en toda salida dinámica |
| CSRF | Token de sesión validado en todos los formularios POST |
| Fuerza bruta | Rate limiting (bloqueo tras 5 intentos en 60s) |
| Spam | Honeypot en formularios de registro y comentarios |
| Session hijacking | `session_regenerate_id(true)` tras login |
| Baneo evasión | Verificación de estado en tiempo real en cada petición |
| Path traversal | Validación de extensión y `getimagesize()` en subidas |
| Emails bloqueados | Tabla `emails_bloqueados` verificada en login y registro |
| Permisos | Verificación de rol y permisos individuales en cada acción de admin |

---

## 10. Estructura de Archivos Clave

### `assets/data/ark_creatures.json`
Contiene datos de referencia de 30 criaturas ARK (stats base, Iw, roles, recolección, domesticación, mapas). Se carga bajo demanda en el tablón de referencia del panel de administración. Para añadir criaturas, edita el JSON siguiendo la estructura existente.

### `assets/js/stats_reference.js`
Carga el JSON y construye el tablón flotante en los formularios de insertar/editar. Al hacer clic en una fila autorellena todos los campos del formulario automáticamente, incluyendo checkboxes de roles, recolección y mapas.

### `assets/js/main.js`
Gestiona: sistema de temas dinámicos, notificaciones con polling, scroll-to-top, preview de imágenes, contador de caracteres en textareas, confirmaciones de borrado y envío de comunicados vía AJAX.

### `config/db.php`
Conexión PDO con soporte de variables de entorno para producción y fallback a valores locales para desarrollo. Incluye generación automática del token CSRF de sesión.

---

© 2026 ARK Survival Hub. Desarrollado por Anxo Berjano.
