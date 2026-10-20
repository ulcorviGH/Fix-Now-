<?php
// public/admin_usuarios.php
// ---------------------------------------------------------------------------
// CU-13 "Gestionar usuarios".
// Lista de clientes y trabajadores con filtros (rol, estado, texto) y los
// botones Bloquear / Activar / Eliminar. Los administradores NO aparecen.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

// Filtros que llegan por la URL (GET). Cualquier valor raro simplemente no filtra.
$rol = Seguridad::texto($_GET['rol'] ?? '');
$estado = Seguridad::texto($_GET['estado'] ?? '');
$texto = trim(Seguridad::texto($_GET['texto'] ?? ''));

$usuarios = (new Usuario(Database::getConexion()))->listarParaAdmin($rol, $estado, $texto);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 1040px;">
    <h1 class="h5 mb-3">Gestión de usuarios</h1>

    <!-- Filtros: method="get" para que queden en la URL y se puedan compartir/recargar -->
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="texto" class="form-control form-control-sm" placeholder="Buscar por nombre o mail"
                   value="<?= Vista::e($texto) ?>">
        </div>
        <div class="col-6 col-md-2">
            <select name="rol" class="form-select form-select-sm">
                <option value="">Todos los roles</option>
                <option value="cliente" <?= $rol === 'cliente' ? 'selected' : '' ?>>Clientes</option>
                <option value="trabajador" <?= $rol === 'trabajador' ? 'selected' : '' ?>>Trabajadores</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <?php foreach (['activo', 'pendiente', 'bloqueado', 'rechazado'] as $e): ?>
                    <option value="<?= $e ?>" <?= $estado === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
            <a href="admin_usuarios.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        </div>
    </form>

    <?php if (empty($usuarios)): ?>
        <!-- CU-13 excepción 3.1 -->
        <div class="alert alert-info">No hay usuarios registrados que coincidan.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>Nombre</th><th>Rol</th><th>Estado</th><th>Localidad</th><th>Registro</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php $id = (int) $u['id_usuario']; $nombre = $u['nombre'] . ' ' . $u['apellido']; ?>
                        <tr>
                            <td>
                                <strong><?= Vista::e($nombre) ?></strong><br>
                                <span class="text-muted"><?= Vista::e($u['mail']) ?></span>
                            </td>
                            <td><?= Vista::e(ucfirst($u['rol'])) ?><?= $u['oficio'] ? '<br><span class="text-muted">' . Vista::e($u['oficio']) . '</span>' : '' ?></td>
                            <td><?= Vista::badgeEstado($u['estado_visible']) ?></td>
                            <td><?= Vista::e($u['localidad']) ?></td>
                            <td><?= Vista::e(date('d/m/Y', strtotime($u['fecha_registro']))) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                <?php if ($u['estado_visible'] === 'activo'): ?>
                                    <?= Vista::formAdmin('bloquear_usuario', $id, 'Bloquear', 'btn-outline-warning btn-sm',
                                        '¿Bloquear la cuenta de ' . $nombre . '? No va a poder iniciar sesión.') ?>
                                <?php elseif ($u['estado_visible'] === 'bloqueado'): ?>
                                    <?= Vista::formAdmin('activar_usuario', $id, 'Activar', 'btn-outline-success btn-sm') ?>
                                <?php else: ?>
                                    <a href="admin_habilitar.php" class="btn btn-outline-secondary btn-sm">Ir a habilitación</a>
                                <?php endif; ?>
                                <?= Vista::formAdmin('eliminar_usuario', $id, 'Eliminar', 'btn-outline-danger btn-sm',
                                    'ELIMINAR la cuenta de ' . $nombre . '. Se borran también sus solicitudes, chats, calificaciones y fotos. '
                                    . 'Esta acción no se puede deshacer. ¿Continuar?') ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="form-text mt-2"><?= count($usuarios) ?> usuario(s) (se muestran hasta 200).</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
