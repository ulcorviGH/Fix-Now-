<?php
// public/calificar.php
// ---------------------------------------------------------------------------
// CU-07 "Calificar trabajador" (Cliente).  calificar.php?id=ID_SOLICITUD
//   - 1 a 5 estrellas (obligatorio) + comentario (opcional)
//   - Solo disponible si ambas partes confirmaron que el trabajo terminó
//     y el cliente no lo calificó antes. Si alguien llega por URL directa sin
//     cumplirlo, ve el mensaje correspondiente y no el formulario.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('cliente');

$idCliente = Sesion::idUsuarioActual();
$idSolicitud = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$error = '';
$puntuacion = 0;
$comentario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $puntuacion = (int) ($_POST['puntuacion'] ?? 0);
    $comentario = Seguridad::texto($_POST['comentario'] ?? '');

    if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario venció. Intentá de nuevo.';
    } else {
        $resultado = CalificacionController::calificar($idCliente, $idSolicitud, $puntuacion, $comentario);
        if ($resultado['exito']) {
            // Se lleva al perfil del trabajador para que el cliente vea su reseña publicada.
            Sesion::flash('success', $resultado['mensaje']);
            header('Location: perfil_trabajador.php?id=' . (int) $resultado['id_trabajador']);
            exit;
        }
        $error = $resultado['mensaje'];
    }
}

$estado = CalificacionController::evaluarElegibilidad($idCliente, $idSolicitud);
$s = $estado['solicitud'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Calificar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 600px;">

    <?php if (!$estado['puede']): ?>
        <div class="alert alert-warning"><?= Vista::e($estado['mensaje']) ?></div>
        <a href="mis_trabajos.php" class="btn btn-outline-secondary">← Volver a mis trabajos</a>

    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5">Calificar a <?= Vista::e($s['trabajador_nombre'] . ' ' . $s['trabajador_apellido']) ?></h1>
                <div class="text-primary small mb-3"><?= Vista::e($s['oficio']) ?></div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><?= Vista::e($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <?= Vista::campoCsrf() ?>
                    <input type="hidden" name="id" value="<?= $idSolicitud ?>">

                    <label class="form-label d-block">Puntuación</label>
                    <div class="estrellas-input mb-3">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="puntuacion" id="estrella<?= $i ?>" value="<?= $i ?>"
                                   <?= $puntuacion === $i ? 'checked' : '' ?>>
                            <label for="estrella<?= $i ?>" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">★</label>
                        <?php endfor; ?>
                    </div>

                    <label for="comentario" class="form-label">Comentario <span class="text-muted">(opcional)</span></label>
                    <textarea id="comentario" name="comentario" class="form-control mb-3" rows="4" maxlength="1000"
                              placeholder="Contá cómo fue tu experiencia"><?= Vista::e($comentario) ?></textarea>

                    <button type="submit" class="btn btn-primary">Publicar calificación</button>
                    <a href="mis_trabajos.php" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
