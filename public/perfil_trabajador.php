<?php
// public/perfil_trabajador.php
// ---------------------------------------------------------------------------
// Perfil público de un trabajador (CU-04 "Ver perfil de trabajador").
// Se accede con:  perfil_trabajador.php?id=NUMERO
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('cliente');

// (int) convierte cualquier cosa rara que venga en la URL en un número,
// así nadie puede inyectar texto malicioso por acá.
$idTrabajador = (int) ($_GET['id'] ?? 0);
$perfil = $idTrabajador > 0 ? PerfilController::obtener($idTrabajador) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">

    <a href="panel_cliente.php" class="btn btn-link px-0 mb-2">← Volver a la lista</a>

    <?php if ($perfil === null): ?>
        <!-- CU-04 excepción 2.2 -->
        <div class="alert alert-warning">Este perfil no está disponible.</div>

    <?php else: ?>

        <!-- ================= CABECERA ================= -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap gap-4 align-items-center">
                <?= Vista::avatar($perfil['foto_perfil'], $perfil['nombre'], $perfil['apellido'], 110) ?>
                <div class="flex-grow-1">
                    <h1 class="h4 mb-1"><?= Vista::e($perfil['nombre'] . ' ' . $perfil['apellido']) ?></h1>
                    <div class="text-primary"><?= Vista::e($perfil['oficio']) ?></div>
                    <div class="small text-muted mt-1">📍 <?= Vista::e($perfil['localidades']) ?></div>
                    <div class="mt-2">
                        <?= Vista::estrellas($perfil['calificacion_promedio']) ?>
                        <span class="small text-muted">(<?= (int) $perfil['cantidad_calificaciones'] ?> reseñas)</span>
                    </div>
                    <!-- CU-04 excepción 2.4: si no hay trabajos, muestra 0 -->
                    <div class="small mt-1">✔ Trabajos completados: <strong><?= (int) $perfil['trabajos_completados'] ?></strong></div>
                </div>
                <div>
                    <!-- El contacto se construye en la Fase 5 (CU-06). -->
                    <button class="btn btn-primary" disabled
                            title="Disponible en la próxima fase">Enviar mensaje</button>
                    <div class="form-text">Disponible próximamente</div>
                </div>
            </div>
        </div>

        <!-- ================= SOBRE EL TRABAJADOR ================= -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Sobre mí</h2>
                <p class="mb-2">
                    <?= !empty($perfil['descripcion']) ? nl2br(Vista::e($perfil['descripcion'])) : '<span class="text-muted">Sin completar</span>' ?>
                </p>
                <div class="small text-muted">
                    Experiencia:
                    <?= $perfil['anios_experiencia'] !== null ? (int) $perfil['anios_experiencia'] . ' años' : 'Sin completar' ?>
                </div>
            </div>
        </div>

        <!-- ================= SERVICIOS ================= -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Servicios que ofrece</h2>
                <?php if (empty($perfil['servicios'])): ?>
                    <span class="text-muted">Todavía no publicó servicios.</span>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($perfil['servicios'] as $servicio): ?>
                            <li class="list-group-item px-0">
                                <div class="fw-semibold"><?= Vista::e($servicio['nombre']) ?></div>
                                <div class="small text-muted"><?= Vista::e($servicio['descripcion']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= PORTFOLIO ================= -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Trabajos anteriores</h2>
                <?php if (empty($perfil['imagenes'])): ?>
                    <span class="text-muted">Todavía no subió fotos.</span>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($perfil['imagenes'] as $img): ?>
                            <div class="col-6 col-md-4">
                                <img src="<?= Vista::e($img['url']) ?>" alt="Trabajo anterior"
                                     class="img-fluid rounded w-100 object-fit-cover" style="height:150px;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= RESEÑAS ================= -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Reseñas de clientes</h2>
                <?php if (empty($perfil['resenias'])): ?>
                    <span class="text-muted">Aún no tiene reseñas.</span>
                <?php else: ?>
                    <?php foreach ($perfil['resenias'] as $r): ?>
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= Vista::e($r['nombre_cliente']) ?></strong>
                                <span class="small text-muted"><?= Vista::e(date('d/m/Y', strtotime($r['fecha']))) ?></span>
                            </div>
                            <div><?= Vista::estrellas($r['puntuacion']) ?></div>
                            <?php if (!empty($r['comentario'])): ?>
                                <p class="mb-1"><?= nl2br(Vista::e($r['comentario'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($r['respuesta'])): ?>
                                <!-- Respuesta pública del trabajador (CU-20) -->
                                <div class="bg-light border-start border-3 ps-3 py-2 mt-2 small">
                                    <strong>Respuesta del trabajador:</strong><br>
                                    <?= nl2br(Vista::e($r['respuesta'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
