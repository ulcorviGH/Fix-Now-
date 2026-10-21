<?php
// public/mi_cuenta.php
// ---------------------------------------------------------------------------
// Fase 8b (cambios 1.3 y 1.4): "Mi cuenta" del CLIENTE y del ADMINISTRADOR.
// Es el destino del enlace de perfil de la barra superior para esos dos roles
// (el trabajador tiene su propia pantalla: mi_perfil.php). Muestra los datos de
// la cuenta y, al final, el botón rojo "Cerrar sesión".
// Es solo de lectura: los casos de uso no incluyen editar datos personales.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRoles(['cliente', 'admin']);

$pdo = Database::getConexion();
$idUsuario = Sesion::idUsuarioActual();
$usuario = (new Usuario($pdo))->buscarPorId($idUsuario);
$localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);
$rol = Sesion::rolActual();
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
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 640px;">
    <h1 class="h5 mb-3">Mi cuenta</h1>

    <?php if ($usuario === null): ?>
        <div class="alert alert-warning">No encontramos los datos de tu cuenta.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?= Vista::avatar(null, $usuario['nombre'], $usuario['apellido'], 72) ?>
                    <div>
                        <div class="h5 mb-0"><?= Vista::e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></div>
                        <span class="badge text-bg-secondary"><?= $rol === 'admin' ? 'Administrador' : 'Cliente' ?></span>
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Mail</dt>
                    <dd class="col-sm-8"><?= Vista::e($usuario['mail']) ?></dd>
                    <?php if ($localidad !== null): ?>
                        <dt class="col-sm-4 text-muted">Localidad</dt>
                        <dd class="col-sm-8"><?= Vista::e($localidad['nombre']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4 text-muted">Estado de la cuenta</dt>
                    <dd class="col-sm-8"><?= Vista::e(ucfirst((string) $usuario['estado'])) ?></dd>
                </dl>
            </div>
        </div>
    <?php endif; ?>

    <!-- Fase 8b (cambio 1.4): el botón de cerrar sesión va al final del perfil -->
    <?php require __DIR__ . '/../src/views/boton_logout.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
