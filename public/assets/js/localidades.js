// public/assets/js/localidades.js
// ---------------------------------------------------------------------------
// Fase 9 (punto 7) - Autocompletado de localidades de Argentina.
// Convierte cada <div class="selector-localidades"> (ver
// src/views/selector_localidades.php) en un buscador:
//   1) al escribir (2 letras o más) consulta api/localidades.php?q=...
//   2) muestra las sugerencias; se elige con clic o con el teclado
//      (flechas arriba/abajo, Enter, Escape)
//   3) cada elección queda como una etiqueta con un input oculto
//      (name = data-campo) que viaja con el formulario
//   4) data-max indica cuántas se pueden elegir; con 1 la nueva REEMPLAZA a la anterior
// El texto de las sugerencias se escribe con textContent (nunca innerHTML),
// así un nombre raro no puede inyectar HTML.
// ---------------------------------------------------------------------------
(function () {
    function iniciar(componente) {
        const campo = componente.dataset.campo;
        const maximo = parseInt(componente.dataset.max || '1', 10);
        const api = componente.dataset.api;
        const etiquetas = componente.querySelector('[data-etiquetas]');
        const buscador = componente.querySelector('[data-buscador]');
        const lista = componente.querySelector('[data-sugerencias]');
        const aviso = componente.querySelector('[data-aviso]');
        const avisoOriginal = aviso ? aviso.textContent : '';

        let temporizador = null;
        let peticion = 0;       // número de la última consulta: ignora respuestas viejas que lleguen tarde
        let sugerencias = [];
        let activa = -1;

        const idsElegidos = () => Array.from(etiquetas.querySelectorAll('input[type="hidden"]')).map((i) => i.value);

        function avisar(texto) { if (aviso) aviso.textContent = texto || avisoOriginal; }

        function cerrarLista() {
            lista.classList.add('d-none');
            lista.innerHTML = '';
            buscador.setAttribute('aria-expanded', 'false');
            sugerencias = [];
            activa = -1;
        }

        function pintarActiva() {
            Array.from(lista.children).forEach((li, i) => li.classList.toggle('active', i === activa));
        }

        // Agrega una etiqueta con su input oculto.
        function agregar(item) {
            if (idsElegidos().includes(String(item.id))) { avisar('Esa localidad ya está elegida.'); return; }
            if (maximo === 1) {
                etiquetas.innerHTML = '';                    // una sola: reemplaza
            } else if (idsElegidos().length >= maximo) {
                avisar('Llegaste al máximo de ' + maximo + ' localidades.');
                return;
            }
            const etiqueta = document.createElement('span');
            etiqueta.className = 'etiqueta-localidad';
            etiqueta.dataset.id = item.id;

            const oculto = document.createElement('input');
            oculto.type = 'hidden';
            oculto.name = campo;
            oculto.value = item.id;

            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'etiqueta-quitar';
            boton.setAttribute('aria-label', 'Quitar ' + item.nombre);
            boton.textContent = '×';

            etiqueta.append(oculto, document.createTextNode(item.nombre + ', ' + item.provincia + ' '), boton);
            etiquetas.appendChild(etiqueta);

            buscador.value = '';
            avisar('');
            cerrarLista();
        }

        function mostrar(items) {
            lista.innerHTML = '';
            sugerencias = items;
            activa = -1;
            if (items.length === 0) {
                const vacio = document.createElement('li');
                vacio.className = 'list-group-item small text-muted';
                vacio.textContent = 'No encontramos esa localidad.';
                lista.appendChild(vacio);
            }
            items.forEach((item, i) => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-2';
                li.setAttribute('role', 'option');
                const nombre = document.createElement('strong');
                nombre.textContent = item.nombre;
                const prov = document.createElement('span');
                prov.className = 'text-muted small';
                prov.textContent = ', ' + item.provincia;
                li.append(nombre, prov);
                // mousedown (y no click) para que se elija antes de que el buscador pierda el foco
                li.addEventListener('mousedown', (e) => { e.preventDefault(); agregar(item); });
                li.addEventListener('mouseenter', () => { activa = i; pintarActiva(); });
                lista.appendChild(li);
            });
            lista.classList.remove('d-none');
            buscador.setAttribute('aria-expanded', 'true');
        }

        async function consultar(texto) {
            const numero = ++peticion;
            try {
                const respuesta = await fetch(api + '?q=' + encodeURIComponent(texto), { headers: { Accept: 'application/json' } });
                if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
                const datos = await respuesta.json();
                if (numero !== peticion) return;             // llegó una respuesta más nueva: esta ya no sirve
                mostrar(Array.isArray(datos) ? datos : []);
            } catch (e) {
                if (numero === peticion) { cerrarLista(); avisar('No se pudo buscar ahora. Probá de nuevo.'); }
            }
        }

        buscador.addEventListener('input', () => {
            clearTimeout(temporizador);
            const texto = buscador.value.trim();
            if (texto.length < 2) { peticion++; cerrarLista(); avisar(''); return; }
            temporizador = setTimeout(() => consultar(texto), 250);   // espera 250 ms sin teclear
        });

        buscador.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' && sugerencias.length) { e.preventDefault(); activa = (activa + 1) % sugerencias.length; pintarActiva(); }
            else if (e.key === 'ArrowUp' && sugerencias.length) { e.preventDefault(); activa = (activa - 1 + sugerencias.length) % sugerencias.length; pintarActiva(); }
            else if (e.key === 'Enter') {
                // Enter nunca debe enviar el formulario desde el buscador
                e.preventDefault();
                if (activa >= 0 && sugerencias[activa]) agregar(sugerencias[activa]);
                else if (sugerencias.length === 1) agregar(sugerencias[0]);
            }
            else if (e.key === 'Escape') { cerrarLista(); }
        });

        buscador.addEventListener('blur', () => setTimeout(cerrarLista, 150));

        // Quitar una etiqueta (delegación de eventos: funciona también con las agregadas después)
        etiquetas.addEventListener('click', (e) => {
            const boton = e.target.closest('.etiqueta-quitar');
            if (boton) { boton.closest('.etiqueta-localidad').remove(); avisar(''); }
        });
    }

    document.querySelectorAll('.selector-localidades').forEach(iniciar);
})();
