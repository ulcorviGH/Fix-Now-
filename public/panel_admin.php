<?php
// public/panel_admin.php
// ---------------------------------------------------------------------------
// Panel al que llega el Administrador después de loguearse.
// Placeholder: en la Fase 7 va a tener habilitación de trabajadores,
// gestión de usuarios, moderación y reportes.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FixNow - Panel Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h1 class="h4">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?> 👋</h1>
    <p class="text-muted">Panel de Administrador. Acá va a ir la gestión de usuarios y moderación (Fase 7).</p>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
</body>
</html>
