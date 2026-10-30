<?php
// public/eliminar_cuenta.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 4): recibe el formulario del modal "Eliminar cuenta"
// (src/views/zona_peligro.php). Solo acepta POST con token CSRF.
//   - Si todo está bien: borra la cuenta, cierra la sesión y vuelve al login
//     con un aviso.
//   - Si algo falla (palabra o contraseña incorrectas): vuelve al perfil con el
//     mensaje de error.
// La lógica y las comprobaciones están en CuentaController::eliminarPropia().
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRoles(['cliente', 'trabajador']);

// A dónde volver si algo sale mal: cada rol tiene su pantalla de perfil.
$perfil = Sesion::rolActual() === 'trabajador' ? 'mi_perfil.php' : 'mi_cuenta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $perfil);
    exit;
}

if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
    Sesion::flash('danger', 'El formulario venció. Intentá de nuevo.');
    header('Location: ' . $perfil);
    exit;
}

$resultado = CuentaController::eliminarPropia(
    Sesion::idUsuarioActual(),
    Seguridad::texto($_POST['confirmacion'] ?? ''),
    Seguridad::texto($_POST['contrasena'] ?? '')
);

if ($resultado['exito']) {
    Sesion::logout();
    header('Location: index.php?cuenta_eliminada=1');
    exit;
}

Sesion::flash('danger', $resultado['mensaje']);
header('Location: ' . $perfil);
exit;
