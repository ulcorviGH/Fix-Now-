<?php
// src/views/boton_logout.php
// ---------------------------------------------------------------------------
// Botón "Cerrar sesión". Fase 9 (punto 4): ahora es DISCRETO (botón chico, gris,
// con un ícono) y va arriba, junto al título de la pantalla de perfil de cada rol:
//   - Trabajador: public/mi_perfil.php
//   - Cliente y Administrador: public/mi_cuenta.php
// Es un formulario POST con token CSRF (ver public/logout.php).
// ---------------------------------------------------------------------------
?>
<form method="post" action="logout.php" class="d-inline">
    <?= Vista::campoCsrf() ?>
    <button type="submit" class="btn btn-salir btn-sm d-inline-flex align-items-center gap-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        Cerrar sesión
    </button>
</form>
