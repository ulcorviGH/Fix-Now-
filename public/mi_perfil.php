<?php
// public/mi_perfil.php
// ---------------------------------------------------------------------------
// "Mi perfil" del Trabajador. Reúne en una sola pantalla:
//   CU-09  Datos del perfil, foto y zonas de trabajo
//   CU-10  Mis servicios (se publican cuando los aprueba el administrador)
//   CU-17  Mis fotos de portfolio (se publican cuando las aprueba el administrador)
// La página es corta: recibe el POST, se lo pasa a TrabajadorController y
// dibuja el resultado. Las reglas de negocio viven en el controller.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('trabajador');

$idUsuario = Sesion::idUsuarioActual();

// ------------------------- Envío de un formulario -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si el archivo superó post_max_size, PHP descarta TODO el POST (también el
    // código CSRF). Lo detectamos para dar un mensaje claro en vez de "venció".
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        Sesion::flash('danger', 'La imagen es demasiado grande. Máximo 5 MB.');
    } elseif (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        Sesion::flash('danger', 'El formulario venció. Intentá de nuevo.');
    } else {
        // "accion" dice cuál de los 5 formularios de la pantalla se envió.
        switch ($_POST['accion'] ?? '') {
            case 'guardar_perfil':
                $resultado = TrabajadorController::guardarPerfil($idUsuario, $_POST, $_FILES['foto'] ?? null);
                break;
            case 'agregar_servicio':
                $resultado = TrabajadorController::agregarServicio($idUsuario, $_POST['nombre'] ?? '', $_POST['descripcion'] ?? '');
                break;
            case 'eliminar_servicio':
                $resultado = TrabajadorController::eliminarServicio($idUsuario, (int) ($_POST['id_servicio'] ?? 0));
                break;
            case 'subir_foto':
                $resultado = TrabajadorController::subirFoto($idUsuario, $_FILES['foto'] ?? null);
                break;
            case 'eliminar_foto':
                $resultado = TrabajadorController::eliminarFoto($idUsuario, (int) ($_POST['id_imagen'] ?? 0));
                break;
            default:
                $resultado = ['exito' => false, 'mensaje' => 'Acción desconocida.'];
        }
        Sesion::flash($resultado['exito'] ? 'success' : 'danger', $resultado['mensaje']);
    }
    // Post/Redirect/Get: recargar la página no reenvía el formulario.
    header('Location: mi_perfil.php');
    exit;
}

$panel = TrabajadorController::panel($idUsuario);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Mi perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h5 mb-3">Mi perfil</h1>

<?php if ($panel === null): ?>
    <div class="alert alert-warning">Tu cuenta de trabajador no está habilitada.</div>

