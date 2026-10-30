<?php
// public/mi_cuenta.php
// ---------------------------------------------------------------------------
// "Mi cuenta" del CLIENTE y del ADMINISTRADOR (el trabajador tiene mi_perfil.php).
// Es el destino del avatar de la barra superior para esos dos roles.
//   Fase 8b: datos de la cuenta y botón de cerrar sesión.
//   Fase 9 (punto 4):
//     - "Cerrar sesión" más discreto, arriba a la derecha.
//     - Cliente: botón "Editar perfil" (nombre, apellido, localidad y contraseña)
//                y mini-panel con tarjetas de estadísticas.
//     - Cliente: "Eliminar cuenta" con confirmación estricta (zona de peligro).
// Las reglas están en CuentaController; esta página solo procesa el POST y dibuja.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRoles(['cliente', 'admin']);

$pdo = Database::getConexion();
$idUsuario = Sesion::idUsuarioActual();
$rol = Sesion::rolActual();
$errorEdicion = null;   // si falla la edición, se vuelve a mostrar el formulario con este mensaje

// ----------------------- Editar perfil (solo clientes) -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'cliente' && Seguridad::texto($_POST['accion'] ?? '') === 'editar_perfil') {
    if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        $errorEdicion = 'El formulario venció. Intentá de nuevo.';
    } else {
        $resultado = CuentaController::editarPerfil($idUsuario, $_POST);
        if ($resultado['exito']) {
            Sesion::flash('success', $resultado['mensaje']);
            header('Location: mi_cuenta.php');   // Post/Redirect/Get
            exit;
        }
        $errorEdicion = $resultado['mensaje'];
    }
}

$usuario = (new Usuario($pdo))->buscarPorId($idUsuario);
$localidadActual = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);
$estadisticas = $rol === 'cliente' ? (new Estadistica($pdo))->delCliente($idUsuario) : null;

// Valores del formulario de edición: si hubo error se muestra lo que la persona escribió.
$valorNombre = $errorEdicion !== null ? Seguridad::texto($_POST['nombre'] ?? '') : ($usuario['nombre'] ?? '');
$valorApellido = $errorEdicion !== null ? Seguridad::texto($_POST['apellido'] ?? '') : ($usuario['apellido'] ?? '');
$idLocalidadForm = ($errorEdicion !== null && ctype_digit(Seguridad::texto($_POST['id_localidad'] ?? '')))
    ? (int) $_POST['id_localidad'] : (int) ($localidadActual['id_localidad'] ?? 0);
$localidadForm = (new Localidad($pdo))->porIds($idLocalidadForm > 0 ? [$idLocalidadForm] : []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Mi cuenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light" <?= $errorEdicion !== null ? 'data-abrir-modal="modalEditarPerfil"' : '' ?>>

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 720px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h5 mb-0">Mi cuenta</h1>
        <!-- Fase 9 (punto 4): "Cerrar sesión" discreto, arriba a la derecha -->
        <?php require __DIR__ . '/../src/views/boton_logout.php'; ?>
    </div>

    <?php if ($usuario === null): ?>
        <div class="alert alert-warning">No encontramos los datos de tu cuenta.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <?= Vista::avatar(null, $usuario['nombre'], $usuario['apellido'], 72) ?>
                    <div class="flex-grow-1">
                        <div class="h5 mb-0"><?= Vista::e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></div>
                        <span class="badge text-bg-secondary"><?= $rol === 'admin' ? 'Administrador' : 'Cliente' ?></span>
                    </div>
                    <?php if ($rol === 'cliente'): ?>
                        <!-- Fase 9 (punto 4): abre el formulario para editar nombre, localidad y contraseña -->
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                            ✎ Editar perfil
                        </button>
                    <?php endif; ?>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Mail</dt>
                    <dd class="col-sm-8"><?= Vista::e($usuario['mail']) ?></dd>
                    <?php if ($localidadActual !== null): ?>
                        <dt class="col-sm-4 text-muted">Localidad</dt>
                        <dd class="col-sm-8"><?= Vista::e($localidadActual['nombre']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4 text-muted">Estado de la cuenta</dt>
                    <dd class="col-sm-8"><?= Vista::e(ucfirst((string) $usuario['estado'])) ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($estadisticas !== null): ?>
            <!-- Fase 9 (punto 4): mini-panel con tarjetas de estadísticas del cliente -->
            <div class="row g-3 mt-1" id="panelEstadisticas">
                <div class="col-4">
                    <div class="card shadow-sm tarjeta-estadistica h-100">
                        <div class="numero"><?= (int) $estadisticas['contratados'] ?></div>
                        <div class="etiqueta">Trabajos solicitados</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card shadow-sm tarjeta-estadistica h-100">
                        <div class="numero"><?= (int) $estadisticas['completados'] ?></div>
                        <div class="etiqueta">Trabajos completados</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card shadow-sm tarjeta-estadistica h-100">
                        <div class="numero"><?= (int) $estadisticas['resenas'] ?></div>
                        <div class="etiqueta">Reseñas escritas</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php /* Fase 9 (punto 4): zona de eliminar la cuenta, con confirmación estricta (no aparece para el administrador) */ ?>
    <?php require __DIR__ . '/../src/views/zona_peligro.php'; ?>
</div>

<?php if ($rol === 'cliente' && $usuario !== null): ?>
<!-- ===== Ventana "Editar perfil" (Fase 9, punto 4): nombre, apellido, localidad y contraseña ===== -->
<div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-labelledby="tituloEditarPerfil" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="post" class="modal-content" novalidate>
            <?= Vista::campoCsrf() ?>
            <input type="hidden" name="accion" value="editar_perfil">
            <div class="modal-header">
                <h2 class="modal-title h5" id="tituloEditarPerfil">Editar perfil</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if ($errorEdicion !== null): ?>
                    <div class="alert alert-danger small"><?= Vista::e($errorEdicion) ?></div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label small" for="editNombre">Nombre</label>
                        <input type="text" class="form-control" id="editNombre" name="nombre" maxlength="60" required
                               value="<?= Vista::e($valorNombre) ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small" for="editApellido">Apellido</label>
                        <input type="text" class="form-control" id="editApellido" name="apellido" maxlength="60" required
                               value="<?= Vista::e($valorApellido) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Localidad</label>
                        <?php $selId = 'localidadEdicion'; $selCampo = 'id_localidad'; $selMax = 1;
                        $selElegidas = $localidadForm; $selPlaceholder = 'Buscá tu localidad';
                        require __DIR__ . '/../src/views/selector_localidades.php'; ?>
                    </div>
                    <div class="col-12"><hr class="my-1"><div class="small text-muted">Cambiar la contraseña (opcional: dejalo vacío para mantener la actual)</div></div>
                    <div class="col-12">
                        <label class="form-label small" for="passActual">Contraseña actual</label>
                        <div class="pw-wrap"><input type="password" class="form-control" id="passActual" name="contrasena_actual" autocomplete="current-password"><?= Vista::ojoPassword() ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small" for="passNueva">Contraseña nueva</label>
                        <div class="pw-wrap"><input type="password" class="form-control" id="passNueva" name="contrasena_nueva" autocomplete="new-password"><?= Vista::ojoPassword() ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small" for="passRepetida">Repetir la nueva</label>
                        <div class="pw-wrap"><input type="password" class="form-control" id="passRepetida" name="contrasena_repetida" autocomplete="new-password"><?= Vista::ojoPassword() ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/localidades.js"></script>
<script src="assets/js/password.js"></script>
<script src="assets/js/cuenta.js"></script>
</body>
</html>
