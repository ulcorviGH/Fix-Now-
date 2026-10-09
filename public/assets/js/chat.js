// public/assets/js/chat.js
// ---------------------------------------------------------------------------
// Chat en pantalla (CU-08). Hace dos cosas:
//   1) Cada 4 segundos le pide a api/chat.php los mensajes NUEVOS y los agrega.
//   2) Al enviar el formulario, manda el mensaje a la API sin recargar la página.
// Solo se activa si la página tiene el contenedor #chat (o sea, en chat.php).
// ---------------------------------------------------------------------------

(function () {
    const contenedor = document.getElementById('chat');
    if (!contenedor) return;

    const idSolicitud = contenedor.dataset.solicitud;
    const csrf = contenedor.dataset.csrf;
    let ultimoId = parseInt(contenedor.dataset.ultimo, 10) || 0; // último mensaje que ya se ve

    const caja = document.getElementById('mensajes');
    const formulario = document.getElementById('formMensaje');
    const campo = document.getElementById('textoMensaje');
    const boton = document.getElementById('botonEnviar');

    /** Arma la "burbuja" de un mensaje. Se usa textContent (no innerHTML) para que
     *  nadie pueda meter código HTML/JS en un mensaje. */
    function crearBurbuja(m) {
        const fila = document.createElement('div');
        fila.className = 'msg ' + (m.mio ? 'mio' : 'otro');

        const burbuja = document.createElement('div');
        burbuja.className = 'burbuja';
        burbuja.appendChild(document.createTextNode(m.contenido));

        const hora = document.createElement('div');
        hora.className = 'hora';
        hora.textContent = m.hora;
        burbuja.appendChild(hora);

        fila.appendChild(burbuja);
        return fila;
    }

    function irAlFinal() {
        caja.scrollTop = caja.scrollHeight;
    }

    /** Agrega un mensaje si todavía no se había mostrado (evita duplicados). */
    function agregar(m) {
        if (m.id <= ultimoId) return;
        ultimoId = m.id;
        caja.appendChild(crearBurbuja(m));
        irAlFinal();
    }

    async function buscarNuevos() {
        try {
            const respuesta = await fetch(
                'api/chat.php?id=' + encodeURIComponent(idSolicitud) + '&desde=' + ultimoId,
                { credentials: 'same-origin' }
            );
            if (!respuesta.ok) return;
            const datos = await respuesta.json();
            (datos.mensajes || []).forEach(agregar);
        } catch (e) {
            // Sin conexión por un momento: se reintenta en el próximo ciclo.
        }
    }

    // El botón "Enviar" solo está activo si hay texto.
    campo.addEventListener('input', function () {
        boton.disabled = campo.value.trim() === '';
    });

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault(); // evita que el navegador recargue la página
        const texto = campo.value.trim();
        if (texto === '') return;

        boton.disabled = true;
        const datos = new FormData();
        datos.append('csrf', csrf);
        datos.append('id', idSolicitud);
        datos.append('contenido', texto);

        try {
            const respuesta = await fetch('api/chat.php', {
                method: 'POST', body: datos, credentials: 'same-origin'
            });
            const resultado = await respuesta.json();
            if (resultado.ok) {
                campo.value = '';
                agregar(resultado.mensaje);
            } else {
                alert(resultado.error || 'No se pudo enviar el mensaje.');
            }
        } catch (e) {
            alert('Error de conexión. Intentá de nuevo.'); // CU-08 excepción 5.1
        }
        boton.disabled = campo.value.trim() === '';
        campo.focus();
    });

    irAlFinal();
    setInterval(buscarNuevos, 4000);
})();