<?php else: ?>
    <?php $p = $panel['perfil']; ?>

    <?php if (!$panel['completo']): ?>
        <div class="alert alert-warning">
            Completá tu <strong>oficio, descripción y zonas de trabajo</strong> para poder publicar servicios y fotos.
        </div>
    <?php endif; ?>

    <!-- ===================== DATOS DEL PERFIL (CU-09) ===================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Datos del perfil</h2>

            <!-- enctype="multipart/form-data" es obligatorio para poder subir archivos -->
            <form method="post" enctype="multipart/form-data">
                <?= Vista::campoCsrf() ?>
                <input type="hidden" name="accion" value="guardar_perfil">

                <div class="d-flex align-items-center gap-3 mb-3">
                    <?= Vista::avatar($p['foto_perfil'], $p['nombre'], $p['apellido'], 80) ?>
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1">Foto de perfil (JPG o PNG, máx. 5 MB)</label>
                        <input type="file" name="foto" class="form-control form-control-sm" accept="image/jpeg,image/png">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre y apellido</label>
                        <!-- No editable: viene del registro (CU-09 paso 2) -->
                        <input type="text" class="form-control" value="<?= Vista::e($p['nombre'] . ' ' . $p['apellido']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Oficio / profesión</label>
                        <input type="text" name="oficio" class="form-control" maxlength="80" required
                               value="<?= Vista::e($p['oficio']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Años de experiencia</label>
                        <input type="number" name="anios_experiencia" class="form-control" min="0" max="80"
                               value="<?= Vista::e($p['anios_experiencia'] !== null ? (string) $p['anios_experiencia'] : '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción personal</label>
                        <textarea name="descripcion" class="form-control" rows="4" maxlength="2000" required
                                  placeholder="Contá tu experiencia y en qué trabajás"><?= Vista::e($p['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Localidades / zonas de trabajo</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($panel['localidades'] as $l): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="localidades[]"
                                           id="loc<?= (int) $l['id_localidad'] ?>" value="<?= (int) $l['id_localidad'] ?>"
                                           <?= in_array((int) $l['id_localidad'], $panel['ids_localidades'], true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="loc<?= (int) $l['id_localidad'] ?>"><?= Vista::e($l['nombre']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== MIS SERVICIOS (CU-10) ===================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-1">Mis servicios</h2>
            <p class="small text-muted">Cada servicio nuevo lo revisa el administrador antes de mostrarse en tu perfil público.</p>

            <?php if (empty($panel['servicios'])): ?>
                <p class="text-muted">Todavía no cargaste servicios.</p>
            <?php endif; ?>
            <?php foreach ($panel['servicios'] as $s): ?>
                <div class="border rounded p-3 mb-2 d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold"><?= Vista::e($s['nombre']) ?></div>
                        <div class="small text-muted" style="white-space: pre-wrap;"><?= Vista::e($s['descripcion']) ?></div>
                        <?php if ($s['estado_revision'] === 'rechazado' && $s['motivo_rechazo']): ?>
                            <div class="small text-danger mt-1">Motivo del rechazo: <?= Vista::e($s['motivo_rechazo']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end text-nowrap">
                        <?= Vista::badgeEstado($s['estado_revision']) ?>
                        <form method="post" class="mt-2" onsubmit="return confirm('¿Eliminar este servicio?');">
                            <?= Vista::campoCsrf() ?>
                            <input type="hidden" name="accion" value="eliminar_servicio">
                            <input type="hidden" name="id_servicio" value="<?= (int) $s['id_servicio'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <hr>
            <h3 class="h6">Agregar nuevo servicio</h3>
            <form method="post">
                <?= Vista::campoCsrf() ?>
                <input type="hidden" name="accion" value="agregar_servicio">
                <div class="mb-2">
                    <input type="text" name="nombre" class="form-control" maxlength="100" required
                           placeholder="Nombre del servicio (ej: Colocación de termotanques)">
                </div>
                <div class="mb-2">
                    <textarea name="descripcion" class="form-control" rows="3" maxlength="1000" required
                              placeholder="Contá brevemente en qué consiste"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" <?= $panel['completo'] ? '' : 'disabled' ?>>Guardar servicio</button>
            </form>
        </div>
    </div>

    <!-- ===================== MIS FOTOS (CU-17) ===================== -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-1">Mis fotos de trabajos</h2>
            <p class="small text-muted">JPG, PNG o WEBP, hasta 5 MB. Cada foto la revisa el administrador antes de publicarse.</p>

            <?php if (empty($panel['imagenes'])): ?>
                <p class="text-muted">Todavía no subiste fotos.</p>
            <?php else: ?>
                <div class="row g-3 mb-3">
                    <?php foreach ($panel['imagenes'] as $img): ?>
                        <div class="col-6 col-md-4">
                            <div class="card h-100">
                                <img src="<?= Vista::e($img['url']) ?>" alt="Foto de trabajo" class="card-img-top object-fit-cover" style="height:140px;">
                                <div class="card-body p-2 small">
                                    <?= Vista::badgeEstado($img['estado_revision']) ?>
                                    <?php if ($img['estado_revision'] === 'rechazada' && $img['motivo_rechazo']): ?>
                                        <div class="text-danger mt-1">Motivo: <?= Vista::e($img['motivo_rechazo']) ?></div>
                                    <?php endif; ?>
                                    <form method="post" class="mt-2" onsubmit="return confirm('¿Eliminar esta foto?');">
                                        <?= Vista::campoCsrf() ?>
                                        <input type="hidden" name="accion" value="eliminar_foto">
                                        <input type="hidden" name="id_imagen" value="<?= (int) $img['id_imagen'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                <?= Vista::campoCsrf() ?>
                <input type="hidden" name="accion" value="subir_foto">
                <input type="file" name="foto" class="form-control form-control-sm w-auto" accept="image/jpeg,image/png,image/webp" required>
                <button type="submit" class="btn btn-primary btn-sm" <?= $panel['completo'] ? '' : 'disabled' ?>>Subir foto</button>
            </form>
        </div>
    </div>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
