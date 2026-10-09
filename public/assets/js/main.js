// public/assets/js/main.js
// ---------------------------------------------------------------------------
// JavaScript general de FixNow.
// ---------------------------------------------------------------------------

console.log('FixNow cargado correctamente.');

// ---------------------------------------------------------------------------
// Registro (CU-01): mostrar/ocultar los campos de "Oficio" y "Localidades
// de trabajo" según si la persona marca que es Trabajador o no.
// Esto es solo VISUAL: la validación real de "obligatorio si es trabajador"
// se hace en el servidor, en AuthController::registrar().
// ---------------------------------------------------------------------------
const formRegistro = document.getElementById('formRegistro');

if (formRegistro) {
    const radiosEsTrabajador = formRegistro.querySelectorAll('input[name="es_trabajador"]');
    const camposTrabajador = document.getElementById('camposTrabajador');

    function actualizarCamposTrabajador() {
        const esTrabajador = formRegistro.querySelector('input[name="es_trabajador"]:checked').value === 'si';
        camposTrabajador.classList.toggle('d-none', !esTrabajador);
    }

    radiosEsTrabajador.forEach(function (radio) {
        radio.addEventListener('change', actualizarCamposTrabajador);
    });

    // Por si el navegador recuerda una selección anterior al recargar la página.
    actualizarCamposTrabajador();
}
