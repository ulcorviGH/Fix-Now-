<?php
// public/api/chat.php
// ---------------------------------------------------------------------------
// Pequeña "API" que usa el JavaScript del chat (assets/js/chat.js).
// Responde en JSON, no en HTML.
//   GET  api/chat.php?id=SOLICITUD&desde=ULTIMO_ID_VISTO  -> mensajes nuevos
//   POST api/chat.php  (csrf, id, contenido)               -> envía un mensaje
// Está en public/api/ para separarla de las páginas visibles.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Da forma a un mensaje para el navegador, indicando si es "mío". */
function formatear(array $m, int $miId): array
{
    return [
        'id'        => (int) $m['id_mensaje'],
        'mio'       => (int) $m['id_emisor'] === $miId,
        'contenido' => $m['contenido'],
        'hora'      => date('H:i', strtotime($m['fecha_envio'])),
    ];
}

if (!Sesion::estaLogueado()) {
    responder(['ok' => false, 'error' => 'Sesión vencida. Volvé a iniciar sesión.'], 401);
}

$miId = Sesion::idUsuarioActual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        responder(['ok' => false, 'error' => 'Formulario vencido. Recargá la página.'], 403);
    }
    $resultado = ChatController::enviar($miId, (int) ($_POST['id'] ?? 0), $_POST['contenido'] ?? '');
    if (!$resultado['exito']) {
        responder(['ok' => false, 'error' => $resultado['error']], 400);
    }
    responder(['ok' => true, 'mensaje' => formatear($resultado['mensaje'], $miId)]);
}

// GET: mensajes nuevos desde el último id que ya tiene el navegador.
$nuevos = ChatController::mensajesDesde($miId, (int) ($_GET['id'] ?? 0), (int) ($_GET['desde'] ?? 0));
if ($nuevos === null) {
    responder(['ok' => false, 'error' => 'No tenés acceso a esta conversación.'], 403);
}
responder(['ok' => true, 'mensajes' => array_map(fn($m) => formatear($m, $miId), $nuevos)]);
