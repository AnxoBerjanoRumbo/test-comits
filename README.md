# ARK Survival Hub - Wiki Project

Proyecto de Wiki dinámica sobre el universo de **ARK: Survival Ascended**, desarrollada como proyecto integrador intermodular.

---

## Base de Datos (MariaDB)
Se ha implementado una estructura relacional sólida para gestionar el contenido y la interacción:

* **Entidades:** Usuarios, Dinosaurios, Mapas y Comentarios.
* **Relaciones:**
    * **1:N (Uno a Muchos):** Entre Usuarios y Comentarios (un usuario puede realizar múltiples aportaciones).
    * **N:M (Muchos a Muchos):** Entre Dinosaurios y Mapas mediante la tabla intermedia `dino_mapas`, permitiendo gestionar avistamientos de una criatura en múltiples localizaciones.

---

## Tecnologías Utilizadas
* **Backend:** PHP 8.x utilizando **PDO** (PHP Data Objects) para una conexión segura y preparada contra inyecciones SQL.
* **Base de Datos:** MariaDB (XAMPP).
* **Frontend:** HTML5, CSS3 Avanzado (Variables globales, Grid, Flexbox) y JavaScript Vanilla (Validación de formularios en tiempo real).
* **Control de Versiones:** Git y GitHub.

---

## Características Implementadas

### Interfaz de Usuario UI/UX
* **Diseño Premium y Oscuro (Dark Mode):** Interfaz inmersiva con colores de acento vibrantes (#00ffcc, #ffcc00), variables CSS y tipografía moderna (Outfit).
* **Componentes Interactivos:** Tarjetas de criaturas y de administración con efectos flotantes (hover), sombras dinámicas y transiciones suaves.
* **Diseño Responsive:** Interfaz completamente adaptativa optimizada para dispositivos móviles y escritorio mediante CSS Grid y Media Queries.

### Motor de Búsqueda y Filtrado
* **Búsqueda por Texto:** Implementación del operador LIKE en SQL para localizar criaturas por nombre o especie de forma parcial.
* **Filtros por Dieta:** Menú desplegable para filtrar dinosaurios según su tipo de alimentación (Carnívoro, Herbívoro, etc.).
* **Consultas Dinámicas:** Lógica en PHP que permite combinar el buscador de texto con el filtro de dieta simultáneamente.

### Sistema de Usuarios y Seguridad (NUEVO)
* **Autenticación Completa:** Sistema de Login y Logout utilizando Variables de Sesión ($_SESSION) protegiendo las rutas importantes (Auth Guards).
* **Registro con Validación:** 
  * Confirmación de contraseña en frontend (JavaScript) mostrando errores en tiempo real y evitando envíos erróneos.
  * Doble verificación de seguridad en backend (PHP).
* **Control de Roles (RBAC):** Sistema escalonado con roles de usuario, admin y superadmin.

### Panel de Administración y SuperAdmin
* **Gestión de Contenido (CRUD Completo):**
  * **Inserción Dinámica (POST):** Formularios seguros para dar de alta nuevas criaturas asignando múltiples mapas vía transacciones SQL atómicas (beginTransaction, commit, rollBack).
  * **Eliminación Segura:** Opción de extinguir criaturas eliminando primero sus referencias en tablas intermedias para mantener la integridad referencial.
* **Flujo de Aprobación de Moderadores (SuperAdmin):**
  * Validación Estricta: Si un usuario quiere ser admin, su nick debe seguir estrictamente un formato validado por Expresiones Regulares (admin0 hasta admin99).
  * Las cuentas admin se registran bloqueadas (sin contraseña).
  * Panel exclusivo para el superadmin mostrando tarjetas de solicitudes pendientes.
  * Opciones para Asignar Contraseña y Activar o Cancelar y Borrar de la BD en un solo clic.

---

## Instalación y Uso
1. **Clonar** el repositorio en la carpeta htdocs de tu servidor local (XAMPP).
2. **Importar** el archivo SQL (ubicado en la carpeta database/) a través de phpMyAdmin. (Asegúrate de agregar al superadmin directamente en base de datos si es la primera vez).
3. **Configurar** las credenciales de acceso a la base de datos en el archivo config/db.php.
4. **Acceder** a localhost/ark-survival-hub desde el navegador.