<?php
// src/controllers/BusquedaController.php
// ---------------------------------------------------------------------------
// Lógica del CU-03 "Ver trabajadores cercanos y buscar".
// Decide QUÉ lista mostrar; la pantalla (public/panel_cliente.php) solo la dibuja.
// ---------------------------------------------------------------------------

class BusquedaController
{
    /**
     * Lista inicial al entrar (pasos 1-2 de CU-03): trabajadores ordenados por
     * cercanía a la localidad del cliente.
     *
     * Devuelve ['modo' => 'cercania' | 'mejor_calificados', 'trabajadores' => [...]]
     *
     * Excepción 2.1: si el cliente no tiene localidad cargada o no hay nadie
     * cerca, se muestra la lista general de mejor calificados.
     */
    public static function listaInicial(int $idUsuario): array
    {
        $pdo = Database::getConexion();
        $modeloTrabajador = new Trabajador($pdo);

        $localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);

        if ($localidad !== null) {
            // Fase 8b: el último parámetro excluye al propio usuario (un trabajador
            // que busca como cliente no debe verse a sí mismo).
            $lista = $modeloTrabajador->listarCercanos(
                (float) $localidad['latitud'],
                (float) $localidad['longitud'],
                30,
                $idUsuario
            );
            if (!empty($lista)) {
                return ['modo' => 'cercania', 'trabajadores' => $lista];
            }
        }

        return ['modo' => 'mejor_calificados', 'trabajadores' => $modeloTrabajador->listarMejorCalificados(30, $idUsuario)];
    }

    /**
     * Búsqueda con filtros (paso 4 de CU-03). Recibe el array $_GET.
     * Devuelve la lista de trabajadores y los filtros ya normalizados, para
     * que la pantalla pueda "recordar" lo que el cliente eligió.
     */
    public static function buscar(int $idUsuario, array $get): array
    {
        $pdo = Database::getConexion();

        $oficio = trim(Seguridad::texto($get['oficio'] ?? '')); // Fase 8: ignora listas armadas a mano

        // Solo ids numéricos válidos. (Fase 8b: el buscador ya no muestra el desplegable
        // de localidades, pero si alguien arma la URL con localidades[] se respeta.)
        $idsLocalidades = array_values(array_filter(
            array_map('intval', (array) ($get['localidades'] ?? [])),
            fn($id) => $id > 0
        ));

        // ---------------- Fase 8b: búsqueda por RADIO (slider de km) ----------------
        // El navegador manda su posición (lat/lng) obtenida con navigator.geolocation.
        // Si no la mandó (el usuario negó el permiso) o tildó "usar mi localidad", se
        // toma el punto de la localidad del usuario.
        $radio = self::numeroEnRango($get['radio'] ?? '', 1, 200);
        $lat = null;
        $lon = null;
        $origen = null; // 'gps' | 'localidad' | null (sin filtro por radio)

        if ($radio !== null) {
            $usarLocalidad = Seguridad::texto($get['origen'] ?? '') === 'localidad';
            $latGps = self::numeroEnRango($get['lat'] ?? '', -90, 90);
            $lonGps = self::numeroEnRango($get['lng'] ?? '', -180, 180);

            if (!$usarLocalidad && $latGps !== null && $lonGps !== null) {
                $lat = $latGps;
                $lon = $lonGps;
                $origen = 'gps';
            } else {
                $localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);
                if ($localidad !== null) {
                    $lat = (float) $localidad['latitud'];
                    $lon = (float) $localidad['longitud'];
                    $origen = 'localidad';
                } else {
                    $radio = null; // sin ubicación no se puede filtrar por distancia
                }
            }
        } elseif (empty($idsLocalidades)) {
            // Excepción 4.2 (URLs viejas sin radio): si no eligió localidad, se usa la del cliente.
            $localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);
            if ($localidad !== null) {
                $idsLocalidades = [(int) $localidad['id_localidad']];
            }
        }

        $calificacionMinima = Seguridad::texto($get['calificacion_minima'] ?? '') !== ''
            ? (float) $get['calificacion_minima']
            : null;

        $trabajadores = (new Trabajador($pdo))->buscar(
            $oficio, $idsLocalidades, $calificacionMinima, 30, $lat, $lon, $radio, $idUsuario
        );

        return [
            'trabajadores' => $trabajadores,
            'filtros' => [
                'oficio' => $oficio,
                'localidades' => $idsLocalidades,
                'calificacion_minima' => $calificacionMinima,
                'radio' => $radio,      // Fase 8b
                'origen' => $origen,    // Fase 8b: 'gps' | 'localidad' | null
                'lat' => $origen === 'gps' ? $lat : null,
                'lng' => $origen === 'gps' ? $lon : null,
            ],
        ];
    }

    /**
     * Fase 8b: convierte un valor que llegó por la URL en un número dentro de un
     * rango. Devuelve null si no es un número válido o si está fuera del rango
     * (así nadie puede colar texto ni valores absurdos en la fórmula de distancia).
     */
    private static function numeroEnRango($valor, float $min, float $max): ?float
    {
        $texto = Seguridad::texto($valor);
        if ($texto === '' || !is_numeric($texto)) {
            return null;
        }
        $numero = (float) $texto;
        return ($numero >= $min && $numero <= $max) ? $numero : null;
    }
}
