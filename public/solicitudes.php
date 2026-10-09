<?php
// public/solicitudes.php
// ---------------------------------------------------------------------------
// CU-11 "Gestionar solicitudes recibidas" (Trabajador).
// Una sola sección con DOS pestañas:
//   ?tab=chat    -> pedidos iniciales de contacto (CU-06): Aceptar / Rechazar
//   ?tab=trabajo -> confirmaciones de contratación (CU-21): Aceptar / Rechazar,
//                   y los trabajos en curso con el botón "Marcar trabajo como
//                   terminado" (CU-22)
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('trabajador');

$pdo = Database::getConexion();
$idTrabajador = (new Trabajador($pdo))->obtenerIdPorUsuario(Sesion::idUsuarioActual());
$modelo = new Solicitud($pdo);

$tab = ($_GET['tab'] ?? 'chat') === 'trabajo' ? 'trabajo' : 'chat';

$contactos = $idTrabajador ? $modelo->listarContactosPendientes($idTrabajador) : [];
$trabajosPendientes = $idTrabajador ? $modelo->listarTrabajosPendientes($idTrabajador) : [];
$trabajosEnCurso = $idTrabajador ? $modelo->listarTrabajosEnCurso($idTrabajador) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h5 mb-3">Solicitudes</h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'chat' ? 'active' : '' ?>" href="solicitudes.php?tab=chat">
                Solicitudes de chat
                <?php if (count($contactos) > 0): ?><span class="badge text-bg-danger"><?= count($contactos) ?></span><?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'trabajo' ? 'active' : '' ?>" href="solicitudes.php?tab=trabajo">
                Solicitudes de trabajo
                <?php if (count($trabajosPendientes) > 0): ?><span class="badge text-bg-danger"><?= count($trabajosPendientes) ?></span><?php endif; ?>
            </a>
        </li>
    </ul>

<?php if ($tab === 'chat'): ?>

    <?php if (empty($contactos)): ?>
        <div class="alert alert-info">No tenés solicitudes pendientes.</div>
    <?php else: ?>
        <?php foreach ($contactos as $s): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap">
                        <strong><?= Vista::e($s['cliente_nombre'] . ' ' . $s['cliente_apellido']) ?></strong>
                        <span class="small text-muted"><?= Vista::e(Vista::fecha($s['fecha_creacion'])) ?></span>
                    </div>
                    <p class="my-2" style="white-space: pre-wrap;"><?= Vista::e($s['mensaje_inicial']) ?></p>
                    <?= Vista::formAccion('aceptar_chat', (int) $s['id_solicitud'], 'Aceptar', 'btn-success btn-sm') ?>
                    <?= Vista::formAccion('rechazar_chat', (int) $s['id_solicitud'], 'Rechazar', 'btn-outline-danger btn-sm',
                        '¿Seguro que querés rechazar esta solicitud? No se habilitará ningún chat.') ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>

    <h2 class="h6 text-uppercase text-muted">Por responder</h2>
    <?php if (empty($trabajosPendientes)): ?>
        <div class="alert alert-info">No tenés solicitudes pendientes.</div>
    <?php else: ?>
        <?php foreach ($trabajosPendientes as $s): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <strong><?= Vista::e($s['cliente_nombre'] . ' ' . $s['cliente_apellido']) ?></strong>
                    quiere contratarte.
                    <a href="chat.php?id=<?= (int) $s['id_solicitud'] ?>" class="small">Ver la conversación</a>
                    <div class="mt-2">
                        <?= Vista::formAccion('aceptar_trabajo', (int) $s['id_solicitud'], 'Aceptar', 'btn-success btn-sm') ?>
                        <?= Vista::formAccion('rechazar_trabajo', (int) $s['id_solicitud'], 'Rechazar', 'btn-outline-danger btn-sm') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 class="h6 text-uppercase text-muted mt-4">Trabajos en curso</h2>
    <?php if (empty($trabajosEnCurso)): ?>
        <div class="text-muted small">No tenés trabajos en curso.</div>
    <?php else: ?>
        <?php foreach ($trabajosEnCurso as $s): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <strong><?= Vista::e($s['cliente_nombre'] . ' ' . $s['cliente_apellido']) ?></strong>
                        <div class="small text-muted">
                            Aceptado el <?= Vista::e(Vista::fecha($s['fecha_confirmacion_trabajo'])) ?> ·
                            Vos: <?= $s['confirmado_trabajador'] ? '✔ confirmaste' : 'sin confirmar' ?> ·
                            Cliente: <?= $s['confirmado_cliente'] ? '✔ confirmó' : 'sin confirmar' ?>
                        </div>
                        <a href="chat.php?id=<?= (int) $s['id_solicitud'] ?>" class="small">Ir al chat</a>
                    </div>
                    <?php if (!$s['confirmado_trabajador']): ?>
                        <!-- CU-22: botón manual -->
                        <?= Vista::formAccion('terminar_manual', (int) $s['id_solicitud'], 'Marcar trabajo como terminado',
                            'btn-primary btn-sm', '¿Confirmás que este trabajo está terminado?') ?>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Esperando confirmación del cliente</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
