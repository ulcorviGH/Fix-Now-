<?php
// src/views/boton_logout.php
// ---------------------------------------------------------------------------
// Fase 8b (cambio 1.4): botón rojo "Cerrar sesión". Ya no está en la barra
// superior: se incluye AL FINAL de la vista de perfil de cada rol
//   - Trabajador: public/mi_perfil.php
//   - Cliente y Administrador: public/mi_cuenta.php
// Es un formulario POST con token CSRF (ver public/logout.php).
// ---------------------------------------------------------------------------
?>
<form method="post" action="logout.php" class="mt-4 text-center">
    <?= Vista::campoCsrf() ?>
    <button type="submit" class="btn btn-danger btn-lg px-5 boton-logout">Cerrar sesión</button>
</form>
