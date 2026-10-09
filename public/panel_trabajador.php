<?php
// public/panel_trabajador.php
// ---------------------------------------------------------------------------
// Pantalla de inicio del Trabajador después de loguearse (CU-02 paso 5).
// Resume lo pendiente. "Mi perfil", "Mis servicios" y "Mis calificaciones"
// se agregan en las próximas fases.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('trabajador');

$pdo = Database::getConexion();
$idTrabajador = (new Trabajador($pdo))->obtenerIdPorUsuario(Sesion::idUsuarioActual());
$pendientes = $idTrabajador ? (new Solicitud($pdo))->contarPendientes($idTrabajador) : ['contactos' => 0, 'trabajos' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Panel Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5" style="max-width: 860px;">
    <h1 class="h4 mb-4">Hola, <?= Vista::e($_SESSION['nombre']) ?> 👋</h1>

    <div class="row g-3">
        <div class="col-md-6">
            <a href="solicitudes.php?tab=chat" class="text-decoration-none text-reset">
                <div class="card shadow-sm tarjeta-trabajador h-100">
                    <div class="card-body">
                        <div class="display-6"><?= (int) $pendientes['contactos'] ?></div>
                        <div class="text-muted">solicitudes de chat por responder</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="solicitudes.php?tab=trabajo" class="text-decoration-none text-reset">
                <div class="card shadow-sm tarjeta-trabajador h-100">
                    <div class="card-body">
                        <div class="display-6"><?= (int) $pendientes['trabajos'] ?></div>
                        <div class="text-muted">pedidos de trabajo por responder</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
