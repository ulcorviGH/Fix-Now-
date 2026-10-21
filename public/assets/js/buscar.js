// public/assets/js/buscar.js
// ---------------------------------------------------------------------------
// Fase 8b (cambio 2.2) - Búsqueda por radio en km.
// Lo usa public/panel_cliente.php ("Inicio"). Hace tres cosas:
//   1) Muestra el valor del slider mientras se mueve.
//   2) Pide la ubicación al navegador (navigator.geolocation) y la guarda en
//      los campos ocultos lat / lng para que viajen con el formulario.
//   3) Si el usuario niega el permiso, o tilda "Usar mi localidad", no manda
//      coordenadas y el servidor usa la localidad del usuario.
// En localhost el navegador permite geolocation aunque no haya HTTPS.
// ---------------------------------------------------------------------------
(function () {
    const formulario = document.getElementById('formBusqueda');
    if (!formulario) return; // esta pantalla no tiene buscador

    const slider = document.getElementById('radio');
    const valor = document.getElementById('radioValor');
    const campoLat = document.getElementById('lat');
    const campoLng = document.getElementById('lng');
    const casilla = document.getElementById('usarLocalidad');
    const estado = document.getElementById('estadoUbicacion');

    // 1) El número al lado de "Radio de búsqueda" sigue al slider.
    slider.addEventListener('input', () => { valor.textContent = slider.value; });

    // Deja los campos de coordenadas vacíos y avisa qué ubicación se va a usar.
    function usarLocalidad(motivo) {
        campoLat.value = '';
        campoLng.value = '';
        estado.textContent = '📍 Se usará la ubicación de tu localidad' + (motivo ? ' (' + motivo + ')' : '') + '.';
    }

    // 2) Pide la posición al navegador.
    function pedirUbicacion() {
        if (casilla.checked) {
            usarLocalidad('elegido por vos');
            return;
        }
        if (!('geolocation' in navigator)) {
            usarLocalidad('tu navegador no tiene GPS');
            return;
        }
        estado.textContent = '📍 Buscando tu ubicación…';
        navigator.geolocation.getCurrentPosition(
            (posicion) => {
                // toFixed(6): precisión de ~10 cm, más que suficiente.
                campoLat.value = posicion.coords.latitude.toFixed(6);
                campoLng.value = posicion.coords.longitude.toFixed(6);
                estado.textContent = '📍 Usando tu ubicación actual.';
            },
            () => usarLocalidad('no diste permiso de ubicación'),
            { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
        );
    }

    // 3) Si tilda/destilda "Usar mi localidad", se vuelve a decidir de dónde sale la ubicación.
    casilla.addEventListener('change', pedirUbicacion);

    pedirUbicacion();
})();
