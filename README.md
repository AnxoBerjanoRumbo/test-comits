# ARK Survival Hub - Wiki Project

Una enciclopedia dinámica y sistema de gestión sobre el universo de ARK: Survival Ascended, desarrollada como una aplicación web completa, segura y de alto rendimiento.

---

## Índice
1. [Visión General](#visión-general)
2. [Arquitectura de Base de Datos](#arquitectura-de-base-de-datos)
3. [Stack Tecnológico](#stack-tecnológico)
4. [Características Principales](#características-principales)
    * [UI/UX y Diseño](#uiux-y-diseño)
    * [Seguridad y Autenticación](#seguridad-y-autenticación)
    * [Gestión de Contenido (CRUD)](#gestión-de-contenido-crud)
    * [Panel Administrativo (RBAC)](#panel-administrativo-rbac)
5. [Seguridad Avanzada](#seguridad-avanzada)
6. [Instalación y Configuración](#instalación-y-configuración)

---

## Visión General
ARK Survival Hub es una plataforma robusta que permite a los usuarios explorar criaturas, descubrir sus localizaciones en diferentes mapas y participar mediante un sistema de comentarios. Los administradores disponen de herramientas avanzadas para la moderación y actualización del ecosistema de la wiki.

---

## Arquitectura de Base de Datos
El sistema utiliza una estructura relacional en MariaDB diseñada para la escalabilidad y la integridad de los datos:

*   **Entidades Core**: Usuarios, Dinosaurios, Mapas y Comentarios.
*   **Relaciones Relacionales**:
    *   **1:N (Uno a Muchos)**: Usuarios -> Comentarios / Dinosaurios -> Comentarios.
    *   **N:M (Muchos a Muchos)**: Dinosaurios <-> Mapas (a través de dino_mapas), permitiendo que una criatura habite múltiples regiones y que cada región albergue diversas especies.
*   **Integridad Referencial**: Implementación de eliminaciones en cascada y transacciones SQL para mantener la coherencia absoluta de los datos.

---

## Stack Tecnológico
*   **Backend**: PHP 8.x con arquitectura modular orientada a la seguridad.
*   **Acceso a Datos**: PHP Data Objects (PDO) con sentencias preparadas para blindaje contra SQL Injection.
*   **Base de Datos**: MariaDB / MySQL.
*   **Frontend**: HTML5 Semántico, CSS3 avanzado (Variables, Grid, Flexbox, Glassmorphism) y JavaScript Vanilla para interactividad fluida y validaciones.
*   **Versionado**: Git y GitHub con flujo de trabajo profesional.

---

## Características Principales

### UI/UX y Diseño
*   **Estética Premium**: Diseño Dark Mode con acentos en colores neón para una inmersión total.
*   **Vertical Card Design**: Tarjetas de dinosaurios con estilo póster, imágenes de gran tamaño, efectos de zoom dinámico y pies de página integrados con degradados.
*   **Paginación Inteligente**: Listados optimizados (9 criaturas por página) para una carga rápida y navegación organizada.
*   **Responsive**: Adaptabilidad total en todos los dispositivos mediante sistemas de rejillas flexibles.

### Seguridad y Autenticación
*   **Sistema de Cuentas**: Gestión de sesiones seguras mediante variables de sesión.
*   **Recuperación de Contraseña**: Flujo con tokens temporales de un solo uso con expiración automática.
*   **Hashing de Seguridad**: Uso de password_hash con algoritmo BCRYPT.
*   **Validación CSRF**: Protección en todos los formularios de acción mediante tokens aleatorios para prevenir ataques de falsificación de peticiones.

### Gestión de Contenido (CRUD)
*   **Panel de Administración**: Gestión de criaturas (Alta, Baja, Modificación).
*   **Subida de Archivos**: Sistema de carga de imágenes con validación de tipos y limpieza automática de archivos huérfanos al editar o borrar.
*   **Sistema de Comentarios**: Interacción social con herramientas de moderación.

### Panel Administrativo (RBAC)
*   **Control de Roles**: Diferenciación entre Usuario, Admin y SuperAdmin.
*   **Aprobación de Moderadores**: El SuperAdmin valida y activa solicitudes de administradores, asignando credenciales iniciales y gestionando permisos.

---

## Seguridad Avanzada
El proyecto implementa medidas de seguridad críticas:
1.  **Blindaje de subidas**: Validación de extensiones y verificación de contenido real de imagen mediante getimagesize para evitar RCE (ejecución remota de código).
2.  **Transacciones Atómicas**: Uso de transacciones para garantizar que los cambios en múltiples tablas se realicen por completo o se reviertan totalmente en caso de error.
3.  **Logs de Error**: Registro interno de excepciones en el servidor para evitar la exposición de datos técnicos al usuario final.

---

## Instalación y Configuración

### 1. Entorno
Clonar el repositorio dentro de la carpeta `htdocs` de XAMPP (o `www` de WAMP):
```
git clone https://github.com/AnxoBerjanoRumbo/test-comits.git ark-survival-hub
```

### 2. Base de Datos
- Abrir **phpMyAdmin** (`http://localhost/phpmyadmin`)
- Crear una base de datos llamada `ark_hub`
- Importar el archivo SQL que se encuentra en la carpeta `database/`
- La conexión ya está preconfigurada para XAMPP (usuario `root`, sin contraseña). Si tu entorno es distinto, edita `config/db.php`.

### 3. Archivos de Configuración Necesarios (⚠️ Obligatorio)

Por seguridad, las credenciales privadas **no se suben a GitHub**. Tienes que crear estos dos archivos manualmente a partir de las plantillas incluidas:

#### 3a. Cloudinary (gestión de imágenes)
1. Copia `config/cloudinary_config.example.php` y renómbralo a `config/cloudinary_config.php`
2. Crea una cuenta gratuita en [cloudinary.com](https://cloudinary.com)
3. En el Dashboard de Cloudinary, copia tu **Cloud Name**, **API Key** y **API Secret**
4. Pégalos en el archivo `config/cloudinary_config.php`

> **Nota:** Sin Cloudinary, la subida de imágenes guardará los archivos en local (`assets/img/`) como fallback automático.

#### 3b. Email / PHPMailer (envío de correos)
1. Copia `config/mailer_config.example.php` y renómbralo a `config/mailer_config.php`
2. Rellena con los datos de tu cuenta de correo SMTP
3. **Si usas Gmail**: genera una *Contraseña de Aplicación* en [myaccount.google.com](https://myaccount.google.com) → Seguridad → Contraseñas de aplicación

> **Nota:** Sin PHPMailer configurado, el sistema seguirá funcionando, pero no se enviarán correos de verificación, recuperación de contraseña ni notificaciones de sanción.

### 4. Ejecución
Accede desde el navegador a:
```
http://localhost/ark-survival-hub/
```

### 5. Cuenta Superadmin Inicial
El archivo SQL incluye un usuario superadmin predefinido. Puedes usar en `login.php`:
- **Nick**: `superadmin`
- **Contraseña**: la que esté en el script SQL importado

---
© 2025 ARK Survival Hub - Proyecto Wiki de Alto Rendimiento.