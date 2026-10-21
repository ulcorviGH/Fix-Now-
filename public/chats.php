<?php
// public/chats.php
// ---------------------------------------------------------------------------
// Sección "Chats" del menú (CU-08 paso 1), separada de "Solicitudes".
//   - CLIENTE: ve sus conversaciones activas y también el estado de las
//     solicitudes que envió (pendiente / rechazada). Así se entera del
//     resultado (CU-06 paso 7).
//   - TRABAJADOR: ve sus conversaciones activas (los pedidos nuevos
//     se responden en "Solicitudes"). Fase 8b (cambio 3.4): además ve las
//     conversaciones donde él es el CLIENTE (cuando contrata a otro trabajador).
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirLogin();
$rol = Sesion::rolActual();
if (!in_array($rol, ['cliente', 'trabajador'], true)) {
    header('Location: panel_' . $rol . '.php');
    exit;
}

$yo = Sesion::idUsuarioActual();
$modeloSolicitud = new Solicitud(Database::getConexion());
$todas = $modeloSolicitud->listarPorUsuario($yo, $rol);
if ($rol === 'trabajador') {
    // Fase 8b: se suman las solicitudes donde este trabajador actúa como cliente.
    $todas = array_merge($todas, $modeloSolicitud->listarPorUsuario($yo, 'cliente'));
    usort($todas, fn($a, $b) => (int) $b['id_solicitud'] <=> (int) $a['id_solicitud']);
}

$activas = array_filter($todas, fn($s) => $s['estado_contacto'] === 'aceptada');
// "Solicitudes enviadas": solo las que YO envié como cliente (en el rol de trabajador
// las solicitudes recibidas se responden en la pantalla "Solicitudes").
$enviadas = array_filter($todas, fn($s) => $s['estado_contacto'] !== 'aceptada' && (int) $s['id_usuario'] === $yo);

/** Nombre de la otra persona: si yo soy el cliente de esa solicitud, es el trabajador; si no, el cliente. */
function otraParte(array $s, int $yo): string
{
    return ((int) $s['id_usuario'] === $yo)
        ? $s['trabajador_nombre'] . ' ' . $s['trabajador_apellido']
        : $s['cliente_nombre'] . ' ' . $s['cliente_apellido'];
}

/** Etiqueta de colores para el estado del trabajo. */
function etiquetaTrabajo(array $s): string
{
    if ($s['confirmado_cliente'] && $s['confirmado_trabajador']) {
        return '<span class="badge text-bg-success">Trabajo terminado</span>';
    }
    return match ($s['estado_trabajo']) {
        'pendiente' => '<span class="badge text-bg-warning">Contratación pendiente</span>',
        'aceptado'  => '<span class="badge text-bg-primary">Trabajo en curso</span>',
        default     => '',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Chats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">

    <h1 class="h5 mb-3">Conversaciones</h1>
    <?php if (empty($activas)): ?>
        <!-- CU-08 excepción 2.1 -->
        <div class="alert alert-info">
            No tenés conversaciones aún.
            <a href="panel_cliente.php" class="alert-link">Buscá un trabajador</a> y solicitale un servicio.
            <?php if ($rol === 'trabajador'): ?>
                Cuando aceptes una solicitud de chat también aparecerá acá.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list-group mb-4 shadow-sm">
            <?php foreach ($activas as $s): ?>
                <a href="chat.php?id=<?= (int) $s['id_solicitud'] ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between flex-wrap gap-1">
                        <strong><?= Vista::e(otraParte($s, $yo)) ?></strong>
                        <?= etiquetaTrabajo($s) ?>
                    </div>
                    <div class="small text-muted text-truncate">
                        <?= Vista::e($s['ultimo_mensaje'] ?? $s['mensaje_inicial']) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($enviadas)): ?>
        <h2 class="h6 text-uppercase text-muted">Solicitudes enviadas</h2>
        <div class="list-group shadow-sm">
            <?php foreach ($enviadas as $s): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between flex-wrap gap-1">
                        <strong><?= Vista::e(otraParte($s, $yo)) ?></strong>
                        <?php if ($s['estado_contacto'] === 'pendiente'): ?>
                            <span class="badge text-bg-warning">Esperando respuesta</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Rechazada</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted"><?= Vista::e($s['mensaje_inicial']) ?></div>
                    <div class="small text-muted">Enviada el <?= Vista::e(Vista::fecha($s['fecha_creacion'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
