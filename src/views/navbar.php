<?php
// src/views/navbar.php
// ---------------------------------------------------------------------------
// Barra superior compartida por las pantallas con sesión iniciada.
// Se incluye con:  require __DIR__ . '/../src/views/navbar.php';
// Está en src/ (no en public/) porque no es una página: es un fragmento.
// ---------------------------------------------------------------------------
?>
<nav class="navbar navbar-expand bg-white border-bottom shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="panel_<?= Vista::e(Sesion::rolActual()) ?>.php">🔧 FixNow</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-muted small">Hola, <?= Vista::e($_SESSION['nombre'] ?? '') ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
        </div>
    </div>
</nav>
