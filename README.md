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
