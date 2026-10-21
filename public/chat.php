<?php
// public/chat.php
// ---------------------------------------------------------------------------
// CU-08 "Enviar y recibir mensajes" + botones de contratación (CU-21) y de
// finalización (CU-22) que viven dentro de la conversación.
//     chat.php?id=ID_SOLICITUD
// Los mensajes nuevos llegan solos: assets/js/chat.js consulta api/chat.php
// cada pocos segundos (sin recargar la página).
// Fase 8b (cambio 3.2): el chat es una sola tarjeta alta e inmersiva. Se quitó el
// título grande y el banner de estado; el estado y los botones (por ejemplo el verde
// "Contratar trabajador") viven en la cabecera compacta del propio chat.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirLogin();
if (!in_array(Sesion::rolActual(), ['cliente', 'trabajador'], true)) {
    header('Location: panel_' . Sesion::rolActual() . '.php');
    exit;
}

$miId = Sesion::idUsuarioActual();
$idSolicitud = (int) ($_GET['id'] ?? 0);

// null si no existe, si el contacto no fue aceptado o si no sos parte de la conversación.
$chat = ChatController::abrir($miId, $idSolicitud);

if ($chat !== null) {
    $mensajes = ChatController::mensajesDesde($miId, $idSolicitud, 0);
    $ultimoId = $mensajes ? (int) end($mensajes)['id_mensaje'] : 0;

    $soyCliente = $chat['mi_rol'] === 'cliente';
    $otraParte = $soyCliente
        ? $chat['trabajador_nombre'] . ' ' . $chat['trabajador_apellido']
        : $chat['cliente_nombre'] . ' ' . $chat['cliente_apellido'];

    $estadoTrabajo = $chat['estado_trabajo'];
    $finalizado = (bool) $chat['confirmado_cliente'] && (bool) $chat['confirmado_trabajador'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-3" style="max-width: 900px;">

<?php if ($chat === null): ?>
    <a href="chats.php" class="btn-volver mb-2">← Volver</a>
    <div class="alert alert-warning">Esta conversación no está disponible.</div>

<?php else: ?>

    <!-- Una sola tarjeta: cabecera compacta + mensajes + caja de texto.
         El atributo data-* lo lee assets/js/chat.js. -->
    <div id="chat" class="chat-panel"
         data-solicitud="<?= $idSolicitud ?>"
         data-ultimo="<?= $ultimoId ?>"
         data-csrf="<?= Vista::e(Sesion::csrfToken()) ?>">

        <!-- ============ CABECERA COMPACTA (con estado y botones de CU-21 / CU-22) ============ -->
        <div class="chat-cabecera">
            <a href="chats.php" class="btn-volver">← Volver</a>
            <?= Vista::avatar(null,
                $soyCliente ? $chat['trabajador_nombre'] : $chat['cliente_nombre'],
                $soyCliente ? $chat['trabajador_apellido'] : $chat['cliente_apellido'], 40) ?>
            <div class="chat-titulo">
                <div class="fw-semibold text-truncate"><?= Vista::e($otraParte) ?></div>
                <div class="small text-muted text-truncate">
                    <?php if ($soyCliente): ?><?= Vista::e($chat['oficio']) ?> · <?php endif; ?>
                    <?php if ($finalizado): ?>
                        ✅ Trabajo terminado
                    <?php elseif ($estadoTrabajo === 'no_solicitado'): ?>
                        Sin trabajo confirmado todavía
                    <?php elseif ($estadoTrabajo === 'pendiente'): ?>
                        ⏳ Contratación pendiente de respuesta
                    <?php elseif ($estadoTrabajo === 'rechazado'): ?>
                        Trabajo no aceptado
                    <?php else: ?>
                        🔧 En curso · cliente <?= $chat['confirmado_cliente'] ? '✔' : '…' ?> · trabajador <?= $chat['confirmado_trabajador'] ? '✔' : '…' ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botones compactos según el estado (siempre a la derecha de la cabecera) -->
            <div class="chat-acciones">
                <?php if ($finalizado): ?>
                    <?php if ($soyCliente && $chat['id_calificacion'] === null): ?>
                        <a href="calificar.php?id=<?= $idSolicitud ?>" class="btn btn-warning btn-sm">★ Calificar</a>
                    <?php elseif ($soyCliente): ?>
                        <span class="badge text-bg-secondary">Ya calificaste</span>
                    <?php elseif ($chat['id_calificacion'] !== null): ?>
                        <a href="mi_perfil.php#calificaciones" class="btn btn-outline-primary btn-sm">Ver calificación</a>
                    <?php endif; ?>

                <?php elseif ($estadoTrabajo === 'no_solicitado' || $estadoTrabajo === 'rechazado'): ?>
                    <?php if ($soyCliente): ?>
                        <!-- CU-21: el botón verde "Contratar trabajador", ahora integrado en la cabecera -->
                        <?= Vista::formAccion('contratar', $idSolicitud, 'Contratar trabajador', 'btn-success btn-sm',
                            '¿Confirmás que querés contratar a este trabajador?') ?>
                    <?php endif; ?>

                <?php elseif ($estadoTrabajo === 'pendiente'): ?>
                    <?php if (!$soyCliente): ?>
                        <?= Vista::formAccion('aceptar_trabajo', $idSolicitud, 'Aceptar', 'btn-success btn-sm') ?>
                        <?= Vista::formAccion('rechazar_trabajo', $idSolicitud, 'Rechazar', 'btn-outline-danger btn-sm') ?>
                    <?php endif; ?>

                <?php else: /* aceptado y todavía sin doble confirmación */ ?>
                    <?php if (!$soyCliente && !$chat['confirmado_trabajador']): ?>
                        <!-- CU-22: botón manual del trabajador -->
                        <?= Vista::formAccion('terminar_manual', $idSolicitud, 'Marcar como terminado', 'btn-primary btn-sm',
                            '¿Confirmás que este trabajo está terminado?') ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ MENSAJES ============ -->
        <div id="mensajes" class="chat-caja">
            <!-- El mensaje inicial de la solicitud (CU-06) es la primera "burbuja" del cliente. -->
            <div class="msg <?= $soyCliente ? 'mio' : 'otro' ?>">
                <div class="burbuja">
                    <?= Vista::e($chat['mensaje_inicial']) ?>
                    <div class="hora"><?= Vista::e(date('H:i', strtotime($chat['fecha_creacion']))) ?></div>
                </div>
            </div>

            <?php foreach ($mensajes as $m): ?>
                <div class="msg <?= (int) $m['id_emisor'] === $miId ? 'mio' : 'otro' ?>">
                    <div class="burbuja">
                        <?= Vista::e($m['contenido']) ?>
                        <div class="hora"><?= Vista::e(date('H:i', strtotime($m['fecha_envio']))) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="formMensaje" class="chat-pie d-flex gap-2">
            <input type="text" id="textoMensaje" class="form-control" maxlength="2000"
                   placeholder="Escribí un mensaje…" autocomplete="off">
            <!-- CU-08 excepción 3.1: deshabilitado mientras esté vacío -->
            <button type="submit" id="botonEnviar" class="btn btn-primary" disabled>Enviar</button>
        </form>
    </div>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/chat.js"></script>
</body>
</html>
