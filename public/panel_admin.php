<?php
// public/panel_admin.php
// ---------------------------------------------------------------------------
// Pantalla de inicio del Administrador después de loguearse (CU-02 paso 5).
// Muestra de un vistazo qué está esperando su revisión y lleva a cada
// pantalla de gestión (Fase 7: CU-13 a CU-19).
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');

$pendientes = (new Estadistica(Database::getConexion()))->pendientesAdmin();

// Cada tarjeta: [enlace, número a mostrar (o null), texto, estilo de borde]
$tarjetas = [
    ['admin_habilitar.php',      $pendientes['trabajadores'],   'cuentas de trabajador por habilitar (CU-18)'],
    ['admin_moderacion.php',     $pendientes['contenido'],      'servicios y fotos por revisar (CU-15, CU-19)'],
    ['admin_calificaciones.php', $pendientes['calificaciones'], 'calificaciones reportadas (CU-14)'],
    ['admin_usuarios.php',       null,                          'Gestionar usuarios: bloquear, activar o eliminar (CU-13)'],
    ['admin_reportes.php',       null,                          'Reportes y estadísticas de la plataforma (CU-16)'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Panel Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h4 mb-4">Hola, <?= Vista::e($_SESSION['nombre']) ?> 👋</h1>

    <div class="row g-3">
        <?php foreach ($tarjetas as [$enlace, $numero, $texto]): ?>
            <div class="col-md-6">
                <a href="<?= $enlace ?>" class="text-decoration-none text-reset">
                    <div class="card shadow-sm tarjeta-trabajador h-100 <?= ($numero !== null && $numero > 0) ? 'border-danger' : '' ?>">
                        <div class="card-body">
                            <?php if ($numero !== null): ?>
                                <div class="display-6"><?= (int) $numero ?></div>
                            <?php endif; ?>
                            <div class="text-muted"><?= Vista::e($texto) ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
