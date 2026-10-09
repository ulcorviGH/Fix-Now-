<?php
// public/accion.php
// ---------------------------------------------------------------------------
// Recibe los botones de acción de toda la aplicación (aceptar / rechazar
// solicitudes, contratar, marcar terminado, responder "¿ya se completó?").
// No muestra nada: ejecuta la acción, deja un aviso y vuelve a la página
// desde la que se envió el botón.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirLogin();

// Solo se acepta POST (nunca una acción por simple enlace/URL).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel_' . Sesion::rolActual() . '.php');
    exit;
}

// A dónde volver: solo se aceptan direcciones internas simples tipo
// "chat.php?id=3". Si viene cualquier otra cosa se ignora. Esto evita que
// alguien arme un formulario que redirija a un sitio externo.
$volver = $_POST['volver'] ?? '';
if (!preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%\-]*)?$/', $volver)) {
    $volver = 'panel_' . Sesion::rolActual() . '.php';
}

if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
    Sesion::flash('danger', 'El formulario venció. Intentá de nuevo.');
    header('Location: ' . $volver);
    exit;
}

$resultado = SolicitudController::ejecutar(
    Sesion::idUsuarioActual(),
    Sesion::rolActual(),
    $_POST['accion'] ?? '',
    (int) ($_POST['id_solicitud'] ?? 0)
);

Sesion::flash($resultado['exito'] ? 'success' : 'danger', $resultado['mensaje']);
header('Location: ' . $volver);
exit;
