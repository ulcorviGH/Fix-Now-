// public/assets/js/cuenta.js
// ---------------------------------------------------------------------------
// Fase 9 (punto 4) - Comportamiento de "Mi cuenta" / "Mi perfil":
//   1) "Eliminar cuenta": el botón de confirmar solo se habilita cuando el campo
//      contiene EXACTAMENTE la palabra pedida (data-palabra) y hay una contraseña.
//      (Es una ayuda visual: el servidor vuelve a comprobar las dos cosas.)
//   2) Si el servidor devolvió un error al editar el perfil, vuelve a abrir esa
//      ventana (<body data-abrir-modal="modalEditarPerfil">) con lo que se escribió.
// ---------------------------------------------------------------------------
(function () {
    const formulario = document.getElementById('formEliminarCuenta');
    if (formulario) {
        const palabra = formulario.dataset.palabra;
        const texto = formulario.querySelector('#confirmacionEliminar');
        const clave = formulario.querySelector('#contrasenaEliminar');
        const boton = formulario.querySelector('#botonConfirmarEliminar');
        const revisar = () => { boton.disabled = !(texto.value === palabra && clave.value !== ''); };
        texto.addEventListener('input', revisar);
        clave.addEventListener('input', revisar);
        // Al cerrar la ventana se limpian los campos, para no dejar la contraseña escrita.
        document.getElementById('modalEliminarCuenta').addEventListener('hidden.bs.modal', () => {
            formulario.reset();
            revisar();
        });
    }

    const abrir = document.body.dataset.abrirModal;
    if (abrir && window.bootstrap) {
        const modal = document.getElementById(abrir);
        if (modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
})();
