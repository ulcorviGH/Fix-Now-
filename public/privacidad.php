<?php
// public/privacidad.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 1): página legal independiente "Política de privacidad".
// Es PÚBLICA (se puede leer sin iniciar sesión, la enlazan el login y el registro).
// El texto es de PRUEBA (Lorem Ipsum legal): reemplazarlo por el real antes de publicar.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Política de privacidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 820px;">

    <!-- Botón estandarizado "← Volver" (cambio 1.5 de la Fase 8b): usa el historial; si no hay, va al login -->
    <a href="index.php" class="btn-volver mb-3" data-volver>← Volver</a>

    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="assets/img/logo.png" alt="FixNow" class="logo-marca">
                <h1 class="h3 mb-0">Política de privacidad</h1>
            </div>
            <p class="text-muted small">Última actualización: 1 de enero de 2026 · <strong>Texto de prueba (Lorem Ipsum legal)</strong></p>
            <hr>

        <h2 class="h5 mt-4">1. Información que recopilamos</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        <h2 class="h5 mt-4">2. Cómo usamos la información</h2>
        <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        <h2 class="h5 mt-4">3. Ubicación</h2>
        <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. La ubicación del dispositivo solo se usa para ordenar y filtrar los trabajadores cercanos.</p>
        <h2 class="h5 mt-4">4. Compartición de datos</h2>
        <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>
        <h2 class="h5 mt-4">5. Seguridad y conservación</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        <h2 class="h5 mt-4">6. Derechos del usuario</h2>
        <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Podés editar o eliminar tu cuenta en cualquier momento desde tu perfil.</p>
        <h2 class="h5 mt-4">7. Contacto</h2>
        <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
        </div>
    </div>
</div>
<script src="assets/js/volver.js"></script>
</body>
</html>
