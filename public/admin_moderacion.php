<?php
// public/admin_moderacion.php
// ---------------------------------------------------------------------------
// CU-15 "Gestionar publicaciones de servicios" + CU-19 "Moderar contenido
// multimedia". Es UNA sola pantalla con dos pestañas (Servicios / Imágenes)
// porque la acción es la misma (aprobar o rechazar con motivo) sobre dos tablas.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

$pdo = Database::getConexion();
$tab = ($_GET['tab'] ?? 'servicios') === 'imagenes' ? 'imagenes' : 'servicios';

$servicios = (new Servicio($pdo))->listarPendientes();
$imagenes = (new Imagen($pdo))->listarPendientes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Moderar contenido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h5 mb-3">Moderar contenido</h1>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'servicios' ? 'active' : '' ?>" href="admin_moderacion.php?tab=servicios">
                Servicios <span class="badge text-bg-light"><?= count($servicios) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'imagenes' ? 'active' : '' ?>" href="admin_moderacion.php?tab=imagenes">
                Imágenes <span class="badge text-bg-light"><?= count($imagenes) ?></span>
            </a>
        </li>
    </ul>

    <p class="text-muted small">Aprobá solo contenido relacionado con el oficio del trabajador, sin material
        inapropiado ni logos de otras empresas. Al rechazar hay que escribir el motivo.</p>

<?php if ($tab === 'servicios'): ?>

    <?php if (empty($servicios)): ?>
        <!-- CU-15 excepción 2.1 -->
        <div class="alert alert-info">No hay publicaciones para revisar en este momento.</div>
    <?php endif; ?>
    <?php foreach ($servicios as $s): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="small text-muted">
                    <?= Vista::e($s['trabajador_nombre'] . ' ' . $s['trabajador_apellido']) ?> · <?= Vista::e($s['oficio']) ?>
                </div>
                <div class="fw-semibold"><?= Vista::e($s['nombre']) ?></div>
                <p class="mb-2" style="white-space: pre-wrap;"><?= Vista::e($s['descripcion']) ?></p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= Vista::formAdmin('aprobar_servicio', (int) $s['id_servicio'], 'Aprobar', 'btn-success btn-sm') ?>
                    <?= Vista::formAdminMotivo('rechazar_servicio', (int) $s['id_servicio'], 'Rechazar') ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php else: ?>

    <?php if (empty($imagenes)): ?>
        <!-- CU-19 excepción 3.1 -->
        <div class="alert alert-info">No hay contenido pendiente de revisión.</div>
    <?php endif; ?>
    <?php foreach ($imagenes as $i): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap gap-3">
                <img src="<?= Vista::e($i['url']) ?>" alt="Foto pendiente" class="rounded object-fit-cover" style="width:200px;height:150px;">
                <div class="flex-grow-1">
                    <div class="small text-muted mb-2">
                        <?= Vista::e($i['trabajador_nombre'] . ' ' . $i['trabajador_apellido']) ?> · <?= Vista::e($i['oficio']) ?>
                        · subida el <?= Vista::e(Vista::fecha($i['fecha_subida'])) ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?= Vista::formAdmin('aprobar_imagen', (int) $i['id_imagen'], 'Aprobar', 'btn-success btn-sm') ?>
                        <?= Vista::formAdminMotivo('rechazar_imagen', (int) $i['id_imagen'], 'Rechazar') ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
