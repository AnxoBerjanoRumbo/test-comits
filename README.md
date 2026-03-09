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

1.  **Entorno**: Clonar el repositorio en la carpeta htdocs de un servidor local (XAMPP/WAMP).
2.  **Base de Datos**: 
    *   Importar el archivo SQL desde la carpeta database.
    *   Asegurarse de tener un usuario superadmin inicial.
3.  **Configuración**: Editar config/db.php con las credenciales correspondientes.
4.  **Ejecución**: Acceder a la URL de localhost correspondiente en el navegador.

---
© 2024 ARK Survival Hub - Proyecto Wiki de Alto Rendimiento.