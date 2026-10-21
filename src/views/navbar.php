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
if ($rolNav === 'cliente') {
    $preguntasNav = $solicitudNav->preguntasFinalizacionPendientes($idUsuarioNav, 'cliente');
} elseif ($rolNav === 'trabajador') {
    // Fase 8b (cambio 3.4): un trabajador recibe las preguntas de los trabajos que le
    // contrataron (como trabajador) Y las de los que él contrató (actuando como cliente).
    $preguntasNav = array_merge(
        $solicitudNav->preguntasFinalizacionPendientes($idUsuarioNav, 'trabajador'),
        $solicitudNav->preguntasFinalizacionPendientes($idUsuarioNav, 'cliente')
    );
}

// Fase 8b (cambio 1.3): datos para la miniatura y el nombre del menú de perfil.
// Solo los trabajadores tienen foto; el resto muestra sus iniciales.
$usuarioNav = (new Usuario($pdoNav))->buscarPorId($idUsuarioNav) ?? ['nombre' => '', 'apellido' => ''];
$fotoNav = ($rolNav === 'trabajador') ? (new Trabajador($pdoNav))->fotoDeUsuario($idUsuarioNav) : null;
// A dónde lleva el perfil según el rol: el trabajador tiene "Mi perfil"; cliente y admin, "Mi cuenta".
$perfilNav = ($rolNav === 'trabajador') ? 'mi_perfil.php' : 'mi_cuenta.php';
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
                <li class="nav-item"><a class="nav-link" href="panel_cliente.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="chats.php">Mis chats</a></li>
                <li class="nav-item"><a class="nav-link" href="mis_trabajos.php">Mis trabajos</a></li>
            <?php elseif ($rolNav === 'trabajador'): ?>
                <!-- Fase 8b (cambio 3.4): el trabajador también puede buscar y contratar a otros -->
                <li class="nav-item"><a class="nav-link" href="panel_cliente.php">Inicio</a></li>
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
                <!-- Fase 8b (cambio 3.3): "Mis calificaciones" ahora vive dentro de "Mi perfil" -->
            <?php elseif ($rolNav === 'admin'): ?>
                <?php
                // Fase 7: menú del Administrador. Cada ítem muestra un globito rojo si hay algo pendiente.
                $itemsAdmin = [
                    ['admin_habilitar.php',      'Habilitar trabajadores', $adminNav['trabajadores']],
                    ['admin_usuarios.php',       'Usuarios',               0],
                    ['admin_moderacion.php',     'Moderar contenido',      $adminNav['contenido']],
                    ['admin_calificaciones.php', 'Calificaciones',         $adminNav['calificaciones']],
                    ['admin_reportes.php',       'Estadísticas',           0],
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

        <?php /* Fase 8b (cambio 1.3): miniatura circular + nombre; es un enlace al perfil.
                 El botón de salir ya no está acá: se movió al final de la vista de perfil (cambio 1.4). */ ?>
        <a href="<?= Vista::e($perfilNav) ?>" class="perfil-nav d-flex align-items-center gap-2 text-decoration-none"
           title="Ir a mi perfil">
            <?= Vista::avatar($fotoNav, (string) $usuarioNav['nombre'], (string) $usuarioNav['apellido'], 36) ?>
            <span class="perfil-nav-nombre small fw-semibold d-none d-sm-inline"><?= Vista::e($usuarioNav['nombre']) ?></span>
        </a>
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
        // Fase 8b: se decide por la solicitud (no por el rol de la cuenta), porque un
        // trabajador puede ser el cliente en unas y el trabajador en otras.
        $otraParte = ((int) $p['id_usuario'] === $idUsuarioNav)
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
