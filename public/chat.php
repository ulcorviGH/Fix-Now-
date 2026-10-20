<?php
// public/chat.php
// ---------------------------------------------------------------------------
// CU-08 "Enviar y recibir mensajes" + botones de contratación (CU-21) y de
// finalización (CU-22) que viven dentro de la conversación.
//     chat.php?id=ID_SOLICITUD
// Los mensajes nuevos llegan solos: assets/js/chat.js consulta api/chat.php
// cada pocos segundos (sin recargar la página).
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

<div class="container pb-5" style="max-width: 780px;">
    <a href="chats.php" class="btn btn-link px-0 mb-2">← Volver a mis chats</a>

<?php if ($chat === null): ?>
    <div class="alert alert-warning">Esta conversación no está disponible.</div>

<?php else: ?>

    <h1 class="h5 mb-3">
        Chat con <?= Vista::e($otraParte) ?>
        <?php if ($soyCliente): ?><small class="text-muted">· <?= Vista::e($chat['oficio']) ?></small><?php endif; ?>
    </h1>

    <!-- ============ ESTADO DEL TRABAJO (CU-21 / CU-22) ============ -->
    <div class="card card-body shadow-sm mb-3">
        <?php if ($finalizado): ?>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1">✅ <strong>Trabajo terminado.</strong> Ambas partes lo confirmaron.</div>
                <?php if ($soyCliente && $chat['id_calificacion'] === null): ?>
                    <a href="calificar.php?id=<?= $idSolicitud ?>" class="btn btn-warning btn-sm">★ Calificar al trabajador</a>
                <?php elseif ($soyCliente): ?>
                    <span class="badge text-bg-secondary">Ya calificaste este trabajo</span>
                <?php elseif ($chat['id_calificacion'] !== null): ?>
                    <a href="mis_calificaciones.php" class="btn btn-outline-primary btn-sm">Ver la calificación</a>
                <?php endif; ?>
            </div>

        <?php elseif ($estadoTrabajo === 'no_solicitado'): ?>
            <?php if ($soyCliente): ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="flex-grow-1 small text-muted">
                        Cuando se pongan de acuerdo en los detalles, confirmá la contratación.
                    </span>
                    <?= Vista::formAccion('contratar', $idSolicitud, 'Contratar trabajador', 'btn-success',
                        '¿Confirmás que querés contratar a este trabajador?') ?>
                </div>
            <?php else: ?>
                <span class="small text-muted">Todavía no hay un trabajo confirmado en esta conversación.</span>
            <?php endif; ?>

        <?php elseif ($estadoTrabajo === 'pendiente'): ?>
            <?php if ($soyCliente): ?>
                <span>⏳ Pediste contratar al trabajador. Está esperando su respuesta.</span>
            <?php else: ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="flex-grow-1"><strong>El cliente quiere contratarte para este trabajo.</strong></span>
                    <?= Vista::formAccion('aceptar_trabajo', $idSolicitud, 'Aceptar', 'btn-success btn-sm') ?>
                    <?= Vista::formAccion('rechazar_trabajo', $idSolicitud, 'Rechazar', 'btn-outline-danger btn-sm') ?>
                </div>
            <?php endif; ?>

        <?php elseif ($estadoTrabajo === 'rechazado'): ?>
            <?php if ($soyCliente): ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="flex-grow-1 small">El trabajador no aceptó el trabajo. Pueden seguir conversando y volver a intentarlo.</span>
                    <?= Vista::formAccion('contratar', $idSolicitud, 'Contratar trabajador', 'btn-success',
                        '¿Querés volver a pedir la contratación?') ?>
                </div>
            <?php else: ?>
                <span class="small text-muted">Rechazaste el último pedido de trabajo.</span>
            <?php endif; ?>

        <?php else: /* aceptado y todavía sin doble confirmación */ ?>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1">
                    🔧 <strong>Trabajo en curso.</strong>
                    <div class="small text-muted">
                        Cliente: <?= $chat['confirmado_cliente'] ? '✔ confirmó' : 'sin confirmar' ?> ·
                        Trabajador: <?= $chat['confirmado_trabajador'] ? '✔ confirmó' : 'sin confirmar' ?>
                    </div>
                </div>
                <?php if (!$soyCliente && !$chat['confirmado_trabajador']): ?>
                    <!-- CU-22: botón manual del trabajador -->
                    <?= Vista::formAccion('terminar_manual', $idSolicitud, 'Marcar trabajo como terminado', 'btn-primary btn-sm',
                        '¿Confirmás que este trabajo está terminado?') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============ MENSAJES ============ -->
    <div id="chat"
         data-solicitud="<?= $idSolicitud ?>"
         data-ultimo="<?= $ultimoId ?>"
         data-csrf="<?= Vista::e(Sesion::csrfToken()) ?>">

        <div id="mensajes" class="chat-caja border rounded p-3 mb-3">
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

        <form id="formMensaje" class="d-flex gap-2">
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
