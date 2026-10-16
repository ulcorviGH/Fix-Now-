<?php
// public/mis_trabajos.php
// ---------------------------------------------------------------------------
// "Mis trabajos" del Cliente: historial de trabajos contratados y su estado
// (CU-07 paso 1). Desde acá se llega a "Calificar", que solo aparece cuando
// el trabajo fue confirmado como terminado por AMBAS partes.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('cliente');

$trabajos = (new Solicitud(Database::getConexion()))->listarTrabajosDeCliente(Sesion::idUsuarioActual());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Mis trabajos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h5 mb-3">Mis trabajos</h1>

    <?php if (empty($trabajos)): ?>
        <div class="alert alert-info">
            Todavía no contrataste ningún trabajo.
            <a href="panel_cliente.php" class="alert-link">Buscá un trabajador</a>.
        </div>
    <?php else: ?>
        <?php foreach ($trabajos as $t): ?>
            <?php
            $terminado = (bool) $t['confirmado_cliente'] && (bool) $t['confirmado_trabajador'];
            // Fase 7 (CU-14): ¿el admin ya le eliminó demasiadas calificaciones hacia este trabajador?
            $bloqueadoCalificar = $terminado && $t['id_calificacion'] === null
                && !CalificacionController::evaluarElegibilidad(Sesion::idUsuarioActual(), (int) $t['id_solicitud'])['puede'];
            ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <strong><?= Vista::e($t['trabajador_nombre'] . ' ' . $t['trabajador_apellido']) ?></strong>
                        <span class="text-primary small ms-1"><?= Vista::e($t['oficio']) ?></span>
                        <div class="small text-muted">
                            Contratado el <?= Vista::e(Vista::fecha($t['fecha_confirmacion_trabajo'])) ?>
                            · <a href="chat.php?id=<?= (int) $t['id_solicitud'] ?>">Ver chat</a>
                        </div>
                    </div>

                    <?php if ($bloqueadoCalificar): ?>
                        <span class="badge text-bg-secondary">No podés calificar a este trabajador</span>

                    <?php elseif ($terminado && $t['id_calificacion'] === null): ?>
                        <!-- CU-07 paso 1: la opción "Calificar" se habilita solo con doble confirmación -->
                        <a href="calificar.php?id=<?= (int) $t['id_solicitud'] ?>" class="btn btn-warning btn-sm">★ Calificar</a>

                    <?php elseif ($terminado): ?>
                        <span class="badge text-bg-secondary">Ya calificaste este trabajo</span>

                    <?php elseif (!$t['confirmado_cliente'] && $t['confirmado_trabajador']): ?>
                        <!-- El trabajador ya lo marcó como terminado: solo falta la confirmación del cliente -->
                        <span class="small text-muted">El trabajador lo marcó como terminado.</span>
                        <?= Vista::formAccion('pregunta_si', (int) $t['id_solicitud'], 'Confirmar que terminó', 'btn-success btn-sm') ?>

                    <?php elseif (!$t['confirmado_cliente']): ?>
                        <span class="badge text-bg-primary">En curso · te vamos a preguntar si ya terminó</span>

                    <?php else: ?>
                        <span class="badge text-bg-warning">Esperando confirmación del trabajador</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
