<?php
// src/controllers/PerfilController.php
// ---------------------------------------------------------------------------
// Lógica del CU-04 "Ver perfil de trabajador".
// Junta en un solo array los datos que vienen de 4 modelos distintos:
// Trabajador (datos + promedio), Servicio, Imagen y Calificacion.
// ---------------------------------------------------------------------------

class PerfilController
{
    /**
     * Devuelve el perfil completo, o null si no existe / no está habilitado /
     * la cuenta está bloqueada (excepción 2.2: "Este perfil no está disponible").
     */
    public static function obtener(int $idTrabajador): ?array
    {
        $pdo = Database::getConexion();

        $perfil = (new Trabajador($pdo))->obtenerPerfilCompleto($idTrabajador);
        if ($perfil === null) {
            return null;
        }

        $perfil['servicios'] = (new Servicio($pdo))->listarAprobadosPorTrabajador($idTrabajador);
        $perfil['imagenes'] = (new Imagen($pdo))->listarAprobadasPorTrabajador($idTrabajador);
        $perfil['resenias'] = (new Calificacion($pdo))->listarPorTrabajador($idTrabajador);

        return $perfil;
    }
}
