<?php
// public/admin_habilitar.php
// ---------------------------------------------------------------------------
// CU-18 "Habilitar cuenta de trabajador".
// El Administrador revisa los trabajadores recién registrados y decide si los
// habilita o los rechaza (con motivo). Los botones envían a accion_admin.php.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

$modelo = new Trabajador(Database::getConexion());
$pendientes = $modelo->listarPorEstadoHabilitacion('pendiente');
$rechazados = $modelo->listarPorEstadoHabilitacion('rechazado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Habilitar trabajadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 960px;">
    <h1 class="h5 mb-1">Habilitar trabajadores</h1>
    <p class="text-muted small">Verificá que sean personas reales con un oficio relacionado a la plataforma
        (plomero, electricista, técnico, etc.) y que los datos sean consistentes.</p>

    <?php if (empty($pendientes)): ?>
        <!-- CU-18 excepción 3.1 -->
        <div class="alert alert-info">No hay cuentas pendientes en este momento.</div>
    <?php endif; ?>

    <?php foreach ($pendientes as $t): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong><?= Vista::e($t['nombre'] . ' ' . $t['apellido']) ?></strong>
                        <span class="text-muted small">· <?= Vista::e($t['mail']) ?></span>
                    </div>
                    <?= Vista::badgeEstado('pendiente') ?>
                </div>
                <div class="small mt-2">
                    <div><strong>Oficio declarado:</strong> <?= Vista::e($t['oficio']) ?></div>
                    <div><strong>Localidades:</strong> <?= Vista::e($t['localidades']) ?></div>
                    <div><strong>Registro:</strong> <?= Vista::e(Vista::fecha($t['fecha_registro'])) ?></div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                    <?= Vista::formAdmin('habilitar_trabajador', (int) $t['id_trabajador'], 'Habilitar', 'btn-success btn-sm') ?>
                    <?= Vista::formAdminMotivo('rechazar_trabajador', (int) $t['id_trabajador'], 'Rechazar', 'btn-outline-danger btn-sm', 'Motivo del rechazo (obligatorio)') ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($rechazados)): ?>
        <h2 class="h6 text-uppercase text-muted mt-4">Rechazadas anteriormente</h2>
        <?php foreach ($rechazados as $t): ?>
            <div class="card shadow-sm mb-2">
                <div class="card-body d-flex justify-content-between flex-wrap gap-2 align-items-center">
                    <div class="small">
                        <strong><?= Vista::e($t['nombre'] . ' ' . $t['apellido']) ?></strong>
                        · <?= Vista::e($t['oficio']) ?>
                        <div class="text-muted">Motivo: <?= Vista::e($t['motivo_rechazo']) ?></div>
                    </div>
                    <?= Vista::formAdmin('habilitar_trabajador', (int) $t['id_trabajador'], 'Habilitar igualmente', 'btn-outline-success btn-sm',
                        'Esta cuenta había sido rechazada. ¿Querés habilitarla igual?') ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
