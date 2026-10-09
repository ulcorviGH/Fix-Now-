# FixNow – Plataforma de servicios y oficios

Práctica Profesionalizante 2 — Corvi Ulises

## Stack
- Frontend: HTML5 + Bootstrap 5 + JavaScript
- Backend: PHP 8 (POO + PDO)
- Base de datos: MySQL 8
- Contenedores: Docker + Docker Compose

## Avance
- [x] Fase 1 – Entorno Docker + estructura
- [x] Fase 2 – Base de datos (11 tablas)
- [x] Fase 3 – Registro e inicio de sesión (CU-01, CU-02)
- [x] Fase 4 – Búsqueda y perfil de trabajador (CU-03, CU-04)
- [ ] Fase 5 – Chat y contratación (CU-06, CU-08, CU-21, CU-22)
- [ ] Fase 6 – Calificaciones (CU-07, CU-12, CU-20)
- [ ] Fase 7 – Panel de administrador (CU-13 a CU-19)

## Cómo levantar el proyecto

Parado en la carpeta donde está `docker-compose.yml`:

1. (Solo la primera vez, si clonás el repo) copiar las variables de entorno:
   `cp .env.example .env` y completar los valores.
2. Primera vez o si cambió el Dockerfile: `docker compose up -d --build`
3. Los demás días: `docker compose up -d`

- Sitio web: http://localhost:8080
- phpMyAdmin: http://localhost:8081

La PRIMERA vez que se crea la base, MySQL tarda 4-5 minutos en inicializarse.
Esperar antes de abrir el sitio.

## Usuarios de prueba (se crean solos al crear la base)

| Rol | Mail | Contraseña |
| --- | --- | --- |
| Cliente | cliente@fixnow.com | cliente123 |
| Trabajador (habilitado) | trabajador@fixnow.com | trabajador123 |
| Administrador | admin@fixnow.com | admin123 |

Los scripts `database/01_init.sql`, `02_admin_prueba.sql` y `03_datos_prueba.sql`
se ejecutan automáticamente, en ese orden, SOLO cuando la base se crea desde cero.

## Comandos útiles

| Acción | Comando |
| --- | --- |
| Levantar los contenedores | `docker compose up -d` |
| Apagar los contenedores | `docker compose down` |
| Ver los logs de PHP/Apache | `docker compose logs app --tail 20` |
| Reconstruir tras cambiar el Dockerfile | `docker compose up -d --build` |
| Borrar TODO (incluida la base) y recrearla | `docker compose down -v` |

## Estructura

```
fixnow/
├── docker-compose.yml
├── .env / .env.example
├── docker/php/Dockerfile
├── database/            scripts SQL (se ejecutan solos al crear la base)
├── src/                 código PHP interno (no visible desde el navegador)
│   ├── config/          conexión a la base
│   ├── models/          una clase por tabla
│   ├── controllers/     lógica de cada caso de uso
│   ├── helpers/         sesión y utilidades de vista
│   └── views/           fragmentos HTML compartidos (navbar)
└── public/              lo único visible por HTTP (páginas, css, js, imágenes)
```
