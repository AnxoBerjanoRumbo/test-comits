"Proyecto de Wiki de ARK en desarrollo"

## 🗄️ Base de Datos (MariaDB)
Se ha implementado la estructura inicial con 5 tablas para cumplir con los requisitos intermodulares:
- **Entidades:** Usuarios, Dinosaurios, Mapas y Comentarios.
- **Relaciones:** - 1:N entre Usuarios y Comentarios.
  - N:M entre Dinosaurios y Mapas (mediante tabla intermedia `dino_mapas`).
