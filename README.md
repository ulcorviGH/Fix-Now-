# FixNow – Plataforma de servicios y oficios

Plataforma web para contratar trabajadores de oficios (plomeros, electricistas, gasistas…).
Tiene tres roles: **Cliente**, **Trabajador** y **Administrador**.
Práctica Profesionalizante 2 — Corvi Ulises.

## Stack

| Capa | Tecnología |
|---|---|
| Front | HTML5 + Bootstrap 5 (CDN) + JavaScript |
| Back | PHP 8.2 (POO + PDO, consultas preparadas) |
| Base de datos | MySQL 8 (administrable con phpMyAdmin) |
| Servidor | Apache, dentro de Docker Compose (3 contenedores) |
| Control de versiones | Git + GitHub |

## Estado del proyecto

- [x] Fases 1-2: Docker + base de datos (11 tablas)
- [x] Fase 3: registro e inicio de sesión (CU-01, CU-02)
- [x] Fase 4: lista por cercanía, buscador y perfil público (CU-03, CU-04)
- [x] Fase 5: contacto, chat, contratación y finalización (CU-06, CU-08, CU-11, CU-21, CU-22)
- [x] Fase 6: calificaciones y respuestas (CU-07, CU-12, CU-20)
- [x] Fase 7: panel del Administrador y "Mi perfil" del Trabajador (CU-09, 10, 13 a 19)
- [x] Fase 8: seguridad, pulido visual y documentación final (los 22 casos de uso implementados)
- [x] **Fase 8b: refinamientos de diseño y UX** — búsqueda por radio con geolocalización, perfil en la barra superior, chat inmersivo, calificaciones dentro de "Mi perfil", trabajador que también puede contratar, login con fondo e ícono de contraseña, filtros del admin

## Instalación desde cero (una computadora nueva)

Requisitos: **Docker Desktop** (con WSL2 en Windows) y **Git**.

```powershell
# 1) Clonar el repositorio y entrar a la carpeta (la que contiene docker-compose.yml)
git clone https://github.com/TU_USUARIO/fixnow.git
cd fixnow

# 2) Crear el archivo de configuración a partir de la plantilla y editarlo
copy .env.example .env        # en Linux/Mac: cp .env.example .env
#    Abrí .env y reemplazá los "cambiar_esto" por contraseñas propias.

# 3) Levantar todo (la primera vez construye la imagen de PHP)
docker compose up -d --build
```

**Esperá 4-5 minutos** la primera vez: MySQL crea la base y ejecuta los scripts de
`database/` (01 a 04) solo. Podés ver el avance con `docker compose logs -f db`
(listo cuando aparece "ready for connections" por segunda vez).

| Qué | Dónde |
|---|---|
| La aplicación | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 (usuario `root` y la clave de `MYSQL_ROOT_PASSWORD`) |

## Usuarios de prueba (se crean solos)

| Rol | Mail | Contraseña | Notas |
|---|---|---|---|
| Administrador | admin@fixnow.com | admin123 | |
| Cliente | cliente@fixnow.com | cliente123 | Laura, San Carlos Centro |
| Cliente | bruno@fixnow.com | cliente123 | para probar bloquear/eliminar |
| Trabajador | trabajador@fixnow.com | trabajador123 | Carlos, plomero, habilitado |
| Trabajador | rocio@fixnow.com | trabajador123 | electricista, con una reseña reportada |
| Trabajador | nicolas@fixnow.com | trabajador123 | pendiente de habilitación |
| Trabajador | ariana@fixnow.com | trabajador123 | pendiente de habilitación |

> Son solo para desarrollo y demostración. **Cambiá o eliminá estas cuentas antes de publicar el sitio.**

## Guía rápida de prueba

1. **Cliente → trabajador:** entrá como `cliente@`, abrí el perfil de Carlos, "Enviar mensaje".
2. Entrá como `trabajador@` → *Solicitudes* → **Aceptar** el chat. Chateen entre los dos.
3. Como cliente, en el chat: **Contratar trabajador**. Como trabajador: aceptar en *Solicitudes de trabajo*.
4. El trabajador pulsa **Marcar trabajo como terminado**; el cliente responde "Sí" a la pregunta
   que aparece arriba. Recién ahí se habilita **Calificar**.
5. Como trabajador: *Mis calificaciones* → responder o reportar la reseña.
6. Como `admin@`: habilitar a Nicolás, moderar la reseña reportada, ver *Estadísticas*.
7. **Búsqueda por radio:** en *Inicio* mové el slider de km. El navegador te pide la ubicación; si estás
   lejos de San Carlos o no querés dar permiso, tildá *"Usar mi localidad en vez del GPS"*.
