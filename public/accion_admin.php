<?php
// public/accion_admin.php
// ---------------------------------------------------------------------------
// Recibe los botones del panel del Administrador (habilitar, bloquear,
// aprobar, eliminar, etc.). No muestra nada: ejecuta la acción mediante
// AdminController, deja un aviso y vuelve a la pantalla de origen
// (patrón Post/Redirect/Get, igual que accion.php).
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

// Solo un administrador con sesión vigente puede estar acá.
Sesion::requerirRol('admin');

// Solo POST: nunca una acción por simple enlace/URL.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel_admin.php');
    exit;
}

// A dónde volver: solo direcciones internas simples tipo "admin_usuarios.php?rol=cliente".
$volver = $_POST['volver'] ?? '';
if (!preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%+.@\-]*)?$/', $volver)) {
    $volver = 'panel_admin.php';
}

if (!Sesion::csrfValido($_POST['csrf'] ?? null)) {
    Sesion::flash('danger', 'El formulario venció. Intentá de nuevo.');
    header('Location: ' . $volver);
    exit;
}

$resultado = AdminController::ejecutar(
    Sesion::idUsuarioActual(),
    Sesion::rolActual(),
    (string) ($_POST['accion'] ?? ''),
    (int) ($_POST['id'] ?? 0),
    (string) ($_POST['motivo'] ?? '')
);

Sesion::flash($resultado['exito'] ? 'success' : 'danger', $resultado['mensaje']);
header('Location: ' . $volver);
exit;
