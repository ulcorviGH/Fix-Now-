<?php
// src/views/selector_localidades.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 7). Selector de localidades con AUTOCOMPLETADO. Reemplaza a los
// checkboxes fijos: se escribe, aparecen sugerencias de localidades de Argentina
// (api/localidades.php) y cada elección queda como una "etiqueta" removible.
// Lo dibuja el JavaScript assets/js/localidades.js; este fragmento deja el HTML
// inicial (con las localidades ya elegidas, para poder editar o volver a mostrar
// el formulario después de un error).
//
// Se incluye con require. Variables que espera:
//   $selId         id único del componente (ej. 'zonasTrabajo')
//   $selCampo      name de los inputs ocultos (ej. 'localidades[]' o 'id_localidad')
//   $selMax        cuántas se pueden elegir (1 = una sola; reemplaza a la anterior)
//   $selElegidas   filas ya elegidas: [id_localidad, nombre, provincia]
//   $selPlaceholder texto del buscador
// ---------------------------------------------------------------------------
?>
<div class="selector-localidades" id="<?= Vista::e($selId) ?>"
     data-campo="<?= Vista::e($selCampo) ?>" data-max="<?= (int) $selMax ?>" data-api="api/localidades.php">
    <!-- Etiquetas de las localidades elegidas; cada una lleva un input oculto que se envía con el formulario -->
    <div class="selector-etiquetas" data-etiquetas>
        <?php foreach ($selElegidas as $l): ?>
            <span class="etiqueta-localidad" data-id="<?= (int) $l['id_localidad'] ?>">
                <input type="hidden" name="<?= Vista::e($selCampo) ?>" value="<?= (int) $l['id_localidad'] ?>">
                <?= Vista::e($l['nombre']) ?>, <?= Vista::e($l['provincia']) ?>
                <button type="button" class="etiqueta-quitar" aria-label="Quitar <?= Vista::e($l['nombre']) ?>">×</button>
            </span>
        <?php endforeach; ?>
    </div>
    <div class="position-relative">
        <input type="text" class="form-control" autocomplete="off" maxlength="60"
               placeholder="<?= Vista::e($selPlaceholder) ?>"
               role="combobox" aria-autocomplete="list" aria-expanded="false" data-buscador>
        <ul class="list-group selector-sugerencias d-none" role="listbox" data-sugerencias></ul>
    </div>
    <div class="form-text" data-aviso>
        <?= (int) $selMax === 1 ? 'Escribí al menos 2 letras y elegí una localidad.' : 'Escribí al menos 2 letras y elegí; podés agregar hasta ' . (int) $selMax . '.' ?>
    </div>
</div>
