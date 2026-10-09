<?php
// public/index.php
// ---------------------------------------------------------------------------
// Pantalla de entrada del sitio (CU-01 paso 1 / CU-02 "Iniciar sesión").
// Esta página hace 2 cosas:
//   1) Si llega un POST con mail+contraseña, le pide a AuthController que
//      valide el login y, si es correcto, redirige al panel según el rol.
//   2) Si no hay POST (o el login falló), muestra el formulario.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

// Si ya hay una sesión activa, no tiene sentido mostrar el login de nuevo:
// mandamos directo al panel correspondiente.
if (Sesion::estaLogueado()) {
    header('Location: panel_' . Sesion::rolActual() . '.php');
    exit;
}

$mensajeError = '';

// $_SERVER['REQUEST_METHOD'] nos dice si el navegador envió el formulario
// (POST) o si solo está pidiendo ver la página (GET).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = AuthController::login($_POST['mail'] ?? '', $_POST['contrasena'] ?? '');

    if ($resultado['exito']) {
        // Paso 4 y 5 del CU-02: identificar el rol y redirigir al panel.
        Sesion::login($resultado['usuario']);
        header('Location: panel_' . $resultado['usuario']['rol'] . '.php');
        exit;
    }

    $mensajeError = $resultado['mensaje'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <h1 class="h4 mb-3 text-center">🔧 FixNow</h1>
        <p class="text-muted text-center small mb-4">Plataforma de servicios y oficios</p>

        <?php if ($mensajeError !== ''): ?>
            <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($mensajeError) ?></div>
        <?php endif; ?>

        <!-- CU-02: formulario de login. method="post" evita que mail/contraseña
             viajen visibles en la URL (a diferencia de method="get"). -->
        <form method="post" novalidate>
            <div class="mb-3">
                <label for="mail" class="form-label">Mail</label>
                <input type="email" class="form-control" id="mail" name="mail" required>
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>

        <hr>

        <a href="registro.php" class="btn btn-outline-secondary w-100">Registrarse</a>

        <div class="text-center mt-3">
            <!-- CU-01 excepción 1.1: fuera del alcance del proyecto, son solo
                 enlaces informativos por ahora. -->
            <a href="#" class="small text-muted me-3">Términos de servicio</a>
            <a href="#" class="small text-muted">Política de privacidad</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
