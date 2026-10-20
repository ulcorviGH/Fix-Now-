<?php
// public/logout.php
// ---------------------------------------------------------------------------
// Cierra la sesión activa y vuelve a la pantalla de login.
// Fase 8: ahora solo cierra sesión si llega un POST con token CSRF (el botón
// "Cerrar sesión" de la navbar es un formulario). Antes bastaba con abrir
// esta dirección, y un sitio ajeno podía desloguearte con una simple imagen.
// Si alguien entra por GET (escribiendo la URL), solo se lo redirige.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Sesion::csrfValido($_POST['csrf'] ?? null)) {
    AuthController::logout();
}

header('Location: index.php');
exit;
