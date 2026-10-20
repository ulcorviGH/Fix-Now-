<?php
// public/admin_reportes.php
// ---------------------------------------------------------------------------
// CU-16 "Ver reportes y estadísticas".
// Todas las cifras se calculan en el momento con COUNT/AVG (modelo Estadistica).
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

$m = (new Estadistica(Database::getConexion()))->metricas();

/** Dibuja una barra de progreso proporcional (valor / total). */
function barra(string $titulo, int $valor, int $total, string $color): string
{
    $porc = $total > 0 ? round($valor * 100 / $total) : 0;
    return '<div class="mb-2"><div class="d-flex justify-content-between small">'
         . '<span>' . Vista::e($titulo) . '</span><strong>' . $valor . '</strong></div>'
         . '<div class="progress" style="height:8px;"><div class="progress-bar bg-' . $color . '" style="width:' . $porc . '%"></div></div></div>';
}

$totalChat = $m['chat_aceptadas'] + $m['chat_rechazadas'] + $m['chat_pendientes'];
$totalTrabajo = $m['trabajo_aceptadas'] + $m['trabajo_rechazadas'] + $m['trabajo_pendientes'];
$hayDatos = ($m['clientes'] + $m['trabajadores'] + $totalChat) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 960px;">
    <h1 class="h5 mb-3">Reportes y estadísticas</h1>

<?php if (!$hayDatos): ?>
    <!-- CU-16 excepción 2.2 -->
    <div class="alert alert-info">Aún no hay datos suficientes para generar reportes.</div>
<?php else: ?>

    <div class="row g-3 mb-4">
        <?php
        $tarjetas = [
            [$m['clientes'] + $m['trabajadores'], 'Usuarios totales (' . $m['clientes'] . ' clientes, ' . $m['trabajadores'] . ' trabajadores)'],
            [$m['activos_mes'], 'Usuarios activos en el último mes'],
            [$m['pendientes_habilitacion'], 'Trabajadores pendientes de habilitación'],
            [$m['servicios_publicados'], 'Servicios publicados'],
            [$m['trabajos_completados'], 'Trabajos completados (doble confirmación)'],
            [$m['calificaciones_reportadas'], 'Calificaciones reportadas pendientes'],
        ];
        ?>
        <?php foreach ($tarjetas as [$numero, $texto]): ?>
            <div class="col-6 col-md-4">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="display-6"><?= (int) $numero ?></div>
                    <div class="small text-muted"><?= Vista::e($texto) ?></div>
                </div></div>
            </div>
        <?php endforeach; ?>
        <div class="col-6 col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="fs-3"><?= Vista::estrellas($m['calificacion_promedio']) ?></div>
                <div class="small text-muted">Calificación promedio general</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Solicitudes de chat (<?= $totalChat ?>)</h2>
                <?= barra('Aceptadas', $m['chat_aceptadas'], $totalChat, 'success') ?>
                <?= barra('Rechazadas', $m['chat_rechazadas'], $totalChat, 'danger') ?>
                <?= barra('Pendientes', $m['chat_pendientes'], $totalChat, 'warning') ?>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Solicitudes de trabajo (<?= $totalTrabajo ?>)</h2>
                <?= barra('Aceptadas', $m['trabajo_aceptadas'], $totalTrabajo, 'success') ?>
                <?= barra('Rechazadas', $m['trabajo_rechazadas'], $totalTrabajo, 'danger') ?>
                <?= barra('Pendientes', $m['trabajo_pendientes'], $totalTrabajo, 'warning') ?>
            </div></div>
        </div>
    </div>
    <div class="form-text mt-3">"Usuarios activos" cuenta a quienes iniciaron sesión en los últimos 30 días.</div>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
