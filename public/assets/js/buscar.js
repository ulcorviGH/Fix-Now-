// public/assets/js/buscar.js
// ---------------------------------------------------------------------------
// Búsqueda por radio en km (Fase 8b, cambio 2.2) + MAPA interactivo (Fase 9, punto 3).
// Lo usa public/panel_cliente.php ("Inicio"). Hace esto:
//   1) Muestra el valor del slider mientras se mueve.
//   2) Pide la ubicación al navegador (navigator.geolocation) y la guarda en los
//      campos ocultos lat / lng para que viajen con el formulario.
//   3) Dibuja un mapa (Leaflet + OpenStreetMap) centrado en esa ubicación, con un
//      CÍRCULO de cobertura cuyo radio sigue al slider en tiempo real.
//   4) El marcador se puede arrastrar (o tocar el mapa) para buscar desde otro punto.
//   5) Si el usuario niega el permiso, o tilda "Usar mi localidad", no manda
//      coordenadas y el servidor usa la localidad del usuario (el mapa se centra ahí).
// En localhost el navegador permite geolocation aunque no haya HTTPS.
// Si Leaflet no cargó (sin internet) el buscador sigue funcionando sin mapa.
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
    const contenedorMapa = document.getElementById('mapaBusqueda');

    // ------------------------------------------------------------------
    // Mapa (solo si Leaflet está disponible)
    // ------------------------------------------------------------------
    let mapa = null;
    let marcador = null;
    let circulo = null;

    const numero = (texto) => (texto !== undefined && texto !== '' && !isNaN(Number(texto))) ? Number(texto) : null;
    const kmActuales = () => Number(slider.value) || 1;

    // Centro inicial: lo que ya trajo la URL (búsqueda anterior) o, si no, la localidad del usuario.
    function centroInicial() {
        const lat = numero(campoLat.value), lng = numero(campoLng.value);
        if (lat !== null && lng !== null) return [lat, lng];
        const latL = numero(contenedorMapa && contenedorMapa.dataset.latLocalidad);
        const lngL = numero(contenedorMapa && contenedorMapa.dataset.lngLocalidad);
        if (latL !== null && lngL !== null) return [latL, lngL];
        return [-34.6, -58.4]; // último recurso: Buenos Aires
    }

    // Ajusta el zoom para que el círculo entero se vea, sea de 1 o de 100 km.
    function encuadrar() {
        if (mapa && circulo) mapa.fitBounds(circulo.getBounds(), { padding: [12, 12], animate: false });
    }

    function iniciarMapa() {
        if (!contenedorMapa || !window.L) {
            if (contenedorMapa) contenedorMapa.innerHTML = '<div class="p-3 small text-muted">No se pudo cargar el mapa (¿sin internet?). El buscador funciona igual.</div>';
            return;
        }
        const centro = centroInicial();
        mapa = L.map(contenedorMapa).setView(centro, 10);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(mapa);

        // Círculo semitransparente: el radio de cobertura (el mapa trabaja en METROS).
        circulo = L.circle(centro, {
            radius: kmActuales() * 1000,
            color: '#C1392B', weight: 2, fillColor: '#C1392B', fillOpacity: 0.15,
        }).addTo(mapa);

        marcador = L.marker(centro, { draggable: true }).addTo(mapa);
        marcador.on('dragend', () => ubicacionElegida(marcador.getLatLng().lat, marcador.getLatLng().lng));
        mapa.on('click', (e) => { marcador.setLatLng(e.latlng); ubicacionElegida(e.latlng.lat, e.latlng.lng); });

        encuadrar();
    }

    // Mueve círculo y marcador a un punto nuevo (sin tocar los campos del formulario).
    function moverMapa(lat, lng) {
        if (!mapa) return;
        marcador.setLatLng([lat, lng]);
        circulo.setLatLng([lat, lng]);
        encuadrar();
    }

    // La persona marcó un punto a mano: pasa a ser el origen de la búsqueda.
    function ubicacionElegida(lat, lng) {
        campoLat.value = lat.toFixed(6);
        campoLng.value = lng.toFixed(6);
        casilla.checked = false;               // marcar un punto invalida "usar mi localidad"
        circulo.setLatLng([lat, lng]);
        encuadrar();
        estado.textContent = '📍 Buscando desde el punto que marcaste en el mapa.';
    }

    // ------------------------------------------------------------------
    // 1) El número al lado de "Radio de búsqueda" y el círculo siguen al slider EN VIVO.
    // ------------------------------------------------------------------
    slider.addEventListener('input', () => {
        valor.textContent = slider.value;
        if (circulo) {
            circulo.setRadius(kmActuales() * 1000);
            encuadrar();
        }
    });

    // ------------------------------------------------------------------
    // 2) Ubicación
    // ------------------------------------------------------------------
    function usarLocalidad(motivo) {
        campoLat.value = '';
        campoLng.value = '';
        estado.textContent = '📍 Se usará la ubicación de tu localidad' + (motivo ? ' (' + motivo + ')' : '') + '.';
        const lat = numero(contenedorMapa && contenedorMapa.dataset.latLocalidad);
        const lng = numero(contenedorMapa && contenedorMapa.dataset.lngLocalidad);
        if (lat !== null && lng !== null) moverMapa(lat, lng);
    }

    function pedirUbicacion() {
        if (casilla.checked) { usarLocalidad('elegido por vos'); return; }
        if (!('geolocation' in navigator)) { usarLocalidad('tu navegador no tiene GPS'); return; }
        estado.textContent = '📍 Buscando tu ubicación…';
        navigator.geolocation.getCurrentPosition(
            (posicion) => {
                const lat = posicion.coords.latitude, lng = posicion.coords.longitude;
                campoLat.value = lat.toFixed(6);   // precisión de ~10 cm, más que suficiente
                campoLng.value = lng.toFixed(6);
                estado.textContent = '📍 Usando tu ubicación actual.';
                moverMapa(lat, lng);
            },
            () => usarLocalidad('no diste permiso de ubicación'),
            { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
        );
    }

    casilla.addEventListener('change', pedirUbicacion);

    iniciarMapa();

    // Si la página se abrió con una búsqueda hecha (lat/lng en la URL), se respeta ese punto:
    // volver a pedir el GPS pisaría el lugar que la persona había marcado en el mapa.
    if (campoLat.value !== '' && campoLng.value !== '' && !casilla.checked) {
        estado.textContent = '📍 Usando el punto de tu búsqueda anterior (podés moverlo en el mapa).';
    } else {
        pedirUbicacion();
    }
})();
