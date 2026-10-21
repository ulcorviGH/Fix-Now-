<?php
// public/mis_calificaciones.php
// ---------------------------------------------------------------------------
// Fase 8b (cambio 3.3): esta pantalla YA NO EXISTE como página aparte. Las
// calificaciones (CU-12, CU-20 y CU-14) se ven y se gestionan dentro de
// "Mi perfil" (public/mi_perfil.php, sección #calificaciones).
// Se deja este archivo mínimo para que los enlaces viejos o los favoritos
// del navegador no den error: simplemente redirigen.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('trabajador');

header('Location: mi_perfil.php#calificaciones');
exit;
