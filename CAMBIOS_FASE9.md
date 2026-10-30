# Cambios de la Fase 9 — qué archivo toca cada punto

Todo lo nuevo está marcado en el código con el comentario **"Fase 9"** (buscalo con Ctrl+Shift+F).

| Punto | Archivos modificados | Archivos nuevos |
|---|---|---|
| 1 Páginas legales | `public/index.php`, `public/registro.php` | `public/terminos.php`, `public/privacidad.php`, `public/assets/js/volver.js` |
| 2 Chat pantalla completa | `public/chat.php`, `public/assets/css/estilos.css` | — |
| 3 Mapa interactivo | `public/panel_cliente.php`, `public/assets/js/buscar.js` (reescrito), `public/assets/css/estilos.css` | — (Leaflet se carga desde jsDelivr) |
| 4 Cuenta: salir discreto, eliminar, editar perfil, estadísticas | `public/mi_cuenta.php` (reescrito), `public/mi_perfil.php`, `src/views/boton_logout.php`, `src/models/Usuario.php`, `src/models/Estadistica.php`, `src/config/Config.php`, `src/bootstrap.php` | `src/controllers/CuentaController.php`, `public/eliminar_cuenta.php`, `src/views/zona_peligro.php`, `public/assets/js/cuenta.js` |
| 5 Navbar sin "Mi perfil" | `src/views/navbar.php` | — |
| 6 Mis contrataciones | `src/views/navbar.php`, `public/mis_trabajos.php` | — |
| 7 Oficios múltiples y localidades | `src/controllers/AuthController.php`, `src/controllers/TrabajadorController.php`, `src/models/Trabajador.php`, `src/models/Localidad.php`, `public/registro.php`, `public/mi_perfil.php`, `database/01_init.sql` | `src/models/Oficio.php`, `src/controllers/CatalogoController.php`, `public/api/localidades.php`, `src/views/selector_localidades.php`, `public/assets/js/localidades.js`, `database/06_catalogos_fase9.sql`, `database/migraciones/migracion_fase9.sql` |
| 8 Buscador del admin | `src/views/navbar.php` (**causa real**), `public/admin_usuarios.php` | — |

**Importante con una base existente:** correr `database/migraciones/migracion_fase9.sql` (y antes
`migracion_geolocalizacion.sql` si todavía no lo hiciste).
