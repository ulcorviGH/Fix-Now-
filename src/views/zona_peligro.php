<?php
// src/views/zona_peligro.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 4): "Eliminar cuenta", al final del perfil de cliente y trabajador.
// El botón rojo abre una ventana (modal) de confirmación ESTRICTA: hay que escribir
// la palabra ELIMINAR y la contraseña actual. El botón de confirmar recién se
// habilita cuando la palabra coincide (assets/js/cuenta.js), pero la validación
// real se hace en el servidor (CuentaController::eliminarPropia).
// El administrador no ve esta sección: su cuenta no se puede borrar desde la web.
// ---------------------------------------------------------------------------
if (Sesion::rolActual() === 'admin') {
    return;
}
?>
<div class="zona-peligro mt-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <strong class="text-danger">Eliminar cuenta</strong>
        <div class="small text-muted">Se borran tu perfil, tus chats, tus trabajos y tus calificaciones. No se puede deshacer.</div>
    </div>
    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminarCuenta">
        Eliminar cuenta
    </button>
</div>

<div class="modal fade" id="modalEliminarCuenta" tabindex="-1" aria-labelledby="tituloEliminarCuenta" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="eliminar_cuenta.php" class="modal-content" autocomplete="off"
              data-palabra="<?= Vista::e(Config::PALABRA_ELIMINAR_CUENTA) ?>" id="formEliminarCuenta">
            <?= Vista::campoCsrf() ?>
            <div class="modal-header">
                <h2 class="modal-title h5 text-danger" id="tituloEliminarCuenta">¿Eliminar tu cuenta para siempre?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger small">
                    <strong>Esta acción es permanente.</strong> Se van a borrar tus datos, tus conversaciones,
                    los trabajos y las calificaciones asociadas<?= Sesion::rolActual() === 'trabajador' ? ', tus servicios y tus fotos' : '' ?>.
                </div>
                <div class="mb-3">
                    <label class="form-label small" for="confirmacionEliminar">
                        Para confirmar, escribí <strong><?= Vista::e(Config::PALABRA_ELIMINAR_CUENTA) ?></strong>
                    </label>
                    <input type="text" class="form-control" id="confirmacionEliminar" name="confirmacion"
                           autocomplete="off" spellcheck="false" placeholder="<?= Vista::e(Config::PALABRA_ELIMINAR_CUENTA) ?>">
                </div>
                <div class="mb-1">
                    <label class="form-label small" for="contrasenaEliminar">Tu contraseña actual</label>
                    <div class="pw-wrap">
                        <input type="password" class="form-control" id="contrasenaEliminar" name="contrasena"
                               autocomplete="current-password">
                        <?= Vista::ojoPassword() ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" id="botonConfirmarEliminar" disabled>Eliminar mi cuenta definitivamente</button>
            </div>
        </form>
    </div>
</div>
