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
            $lista = $modeloTrabajador->listarCercanos(
                (float) $localidad['latitud'],
                (float) $localidad['longitud']
            );
            if (!empty($lista)) {
                return ['modo' => 'cercania', 'trabajadores' => $lista];
            }
        }

        return ['modo' => 'mejor_calificados', 'trabajadores' => $modeloTrabajador->listarMejorCalificados()];
    }

    /**
     * Búsqueda con filtros (paso 4 de CU-03). Recibe el array $_GET.
     * Devuelve la lista de trabajadores y los filtros ya normalizados, para
     * que la pantalla pueda "recordar" lo que el cliente eligió.
     */
    public static function buscar(int $idUsuario, array $get): array
    {
        $pdo = Database::getConexion();

        $oficio = trim($get['oficio'] ?? '');

        // Solo ids numéricos válidos.
        $idsLocalidades = array_values(array_filter(
            array_map('intval', $get['localidades'] ?? []),
            fn($id) => $id > 0
        ));

        // Excepción 4.2: si no eligió ninguna localidad, se usa la del cliente.
        if (empty($idsLocalidades)) {
            $localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuario);
            if ($localidad !== null) {
                $idsLocalidades = [(int) $localidad['id_localidad']];
            }
        }

        $calificacionMinima = ($get['calificacion_minima'] ?? '') !== ''
            ? (float) $get['calificacion_minima']
            : null;

        $trabajadores = (new Trabajador($pdo))->buscar($oficio, $idsLocalidades, $calificacionMinima);

        return [
            'trabajadores' => $trabajadores,
            'filtros' => [
                'oficio' => $oficio,
                'localidades' => $idsLocalidades,
                'calificacion_minima' => $calificacionMinima,
            ],
        ];
    }
}
