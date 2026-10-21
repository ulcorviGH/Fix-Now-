<?php
// public/registro.php
// ---------------------------------------------------------------------------
// Pantalla de registro (CU-01 "Registrar usuario"), pasos 3 a 7.
// Igual que index.php: si llega POST, procesa; si no, muestra el formulario.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fase 8: el registro también lleva token CSRF.
    if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
        $resultado = ['exito' => false, 'errores' => ['El formulario venció. Intentá de nuevo.']];
    } else {
        $resultado = AuthController::registrar($_POST);
    }

    if ($resultado['exito']) {
        $exito = true;
    } else {
        $errores = $resultado['errores'];
    }
}

// Traemos la lista de localidades para llenar los <select> del formulario.
// Se pide siempre (haya habido error o no) porque el formulario se vuelve
// a mostrar si algo salió mal.
$pdo = Database::getConexion();
$localidades = (new Localidad($pdo))->listarTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">
<div class="container py-5 d-flex justify-content-center">
    <div class="card shadow p-4" style="width: 100%; max-width: 520px;">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img src="assets/img/logo.png" alt="FixNow" class="logo-marca">
            <h1 class="h4 mb-0">Crear cuenta en FixNow</h1>
        </div>

        <?php if ($exito): ?>
            <!-- Paso 7 del CU-01: mensaje de confirmación. -->
            <div class="alert alert-success">
                ✅ ¡Cuenta creada correctamente! Ya podés
                <a href="index.php" class="alert-link">iniciar sesión</a>.
            </div>
        <?php else: ?>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= Vista::e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- id="formRegistro" se usa desde main.js para mostrar/ocultar
                 los campos de "Trabajador" dinámicamente (ver assets/js/main.js) -->
            <form method="post" id="formRegistro" novalidate>
                <?= Vista::campoCsrf() /* Fase 8: token anti-CSRF */ ?>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre"
                               value="<?= Vista::e(Seguridad::texto($_POST['nombre'] ?? '')) ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" class="form-control" name="apellido"
                               value="<?= Vista::e(Seguridad::texto($_POST['apellido'] ?? '')) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mail</label>
                    <input type="email" class="form-control" name="mail"
                           value="<?= Vista::e(Seguridad::texto($_POST['mail'] ?? '')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <!-- Cambio 1.1: ojito para ver / ocultar la contraseña -->
                    <div class="pw-wrap">
                        <input type="password" class="form-control" name="contrasena" required>
                        <?= Vista::ojoPassword() ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tu localidad</label>
                    <select class="form-select" name="id_localidad" required>
                        <option value="" disabled selected>Elegí tu localidad</option>
                        <?php foreach ($localidades as $localidad): ?>
                            <option value="<?= $localidad['id_localidad'] ?>">
                                <?= htmlspecialchars($localidad['nombre']) ?>, <?= htmlspecialchars($localidad['provincia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>

                <!-- CU-01 paso 3: "¿Es trabajador? (SI/NO)" -->
                <div class="mb-3">
                    <label class="form-label d-block">¿Sos trabajador/a de oficio?</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="es_trabajador" id="esTrabajadorNo" value="no" checked>
                        <label class="form-check-label" for="esTrabajadorNo">No, soy cliente</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="es_trabajador" id="esTrabajadorSi" value="si">
                        <label class="form-check-label" for="esTrabajadorSi">Sí, ofrezco servicios</label>
                    </div>
                </div>

                <!-- Estos campos solo se muestran (vía JS) si eligió "Sí".
                     El div empieza oculto con la clase d-none de Bootstrap. -->
                <div id="camposTrabajador" class="d-none border rounded p-3 bg-light-subtle mb-3">
                    <div class="mb-3">
                        <label class="form-label">Profesión / Oficio</label>
                        <input type="text" class="form-control" name="oficio" placeholder="Ej: Plomero, Electricista">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Localidades / zonas de trabajo</label>
                        <!-- multiple: permite seleccionar varias opciones (Ctrl+clic) -->
                        <select class="form-select" name="localidades_trabajo[]" multiple size="4">
                            <?php foreach ($localidades as $localidad): ?>
                                <option value="<?= $localidad['id_localidad'] ?>">
                                    <?= htmlspecialchars($localidad['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Mantené presionado Ctrl (o Cmd en Mac) para elegir varias.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Aceptar</button>
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/password.js"></script>
</body>
</html>
