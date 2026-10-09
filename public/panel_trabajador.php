<?php
// public/panel_trabajador.php
// ---------------------------------------------------------------------------
// Panel al que llega un Trabajador después de loguearse.
// Placeholder: en próximas fases va a tener "Mi perfil", "Mis servicios",
// "Solicitudes" y "Mis calificaciones".
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('trabajador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FixNow - Panel Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h1 class="h4">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?> 👋</h1>
    <p class="text-muted">Panel de Trabajador. Acá van a ir tu perfil, servicios y solicitudes (próximas fases).</p>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
</body>
</html>