8. **Trabajador como cliente:** entrá como `trabajador@` → *Inicio* → pedile un servicio a Rocío
   (sin crear otra cuenta). Un trabajador nunca se ve a sí mismo en las búsquedas.
9. **Cerrar sesión:** está al final de tu perfil (*Mi perfil* para el trabajador; *Mi cuenta* para cliente
   y administrador), en la miniatura de arriba a la derecha.

Para probar rápido la pregunta "¿Ya se completó?" poné `DIAS_PRIMERA_PREGUNTA = 0` en
`src/config/Config.php` (el valor real es 2 días).

## Comandos útiles (parado en la carpeta de `docker-compose.yml`)

| Comando | Para qué |
|---|---|
| `docker compose up -d` | Encender (uso diario) |
| `docker compose up -d --build` | Encender reconstruyendo la imagen (solo si cambió el Dockerfile) |
| `docker compose stop` | Apagar sin borrar nada |
| `docker compose logs -f app` | Ver los errores de PHP (en pantalla ya no se muestran) |
| `docker compose down -v` | **BORRA LA BASE DE DATOS** y empieza de cero |

## Si tenés una base ya creada (actualizar sin borrar)

La **Fase 8b agrega dos columnas** (`USUARIO.latitud` y `USUARIO.longitud`) para la búsqueda por radio.
Con una base existente es **obligatorio** correr, en phpMyAdmin (pestaña SQL, con la base `fixnow`
seleccionada), el archivo **`database/migraciones/migracion_geolocalizacion.sql`**. Es seguro correrlo
más de una vez y además carga coordenadas simuladas a los trabajadores de prueba. Sin ese paso el
buscador da error. Con una base **nueva** no hace falta: Docker ya ejecuta `01` a `05`.

La Fase 8 no cambió el esquema y solo modificó el Dockerfile (hay que correr una vez
`docker compose up -d --build`). Los demás scripts de `database/migraciones/` son para bases viejas:
`fase5_alter.sql`, `fase7_alter.sql`, `fase7b_calificaciones_eliminadas.sql`.

## Seguridad (resumen)

- Contraseñas con bcrypt; bloqueo temporal tras 3 intentos fallidos; mínimo 8 caracteres.
- Token CSRF en **todos** los formularios (incluidos login, registro y cerrar sesión).
- Consultas PDO preparadas; todo texto mostrado pasa por `Vista::e()` (anti-XSS).
- Permisos validados **en el servidor** en cada controller (no solo ocultando botones).
- Cookie de sesión `HttpOnly` + `SameSite=Lax`; cabeceras `CSP`, `X-Frame-Options`, `nosniff`, etc.
- Fotos: se valida el contenido real, el servidor inventa el nombre, y Apache no ejecuta PHP en la carpeta de fotos.
- Errores de PHP a log (no a pantalla); Apache sin versión visible ni listado de carpetas.
- Límite de mensajes de chat por minuto.
- La geolocalización del navegador solo se permite para el propio sitio (cabecera `Permissions-Policy`).

## Estructura

```
fixnow/
├── docker-compose.yml          # los 3 contenedores
├── .env / .env.example         # claves (el .env NO se sube a Git)
├── docker/php/                 # Dockerfile, uploads.ini, seguridad.ini, zz-fixnow-seguridad.conf
├── database/                   # 01_init.sql … 05_datos_geolocalizacion.sql (Docker los ejecuta solos)
│   └── migraciones/            # SQL manuales para bases existentes
├── src/                        # código interno (NO visible por HTTP)
│   ├── config/                 # Database.php (PDO), Config.php (reglas y topes)
│   ├── models/                 # una clase por tabla: SOLO SQL
│   ├── controllers/            # reglas de negocio y permisos
│   ├── helpers/                # Sesion, Vista, Archivos, Seguridad
│   ├── views/                  # navbar.php, boton_logout.php, calificaciones_trabajador.php
│   └── bootstrap.php           # único require de todas las páginas
└── public/                     # raíz web de Apache
    ├── *.php                   # pantallas (cortas: llaman al controller y dibujan HTML)
    ├── accion.php / accion_admin.php   # despachadores de botones
    ├── api/chat.php            # JSON del chat
    └── assets/{css,js,img}
```

## Trabajo futuro (fuera del alcance actual)

Mails reales de aviso, notificaciones push, pagos online, geolocalización avanzada (mapas),
tareas programadas (cron) para las preguntas de finalización, límite de intentos por IP,
verificación de identidad de trabajadores, y chat en tiempo real con WebSockets.
