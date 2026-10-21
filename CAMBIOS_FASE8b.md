# Cambios de la Fase 8b — qué archivo toca cada cambio

Todos los cambios están marcados en el código con el comentario **"Fase 8b (cambio X.Y)"**: buscalo con
Ctrl+Shift+F en VS Code para ver exactamente qué parte se agregó o reemplazó.

| Cambio | Archivos modificados | Archivos nuevos |
|---|---|---|
| 1.1 Ojito de contraseña | `public/index.php`, `public/registro.php`, `src/helpers/Vista.php` (método `ojoPassword`), `public/assets/css/estilos.css` | `public/assets/js/password.js` |
| 1.2 Login con fondo | `public/index.php`, `public/assets/css/estilos.css` | `public/assets/img/fondo-login.svg` |
| 1.3 Perfil en la navbar | `src/views/navbar.php`, `src/models/Trabajador.php` (`fotoDeUsuario`) | `public/mi_cuenta.php` (destino del perfil para cliente y admin) |
| 1.4 Logout al final del perfil | `src/views/navbar.php`, `public/mi_perfil.php` | `src/views/boton_logout.php`, `public/mi_cuenta.php` |
| 1.5 Botón "← Volver" | `public/chat.php`, `public/perfil_trabajador.php`, `public/contactar.php`, `public/calificar.php`, `public/assets/css/estilos.css` (clase `.btn-volver`) | — |
| 2.1 "Inicio" y "Buscar trabajador" | `src/views/navbar.php`, `public/panel_cliente.php` | — |
| 2.2 Radio + Haversine | `public/panel_cliente.php`, `src/controllers/BusquedaController.php`, `src/models/Trabajador.php`, `src/helpers/Seguridad.php` (permiso de geolocalización), `database/01_init.sql` | `public/assets/js/buscar.js`, `database/migraciones/migracion_geolocalizacion.sql`, `database/05_datos_geolocalizacion.sql` |
| 3.1 "Solicitar servicio" | `public/perfil_trabajador.php`, `src/models/Trabajador.php` | — |
| 3.2 Chat inmersivo | `public/chat.php`, `public/assets/css/estilos.css` | — |
| 3.3 Calificaciones en Mi perfil | `public/mi_perfil.php`, `public/mis_calificaciones.php` (ahora solo redirige), `public/panel_trabajador.php`, `src/views/navbar.php` | `src/views/calificaciones_trabajador.php` |
| 3.4 Trabajador como cliente | `src/helpers/Sesion.php` (`requerirRoles`), `public/panel_cliente.php`, `public/perfil_trabajador.php`, `public/contactar.php`, `public/calificar.php`, `public/mis_trabajos.php`, `public/chats.php`, `src/views/navbar.php`, `src/controllers/SolicitudController.php`, `src/controllers/BusquedaController.php`, `src/models/Trabajador.php` | — |
| 4.1 "Estadísticas" | `src/views/navbar.php` | — |
| 4.2 Filtros del admin | `src/models/Usuario.php` (`listarParaAdmin`) | — |
