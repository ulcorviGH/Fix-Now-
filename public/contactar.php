<?php
// public/contactar.php
// ---------------------------------------------------------------------------
// CU-06 "Solicitar contacto con un trabajador" (pasos 1 a 4).
// Se accede desde el botón "Enviar mensaje" del perfil:
//     contactar.php?id=ID_DEL_TRABAJADOR
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

// Fase 8b (cambio 3.4): cliente Y trabajador (un trabajador puede actuar como cliente).
Sesion::requerirRoles(['cliente', 'trabajador']);

$idTrabajador = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$idCliente = Sesion::idUsuarioActual();
$pdo = Database::getConexion();

$perfil = $idTrabajador > 0 ? (new Trabajador($pdo))->obtenerPerfilCompleto($idTrabajador) : null;

// Si ya hay una solicitud o un chat en curso con este trabajador, no se pide otra:
// se lleva a la persona a donde corresponde.
if ($perfil !== null) {
    $vigente = (new Solicitud($pdo))->vigenteEntre($idCliente, $idTrabajador);
    if ($vigente !== null) {
        if ($vigente['estado_contacto'] === 'aceptada') {
            header('Location: chat.php?id=' . (int) $vigente['id_solicitud']);
        } else {
            Sesion::flash('info', 'Ya enviaste una solicitud a este trabajador. Está pendiente de respuesta.');
            header('Location: chats.php');
        }
        exit;
    }
}

$error = '';
$mensaje = '';

if ($perfil !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = Seguridad::texto($_POST['mensaje'] ?? '');

    if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        $error = 'El formulario venció. Intentá de nuevo.';
    } else {
        $resultado = SolicitudController::solicitarContacto($idCliente, $idTrabajador, $mensaje);
        if ($resultado['exito']) {
            Sesion::flash('success', $resultado['mensaje']);
            header('Location: chats.php');
            exit;
        }
        $error = $resultado['mensaje'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Contactar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 640px;">
    <?php if ($perfil === null): ?>
        <div class="alert alert-warning">Este perfil no está disponible.</div>
        <a href="panel_cliente.php" class="btn-volver">← Volver</a>
    <?php else: ?>
        <!-- Fase 8b (cambio 1.5): enlace de retroceso estandarizado, vuelve al perfil del trabajador -->
        <a href="perfil_trabajador.php?id=<?= (int) $idTrabajador ?>" class="btn-volver mb-2">← Volver</a>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <?= Vista::avatar($perfil['foto_perfil'], $perfil['nombre'], $perfil['apellido'], 56) ?>
                    <div>
                        <h1 class="h5 mb-0">Contactar a <?= Vista::e($perfil['nombre'] . ' ' . $perfil['apellido']) ?></h1>
                        <div class="text-primary small"><?= Vista::e($perfil['oficio']) ?></div>
                    </div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><?= Vista::e($error) ?></div>
                <?php endif; ?>

                <!-- CU-06 paso 2: formulario con el mensaje inicial -->
                <form method="post">
                    <?= Vista::campoCsrf() ?>
                    <input type="hidden" name="id" value="<?= $idTrabajador ?>">

                    <label for="mensaje" class="form-label">Contale brevemente qué necesitás</label>
                    <textarea id="mensaje" name="mensaje" class="form-control" rows="5" maxlength="1000"
                              placeholder="Tipo de trabajo, urgencia y detalles que ayuden a entender el problema"
                              required><?= Vista::e($mensaje) ?></textarea>
                    <div class="form-text mb-3">
                        Todavía no estás contratando: primero el trabajador decide si abre el chat.
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                    <a href="perfil_trabajador.php?id=<?= $idTrabajador ?>" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
