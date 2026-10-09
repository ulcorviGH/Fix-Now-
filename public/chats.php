<?php
// public/chats.php
// ---------------------------------------------------------------------------
// Sección "Chats" del menú (CU-08 paso 1), separada de "Solicitudes".
//   - CLIENTE: ve sus conversaciones activas y también el estado de las
//     solicitudes que envió (pendiente / rechazada). Así se entera del
//     resultado (CU-06 paso 7).
//   - TRABAJADOR: ve sus conversaciones activas (los pedidos nuevos
//     se responden en "Solicitudes").
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirLogin();
$rol = Sesion::rolActual();
if (!in_array($rol, ['cliente', 'trabajador'], true)) {
    header('Location: panel_' . $rol . '.php');
    exit;
}

$todas = (new Solicitud(Database::getConexion()))->listarPorUsuario(Sesion::idUsuarioActual(), $rol);

$activas = array_filter($todas, fn($s) => $s['estado_contacto'] === 'aceptada');
$enviadas = array_filter($todas, fn($s) => $s['estado_contacto'] !== 'aceptada');

/** Nombre de la otra persona según mi rol. */
function otraParte(array $s, string $rol): string
{
    return $rol === 'cliente'
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
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">

    <h1 class="h5 mb-3">Conversaciones</h1>
    <?php if (empty($activas)): ?>
        <!-- CU-08 excepción 2.1 -->
        <div class="alert alert-info">
            No tenés conversaciones aún.
            <?php if ($rol === 'cliente'): ?>
                <a href="panel_cliente.php" class="alert-link">Buscá un trabajador</a> y enviale un mensaje.
            <?php else: ?>
                Cuando aceptes una solicitud de chat aparecerá acá.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list-group mb-4 shadow-sm">
            <?php foreach ($activas as $s): ?>
                <a href="chat.php?id=<?= (int) $s['id_solicitud'] ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between flex-wrap gap-1">
                        <strong><?= Vista::e(otraParte($s, $rol)) ?></strong>
                        <?= etiquetaTrabajo($s) ?>
                    </div>
                    <div class="small text-muted text-truncate">
                        <?= Vista::e($s['ultimo_mensaje'] ?? $s['mensaje_inicial']) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($rol === 'cliente' && !empty($enviadas)): ?>
        <h2 class="h6 text-uppercase text-muted">Solicitudes enviadas</h2>
        <div class="list-group shadow-sm">
            <?php foreach ($enviadas as $s): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between flex-wrap gap-1">
                        <strong><?= Vista::e(otraParte($s, $rol)) ?></strong>
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
