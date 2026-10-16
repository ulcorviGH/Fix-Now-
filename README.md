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
- [x] Fase 5 – Contacto, chat y contratación (CU-06, CU-08, CU-11, CU-21, CU-22)
- [x] Fase 6 – Calificaciones (CU-07, CU-12, CU-20)
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

## Cómo probar el flujo de la Fase 5

1. Cliente (`cliente@fixnow.com`): buscar → perfil de Carlos → **Enviar mensaje**.
2. Trabajador (`trabajador@fixnow.com`, en otra ventana o en incógnito): **Solicitudes** → Aceptar.
3. Los dos entran al **chat** y se escriben (los mensajes llegan solos cada 4 segundos).
4. Cliente: **Contratar trabajador**. Trabajador: Solicitudes → *Solicitudes de trabajo* → Aceptar.
5. La pregunta "¿Ya se completó el trabajo?" aparece a los 2 días. Para probarla ya,
   en `src/config/Config.php` poné `DIAS_PRIMERA_PREGUNTA = 0` (y volvelo a 2 después).
6. Cuando ambos confirman, el trabajo suma a "Trabajos completados" del perfil.

## Cómo probar la Fase 6 (calificaciones)

1. Completá un trabajo como en la Fase 5 (contratar → aceptar → "Marcar trabajo como terminado" del trabajador).
2. Cliente: al marcarlo el trabajador, le aparece enseguida "¿Ya se completó el trabajo?" → **Sí**.
3. Cliente: **Mis trabajos** → **★ Calificar** → elegir estrellas (+ comentario opcional) → Publicar.
4. La reseña aparece en el perfil público y el promedio se actualiza solo.
5. Trabajador: **Mis calificaciones** → **Responder** (o **Editar respuesta**).

## Fase 7 — Panel del Administrador y perfil del Trabajador

**Administrador** (`admin@fixnow.com` / `admin123`): menú con globitos de pendientes.
- **Habilitar trabajadores** (CU-18): habilitar o rechazar (con motivo).
- **Usuarios** (CU-13): filtrar, bloquear, activar y eliminar.
- **Moderar contenido** (CU-15 + CU-19): servicios e imágenes pendientes, aprobar o rechazar (con motivo).
- **Calificaciones** (CU-14): reseñas reportadas por trabajadores, eliminar o mantener.
- **Reportes** (CU-16): métricas calculadas en el momento.

**Trabajador** (`trabajador@fixnow.com` / `trabajador123`): **Mi perfil** (CU-09, CU-10, CU-17) y botón **Reportar** en "Mis calificaciones".

Otros trabajadores de prueba (contraseña `trabajador123`): `nicolas@fixnow.com` y `ariana@fixnow.com`
(pendientes de habilitación), `rocio@fixnow.com` (habilitada, con una reseña reportada).
Cliente extra: `bruno@fixnow.com` / `cliente123`.

**Base ya creada:** correr en phpMyAdmin, en este orden, `database/migraciones/fase7_alter.sql`,
`database/migraciones/fase7b_calificaciones_eliminadas.sql` y luego `database/04_datos_prueba_fase7.sql`. **Dockerfile nuevo línea de subida de fotos:**
`docker compose up -d --build` una vez.

**Regla de bajas de calificaciones (CU-14):** si el admin elimina una reseña, el cliente puede volver a
calificar ese trabajo una vez; si elimina una segunda reseña del mismo cliente hacia el mismo trabajador, ya no
puede calificarlo más (`Config::MAX_BAJAS_CALIFICACION`).

**Probar el bloqueo de login:** en `src/config/Config.php`, `MINUTOS_BLOQUEO_LOGIN = 1`.

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
