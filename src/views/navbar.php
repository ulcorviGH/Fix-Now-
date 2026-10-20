<?php
// src/views/navbar.php
// ---------------------------------------------------------------------------
// Barra superior compartida por las pantallas con sesión iniciada. Se incluye con:
//     require __DIR__ . '/../src/views/navbar.php';
// Además de los enlaces, muestra debajo de la barra:
//   1) el aviso "flash" de la última acción (ej. "Solicitud enviada")
//   2) las preguntas "¿Ya se completó el trabajo?" que estén pendientes
//      (CU-21 pasos 6-7), para que le aparezcan al usuario en cualquier pantalla.
// Fase 7: agrega el menú del Administrador y "Mi perfil" del Trabajador.
// Está en src/ (no en public/) porque es un fragmento, no una página.
// Las variables llevan sufijo "Nav" para no pisar las de la página que lo incluye.
// ---------------------------------------------------------------------------

$rolNav = Sesion::rolActual();
$idUsuarioNav = Sesion::idUsuarioActual();
$pdoNav = Database::getConexion();
$solicitudNav = new Solicitud($pdoNav);

$flashNav = Sesion::tomarFlash();
$pendientesNav = ['contactos' => 0, 'trabajos' => 0];
$preguntasNav = [];

if ($rolNav === 'trabajador') {
    $idTrabajadorNav = (new Trabajador($pdoNav))->obtenerIdPorUsuario($idUsuarioNav);
    if ($idTrabajadorNav !== null) {
        $pendientesNav = $solicitudNav->contarPendientes($idTrabajadorNav);
    }
}
if ($rolNav === 'cliente' || $rolNav === 'trabajador') {
    $preguntasNav = $solicitudNav->preguntasFinalizacionPendientes($idUsuarioNav, $rolNav);
}
$totalPendientesNav = (int) $pendientesNav['contactos'] + (int) $pendientesNav['trabajos'];

// Fase 7: el Administrador ve en el menú cuánto tiene esperando revisión.
$adminNav = ['trabajadores' => 0, 'contenido' => 0, 'calificaciones' => 0];
if ($rolNav === 'admin') {
    $adminNav = (new Estadistica($pdoNav))->pendientesAdmin();
}
?>
<nav class="navbar navbar-expand bg-white border-bottom shadow-sm mb-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="panel_<?= Vista::e($rolNav) ?>.php">
            <img src="assets/img/logo.png" alt="" class="logo-marca"> FixNow
        </a>

        <ul class="navbar-nav me-auto">
            <?php if ($rolNav === 'cliente'): ?>
                <li class="nav-item"><a class="nav-link" href="panel_cliente.php">Buscar</a></li>
                <li class="nav-item"><a class="nav-link" href="chats.php">Mis chats</a></li>
                <li class="nav-item"><a class="nav-link" href="mis_trabajos.php">Mis trabajos</a></li>
            <?php elseif ($rolNav === 'trabajador'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="solicitudes.php">
                        Solicitudes
                        <?php if ($totalPendientesNav > 0): ?>
                            <span class="badge text-bg-danger"><?= $totalPendientesNav ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="chats.php">Chats</a></li>
                <li class="nav-item"><a class="nav-link" href="mi_perfil.php">Mi perfil</a></li>
                <li class="nav-item"><a class="nav-link" href="mis_calificaciones.php">Mis calificaciones</a></li>
            <?php elseif ($rolNav === 'admin'): ?>
                <?php
                // Fase 7: menú del Administrador. Cada ítem muestra un globito rojo si hay algo pendiente.
                $itemsAdmin = [
                    ['admin_habilitar.php',      'Habilitar trabajadores', $adminNav['trabajadores']],
                    ['admin_usuarios.php',       'Usuarios',               0],
                    ['admin_moderacion.php',     'Moderar contenido',      $adminNav['contenido']],
                    ['admin_calificaciones.php', 'Calificaciones',         $adminNav['calificaciones']],
                    ['admin_reportes.php',       'Reportes',               0],
                ];
                ?>
                <?php foreach ($itemsAdmin as [$enlace, $texto, $cantidad]): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $enlace ?>">
                            <?= Vista::e($texto) ?>
                            <?php if ($cantidad > 0): ?>
                                <span class="badge text-bg-danger"><?= (int) $cantidad ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-md-inline">Hola, <?= Vista::e($_SESSION['nombre'] ?? '') ?></span>
            <!-- Fase 8: cerrar sesión es un POST con token CSRF, no un simple enlace -->
            <form method="post" action="logout.php" class="d-inline">
                <?= Vista::campoCsrf() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>

<div class="container">
    <?php if ($flashNav !== null): ?>
        <div class="alert alert-<?= Vista::e($flashNav['tipo']) ?> alert-dismissible fade show" role="alert">
            <?= Vista::e($flashNav['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($preguntasNav as $p): ?>
        <?php
        $otraParte = ($rolNav === 'cliente')
            ? $p['trabajador_nombre'] . ' ' . $p['trabajador_apellido']
            : $p['cliente_nombre'] . ' ' . $p['cliente_apellido'];
        ?>
        <!-- CU-21 paso 6: "¿Ya se completó el trabajo?" -->
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2" role="alert">
            <div class="flex-grow-1">
                <strong>¿Ya se completó el trabajo con <?= Vista::e($otraParte) ?>?</strong>
                <a href="chat.php?id=<?= (int) $p['id_solicitud'] ?>" class="ms-2 small">Ver chat</a>
            </div>
            <?= Vista::formAccion('pregunta_si', (int) $p['id_solicitud'], 'Sí, está terminado', 'btn-success btn-sm') ?>
            <?= Vista::formAccion('pregunta_no', (int) $p['id_solicitud'], 'Todavía no', 'btn-outline-secondary btn-sm') ?>
        </div>
    <?php endforeach; ?>
</div>
