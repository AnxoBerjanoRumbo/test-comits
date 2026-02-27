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
* **Frontend:** HTML5 y CSS3 siguiendo la metodología de separación de responsabilidades.
* **Control de Versiones:** Git y GitHub.

---

## Características Implementadas
* **Navegación Dinámica:** Sistema de rutas mediante parámetros **GET** para visualizar fichas técnicas individuales (`detalle.php?id=X`).
* **Consultas Relacionales:** Uso de `INNER JOIN` para cruzar datos entre criaturas y sus localizaciones geográficas en tiempo real.
* **Diseño Responsive:** Interfaz adaptativa optimizada para dispositivos móviles y escritorio mediante **CSS Grid** y **Media Queries**.
* **Seguridad:** Consultas preparadas con `bindParam` para proteger la integridad de la base de datos.

### Motor de Búsqueda y Filtrado
* **Búsqueda por Texto:** Implementación del operador `LIKE` en SQL para localizar criaturas por nombre o especie de forma parcial.
* **Filtros por Dieta:** Menú desplegable para filtrar dinosaurios según su tipo de alimentación (Carnívoro, Herbívoro, etc.).
* **Consultas Dinámicas:** Lógica en PHP que permite combinar el buscador de texto con el filtro de dieta simultáneamente.
* **Gestión de Errores:** Sistema de control que muestra un mensaje personalizado cuando una búsqueda no devuelve resultados.

### Panel de Administración y Gestión de Datos
* **Arquitectura de Directorios:** Separación lógica creando la ruta `/admin`, aislando las operaciones de escritura y alteración de la base de datos de las vistas públicas.
* **Inserción Dinámica (POST):** Implementación de formularios seguros para dar de alta nuevas criaturas y asignarlas a sus respectivos mapas directamente desde la interfaz.
* **Transacciones SQL Avanzadas:** Uso de `beginTransaction()`, `commit()` y `rollBack()` con PDO. Esto permite insertar datos en la tabla principal (`dinosaurios`) y en la tabla intermedia (`dino_mapas`) en una sola operación atómica, garantizando la integridad referencial.
* **Validación de Duplicados:** Lógica en el servidor que ejecuta consultas previas (`SELECT COUNT`) para evitar el registro de criaturas con nombres idénticos, devolviendo alertas visuales en caso de error.
---

## Instalación y Uso
1. **Clonar** el repositorio en la carpeta `htdocs` de tu servidor local (XAMPP).
2. **Importar** el archivo SQL (ubicado en la carpeta `database/`) a través de **phpMyAdmin**.
3. **Configurar** las credenciales de acceso a la base de datos en el archivo `config/db.php`.
4. **Acceder** a `localhost/ark-survival-hub` desde cualquier navegador.