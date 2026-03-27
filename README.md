# ARK Survival Hub - Digital Wiki Encyclopedia

[Versión 3.0 Stable](https://github.com/AnxoBerjanoRumbo/test-comits) | [Seguridad Blindada](https://github.com/AnxoBerjanoRumbo/test-comits) | [Stack: PHP PDO MySQL](https://github.com/AnxoBerjanoRumbo/test-comits)

Una plataforma web integral diseñada para la catalogación, exploración y gestión comunitaria del universo de ARK: Survival Ascended. Este proyecto implementa una arquitectura segura, eficiente y con una experiencia de usuario optimizada para alto rendimiento.

---

## Arquitectura del Sistema

El proyecto se basa en una arquitectura modular separando la lógica de negocio (Actions) de la persistencia de datos (Config) y la interfaz de usuario (Assets/Templates).

*   Persistencia Atómica: Integridad absoluta mediante transacciones y borrado en cascada (Cascade Deletes).
*   Gestión de Medios: Sistema híbrido de almacenamiento (Cloudinary SaaS + Fallback Local) para garantizar disponibilidad total de imágenes.
*   Control de Acceso (RBAC): Diferenciación de privilegios entre Superviviente, Administrador y SuperAdmin.

---

## Seguridad Avanzada (Core Features)

*   Protección de Datos: Encriptación de contraseñas mediante algoritmo BCRYPT y protección contra Session Fixation con regeneración de IDs.
*   Neutralización de Vectores de Ataque: Blindaje contra inyección SQL (PDO Prepared Statements) y escape sistemático de salidas contra XSS (Cross-Site Scripting).
*   Medidas Anti-Spam: Implementación de Honeypot (campo trampa), Rate Limiting por IP (Control de tasa de peticiones) y validación de tokens CSRF en todos los formularios de acción.
*   Validación Email: Flujo de verificación de cuentas mediante PHPMailer para garantizar la legitimidad de los usuarios registrados.

---

## Stack Tecnológico

| Componente | Tecnología |
| :--- | :--- |
| Backend | PHP 8.1+ (Procedural Modular) |
| Base de Datos | MariaDB / MySQL (UTF-8 Latin CI) |
| Frontend | HTML5 Semántico, CSS3 (Modern Flex/Grid), JS Vanilla |
| Email Service | PHPMailer Library (SMTP Integration) |
| Cloud Service | Cloudinary API (Media Management) |

---

## Guía de Instalación (Entorno Local)

### 1. Requisitos Previos
*   Servidor web (Apache/NGINX) con PHP 8.1+ y soporte PDO.
*   Gestor de base de datos (MariaDB/MySQL).
*   Recomendado: XAMPP para entornos Windows.

### 2. Configuración de Base de Datos
1. Acceda a su interfaz de gestión SQL (phpMyAdmin).
2. Cree una base de datos denominada ark_hub.
3. Importe el archivo database/ark_hub.sql incluido en este repositorio.
4. Verifique la conexión en config/db.php.

### 3. Configuración del Entorno (Variables Privadas)
Por motivos de seguridad, los archivos de configuración sensibles han sido excluidos. Siga estos pasos para el despliegue:

*   Imágenes (Cloudinary): Copie config/cloudinary_config.example.php a config/cloudinary_config.php y complete sus credenciales.
*   Correo (SMTP): Copie config/mailer_config.example.php a config/mailer_config.php y configure su servidor SMTP (Ej: Gmail App Password).

---

## Panel de Control (Acceso Inicial)

Para las pruebas de corrección, el script SQL incluye una cuenta de nivel SuperAdministrador con todos los privilegios activos:

*   Identificador: Anxo
*   Credential: Admin1234 (Se recomienda cambiarla inmediatamente al iniciar sesión)

---

## Características de Gestión 

*   Dino-Mapa Integrado: Relación Muchos-a-Muchos entre especies y localizaciones geográficas.
*   Sistema de Moderación: Herramientas integradas para Baneo Temporal, Expulsión Total y Gestión de Lista Negra (Blacklist).
*   Comentarios en Hilo: Sistema jerárquico de respuestas con moderación activa y notificaciones automáticas.

---
© 2025 ARK Survival Hub - Digital Wiki Encyclopedia. Desarrollado por Anxo Berjano.