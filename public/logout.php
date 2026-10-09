<?php
// public/logout.php
// ---------------------------------------------------------------------------
// Cierra la sesión activa y vuelve a la pantalla de login.
// No muestra nada: solo ejecuta la acción y redirige.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

AuthController::logout();

header('Location: index.php');
exit;
