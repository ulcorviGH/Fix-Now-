<?php
// src/controllers/CatalogoController.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 7). Validación y consulta de los CATÁLOGOS que usan el registro
// del trabajador y "Mi perfil":
//   - oficios (selección múltiple)
//   - localidades de Argentina (con autocompletado)
// Lo comparten AuthController (CU-01) y TrabajadorController (CU-09) para no
// repetir las mismas reglas en dos lugares.
// ---------------------------------------------------------------------------

class CatalogoController
{
    /**
     * Valida los oficios elegidos (llegan como lista de ids: oficios[]).
     * Devuelve ['ids' => [...], 'texto' => 'Gasista, Plomero', 'error' => null|'mensaje'].
     * 'texto' es lo que se guarda en TRABAJADOR.oficio (resumen para la lista y el buscador).
     */
    public static function resolverOficios($valor): array
    {
        $ids = self::idsEnteros($valor);
        if (empty($ids)) {
            return ['ids' => [], 'texto' => '', 'error' => 'Elegí al menos un oficio.'];
        }
        if (count($ids) > Config::MAX_OFICIOS_POR_TRABAJADOR) {
            return ['ids' => [], 'texto' => '', 'error' => 'Podés elegir hasta ' . Config::MAX_OFICIOS_POR_TRABAJADOR . ' oficios.'];
        }

        // Solo cuentan los que existen en el catálogo (descarta ids inventados).
        $existentes = (new Oficio(Database::getConexion()))->porIds($ids);
        if (count($existentes) !== count($ids)) {
            return ['ids' => [], 'texto' => '', 'error' => 'Alguno de los oficios elegidos no es válido.'];
        }

        $texto = implode(', ', array_column($existentes, 'nombre'));
        if (mb_strlen($texto) > 80) {
            return ['ids' => [], 'texto' => '', 'error' => 'Los oficios elegidos tienen nombres demasiado largos juntos: elegí menos.'];
        }
        return ['ids' => array_map('intval', array_column($existentes, 'id_oficio')), 'texto' => $texto, 'error' => null];
    }

    /**
     * Valida las localidades de trabajo (lista de ids). Exige al menos una, un máximo,
     * y que todas existan. Devuelve ['ids' => [...], 'error' => null|'mensaje'].
     */
    public static function resolverLocalidades($valor): array
    {
        $ids = self::idsEnteros($valor);
        if (empty($ids)) {
            return ['ids' => [], 'error' => 'Debe seleccionar al menos una localidad.'];
        }
        if (count($ids) > Config::MAX_LOCALIDADES_TRABAJO) {
            return ['ids' => [], 'error' => 'Podés elegir hasta ' . Config::MAX_LOCALIDADES_TRABAJO . ' localidades de trabajo.'];
        }
        $existentes = (new Localidad(Database::getConexion()))->porIds($ids);
        if (count($existentes) !== count($ids)) {
            return ['ids' => [], 'error' => 'Alguna de las localidades elegidas no es válida.'];
        }
        return ['ids' => $ids, 'error' => null];
    }

    /**
     * Sugerencias del autocompletado (api/localidades.php). Pide al menos 2 letras
     * y corta el texto en 60 caracteres. Devuelve [{id, nombre, provincia}, ...].
     */
    public static function sugerirLocalidades($texto): array
    {
        $texto = trim(Seguridad::texto($texto));
        if (mb_strlen($texto) < 2) {
            return [];
        }
        $filas = (new Localidad(Database::getConexion()))->buscar(mb_substr($texto, 0, 60), 10);
        return array_map(fn($f) => [
            'id' => (int) $f['id_localidad'],
            'nombre' => $f['nombre'],
            'provincia' => $f['provincia'],
        ], $filas);
    }

    /** Convierte lo que llegó por POST (debería ser una lista de ids) en una lista de enteros únicos y positivos. */
    private static function idsEnteros($valor): array
    {
        if (!is_array($valor)) {
            return [];
        }
        $ids = [];
        foreach ($valor as $v) {
            if (is_string($v) && ctype_digit($v) && (int) $v > 0) {
                $ids[(int) $v] = (int) $v;
            }
        }
        return array_values($ids);
    }
}
