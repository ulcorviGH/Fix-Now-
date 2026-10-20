<?php
// public/admin_calificaciones.php
// ---------------------------------------------------------------------------
// CU-14 "Moderar calificaciones".
// Reseñas que un trabajador reportó. El Administrador decide:
//   - Eliminar: desaparece del perfil y el promedio se recalcula solo.
//   - Mantener: es válida; ya no se puede volver a reportar.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

$reportadas = (new Calificacion(Database::getConexion()))->listarReportadas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Moderar calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h5 mb-3">Calificaciones reportadas</h1>

    <?php if (empty($reportadas)): ?>
        <!-- CU-14 excepción 2.1 -->
        <div class="alert alert-info">No hay calificaciones reportadas en este momento.</div>
    <?php endif; ?>

    <?php foreach ($reportadas as $c): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <strong><?= Vista::e($c['cliente_nombre'] . ' ' . $c['cliente_apellido']) ?></strong>
                        <span class="text-muted">→ <?= Vista::e($c['trabajador_nombre'] . ' ' . $c['trabajador_apellido']) ?></span>
                    </div>
                    <span class="small text-muted"><?= Vista::e(Vista::fecha($c['fecha'])) ?></span>
                </div>
                <div><?= Vista::estrellas($c['puntuacion']) ?></div>
                <p class="mb-2 mt-1" style="white-space: pre-wrap;"><?= $c['comentario'] !== null && $c['comentario'] !== ''
                    ? Vista::e($c['comentario']) : '<span class="text-muted">Sin comentario.</span>' ?></p>
                <div class="alert alert-warning py-2 small mb-3">
                    <strong>Motivo del reporte:</strong> <?= Vista::e($c['motivo_reporte']) ?>
                </div>
                <div class="d-flex gap-2">
                    <?= Vista::formAdmin('eliminar_calificacion', (int) $c['id_calificacion'], 'Eliminar calificación', 'btn-danger btn-sm',
                        '¿Eliminar esta calificación? Desaparece del perfil y se recalcula el promedio.') ?>
                    <?= Vista::formAdmin('mantener_calificacion', (int) $c['id_calificacion'], 'Mantener calificación', 'btn-outline-secondary btn-sm') ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
