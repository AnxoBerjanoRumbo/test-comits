# ARK Survival Hub - Digital Wiki Encyclopedia

Manual de documentacion técnica y guía de despliegue para el entorno de evaluación.

---

## Indice de Contenido
1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Requisitos del Sistema](#requisitos-del-sistema)
4. [Guía de Instalación Paso a Paso](#guía-de-instalación-paso-a-paso)
5. [Configuración de Credenciales Externas](#configuración-de-credenciales-externas)
6. [Credenciales de Acceso para Evaluación](#credenciales-de-acceso-para-evaluación)
7. [Seguridad y Protección de Datos](#seguridad-y-protección-de-datos)

---

<a name="introducción"></a>
## 1. Introducción
ARK Survival Hub es una plataforma web desarrollada en PHP nativo diseñada para la gestión de una enciclopedia digital sobre el universo de ARK: Survival Ascended. El sistema permite la gestión de criaturas, mapas de avistamiento y un sistema social de comentarios moderado.

<a name="arquitectura-del-sistema"></a>
## 2. Arquitectura del Sistema
El proyecto sigue una estructura modular para facilitar la escalabilidad y el mantenimiento:
- **Root**: Archivos de vista principal (index, login, registro, perfil).
- **actions/**: Procesadores de lógica de servidor (POST/peticiones).
- **config/**: Archivos de conexión a base de datos y helpers de servicios externos.
- **includes/**: Componentes reutilizables de interfaz (header, footer).
- **database/**: Scripts de estructura e integridad de datos.
- **assets/**: Recursos estáticos (CSS, JS, Imágenes).

<a name="requisitos-del-sistema"></a>
## 3. Requisitos del Sistema
Para el correcto funcionamiento de la plataforma, el entorno debe cumplir con:
- Servidor Web: Apache 2.4 o superior (incluido en XAMPP).
- Lenguaje: PHP 8.1 o superior.
- Extensiones PHP requeridas: pdo_mysql, mbstring, curl, gd.
- Gestor de Base de Datos: MySQL / MariaDB (motor InnoDB).

<a name="guía-de-instalación-paso-a-paso"></a>
## 4. Guía de Instalación Paso a Paso (XAMPP)

Siga estas instrucciones detalladas para desplegar el proyecto en su entorno local:

1. **Localización de archivos**: Copie la carpeta completa del proyecto en el directorio de su servidor web (habitualmente `C:\xampp\htdocs\ark-survival-hub-main`).
2. **Arranque de Servicios**: Inicie los módulos de **Apache** y **MySQL** desde el Panel de Control de XAMPP.
3. **Importación de la Base de Datos**:
   - Acceda a su navegador y entre en la URL: `http://localhost/phpmyadmin`.
   - Cree una nueva base de datos llamada exactamente `ark_hub`.
   - Seleccione la base de datos recién creada y pulse sobre la pestaña superior **Importar**.
   - Seleccione el archivo localizado en su proyecto: `database/ark_hub.sql`.
   - Pulse el botón "Importar" al final de la página para cargar la estructura y los datos de prueba iniciales.
4. **Validación de Conexión**: Verifique que el archivo `config/db.php` tiene las credenciales correctas de su MySQL (por defecto usuario 'root' y sin contraseña).

<a name="configuración-de-credenciales-externas"></a>
## 5. Configuración de Credenciales Externas (Archivos .example)
Por seguridad, los archivos que contienen contraseñas reales (Email y Cloudinary) no se suben al repositorio. El examinador debe configurar los suyos si desea probar estas funcionalidades específicas:

- **Imágenes (Cloudinary)**: Renombre el archivo `config/cloudinary_config.example.php` a `config/cloudinary_config.php` y complete sus claves API.
- **Correo SMTP (PHPMailer)**: Renombre el archivo `config/mailer_config.example.php` a `config/mailer_config.php` e introduzca su servidor SMTP (Ej: Gmail con Contraseña de Aplicación).

*Nota: Si no se configuran estos archivos, el sistema usará almacenamiento local para las imágenes y las funciones de email no enviarán correos reales, pero la navegación seguirá operativa.*

<a name="credenciales-de-acceso-para-evaluación"></a>
## 6. Credenciales de Acceso para Evaluación
Para realizar las pruebas de corrección de privilegios y administración, utilice la siguiente cuenta de Superadministrador:

- **URL de acceso**: `http://localhost/ark-survival-hub-main/login.php`
- **Identificador (Nick/Email)**: `Anxo`
- **Contraseña**: `Admin1234`

<a name="seguridad-y-protección-de-datos"></a>
## 7. Seguridad y Protección de Datos
La plataforma ha sido auditada para prevenir los vectores de ataque más comunes en aplicaciones web:
- **Inyección SQL**: Mitigada mediante el uso sistemático de PDO Prepared Statements.
- **Cross-Site Scripting (XSS)**: Neutralizado mediante el escape de toda salida de datos dinámica con `htmlspecialchars()`.
- **Session Hijacking**: Implementación de regeneración de IDs de sesión y blindaje contra baneo en tiempo real.
- **Ataques de Fuerza Bruta**: Implementación de Rate Limiting y Honeypots en los formularios de registro y acceso.

---
© 2025 ARK Survival Hub - Digital Wiki Encyclopedia. Desarrollado por Anxo Berjano.