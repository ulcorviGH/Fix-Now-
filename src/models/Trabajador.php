<?php
// src/models/Trabajador.php
// ---------------------------------------------------------------------------
// Modelo de la tabla TRABAJADOR (y de TRABAJADOR_LOCALIDAD).
//   Fase 3: crear() y asignarLocalidades()      -> registro (CU-01)
//   Fase 4: listarCercanos(), buscar(),          -> CU-03
//           listarMejorCalificados(),
//           obtenerPerfilCompleto()              -> CU-04
//
// IMPORTANTE: en toda la clase usamos parámetros POSICIONALES (?) y no
// nombrados (:nombre). Motivo: nuestra conexión usa consultas preparadas
// reales (EMULATE_PREPARES = false) y con ellas NO se puede repetir el mismo
// parámetro nombrado dos veces en una consulta.
// ---------------------------------------------------------------------------

class Trabajador
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // =====================================================================
    // FASE 3 - Registro
    // =====================================================================

    /** Crea la fila TRABAJADOR de un USUARIO ya creado (queda 'pendiente'). */
    public function crear(int $idUsuario, string $oficio): int
    {
        $sql = 'INSERT INTO TRABAJADOR (id_usuario, oficio, estado_habilitacion)
                VALUES (?, ?, \'pendiente\')';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idUsuario, $oficio]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Guarda las localidades/zonas donde trabaja (tabla N:N). */
    public function asignarLocalidades(int $idTrabajador, array $idsLocalidades): void
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO TRABAJADOR_LOCALIDAD (id_trabajador, id_localidad) VALUES (?, ?)'
        );
        foreach ($idsLocalidades as $idLocalidad) {
            $consulta->execute([$idTrabajador, (int) $idLocalidad]);
        }
    }

    // =====================================================================
    // FASE 4 - Búsqueda (CU-03)
    // =====================================================================

    /**
     * Fragmento SQL con las columnas que se muestran en las tarjetas de la
     * lista. Cada columna calculada es una subconsulta, así evitamos GROUP BY.
     */
    private function columnasLista(): string
    {
        return "t.id_trabajador,
                u.nombre,
                u.apellido,
                t.oficio,
                t.foto_perfil,
                (SELECT GROUP_CONCAT(l.nombre ORDER BY l.nombre SEPARATOR ', ')
                   FROM TRABAJADOR_LOCALIDAD tl
                   JOIN LOCALIDAD l ON l.id_localidad = tl.id_localidad
                  WHERE tl.id_trabajador = t.id_trabajador) AS localidades,
                (SELECT AVG(c.puntuacion)
                   FROM CALIFICACION c
                  WHERE c.id_trabajador = t.id_trabajador) AS calificacion_promedio";
    }

    /**
     * Lista de trabajadores ordenada por cercanía a una coordenada (la de la
     * localidad del cliente). Si el trabajador cubre varias localidades, se
     * toma la MÁS CERCANA de todas.
     *
     * Fórmula de Haversine/ley de cosenos: distancia en km entre dos puntos
     * de la Tierra (radio 6371 km). LEAST/GREATEST "recortan" el valor entre
     * -1 y 1 porque por redondeo puede dar 1.0000000001 y ACOS() fallaría.
     */
    public function listarCercanos(float $lat, float $lon, int $limite = 30): array
    {
        $limite = (int) $limite; // se castea a entero, es seguro interpolarlo

        $sql = "SELECT {$this->columnasLista()},
                       (SELECT MIN(6371 * ACOS(LEAST(1, GREATEST(-1,
                                 COS(RADIANS(?)) * COS(RADIANS(l2.latitud))
                               * COS(RADIANS(l2.longitud) - RADIANS(?))
                               + SIN(RADIANS(?)) * SIN(RADIANS(l2.latitud))))))
                          FROM TRABAJADOR_LOCALIDAD tl2
                          JOIN LOCALIDAD l2 ON l2.id_localidad = tl2.id_localidad
                         WHERE tl2.id_trabajador = t.id_trabajador) AS distancia_km
                  FROM TRABAJADOR t
                  JOIN USUARIO u ON u.id_usuario = t.id_usuario
                 WHERE t.estado_habilitacion = 'habilitado'
                   AND u.estado = 'activo'
                 ORDER BY distancia_km ASC
                 LIMIT {$limite}";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$lat, $lon, $lat]); // orden = orden de los ? en el SQL
        return $consulta->fetchAll();
    }

    /**
     * Búsqueda con filtros (barra superior de CU-03):
     *   - $oficio: texto libre, se compara contra oficio y nombre/descr. de servicios
     *   - $idsLocalidades: el trabajador debe cubrir AL MENOS UNA de ellas
     *   - $calificacionMinima: promedio mínimo (null = sin filtro)
     * Resultado ordenado por calificación (mejor primero).
     */
    public function buscar(string $oficio, array $idsLocalidades, ?float $calificacionMinima, int $limite = 30): array
    {
        $limite = (int) $limite;
        $params = [];
        $filtros = '';

        if ($oficio !== '') {
            // Busca coincidencias en el oficio o en los servicios aprobados.
            $filtros .= " AND (t.oficio LIKE ?
                          OR EXISTS (SELECT 1 FROM SERVICIO s
                                      WHERE s.id_trabajador = t.id_trabajador
                                        AND s.estado_revision = 'aprobado'
                                        AND (s.nombre LIKE ? OR s.descripcion LIKE ?)))";
            $comodin = '%' . $oficio . '%';
            array_push($params, $comodin, $comodin, $comodin);
        }

        if (!empty($idsLocalidades)) {
            // Un "?" por cada localidad elegida: IN (?, ?, ?)
            $marcadores = implode(',', array_fill(0, count($idsLocalidades), '?'));
            $filtros .= " AND EXISTS (SELECT 1 FROM TRABAJADOR_LOCALIDAD tl
                                       WHERE tl.id_trabajador = t.id_trabajador
                                         AND tl.id_localidad IN ({$marcadores}))";
            foreach ($idsLocalidades as $id) {
                $params[] = (int) $id;
            }
        }

        // Se arma como subconsulta para poder filtrar por la columna calculada.
        $sql = "SELECT * FROM (
                    SELECT {$this->columnasLista()}
                      FROM TRABAJADOR t
                      JOIN USUARIO u ON u.id_usuario = t.id_usuario
                     WHERE t.estado_habilitacion = 'habilitado'
                       AND u.estado = 'activo'
                       {$filtros}
                ) AS resultado
                WHERE 1 = 1";

        if ($calificacionMinima !== null) {
            $sql .= ' AND calificacion_promedio >= ?';
            $params[] = $calificacionMinima;
        }

        $sql .= " ORDER BY calificacion_promedio DESC, nombre ASC LIMIT {$limite}";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($params);
        return $consulta->fetchAll();
    }

    /**
     * Lista general de mejor calificados. Se usa de respaldo cuando el
     * cliente no tiene localidad o no hay nadie cerca (excepción 2.1 de CU-03).
     */
    public function listarMejorCalificados(int $limite = 30): array
    {
        $limite = (int) $limite;
        $sql = "SELECT * FROM (
                    SELECT {$this->columnasLista()}
                      FROM TRABAJADOR t
                      JOIN USUARIO u ON u.id_usuario = t.id_usuario
                     WHERE t.estado_habilitacion = 'habilitado'
                       AND u.estado = 'activo'
                ) AS resultado
                ORDER BY calificacion_promedio DESC, nombre ASC
                LIMIT {$limite}";

        return $this->pdo->query($sql)->fetchAll();
    }

    // =====================================================================
    // FASE 4 - Perfil público (CU-04)
    // =====================================================================

    /**
     * Datos principales del perfil de un trabajador, incluyendo el promedio,
     * la cantidad de reseñas y los "trabajos completados".
     *
     * "Trabajos completados" NO es un campo guardado: es un COUNT de las
     * solicitudes con doble confirmación (ver Notas de diseño del documento).
     *
     * Devuelve null si no existe, no está habilitado o su cuenta está
     * bloqueada (excepción 2.2 de CU-04: "Este perfil no está disponible").
     */
    public function obtenerPerfilCompleto(int $idTrabajador): ?array
    {
        $sql = "SELECT t.id_trabajador,
                       u.nombre,
                       u.apellido,
                       t.oficio,
                       t.descripcion,
                       t.anios_experiencia,
                       t.foto_perfil,
                       (SELECT GROUP_CONCAT(l.nombre ORDER BY l.nombre SEPARATOR ', ')
                          FROM TRABAJADOR_LOCALIDAD tl
                          JOIN LOCALIDAD l ON l.id_localidad = tl.id_localidad
                         WHERE tl.id_trabajador = t.id_trabajador) AS localidades,
                       (SELECT COUNT(*) FROM SOLICITUD s
                         WHERE s.id_trabajador = t.id_trabajador
                           AND s.confirmado_cliente = TRUE
                           AND s.confirmado_trabajador = TRUE) AS trabajos_completados,
                       (SELECT AVG(c.puntuacion) FROM CALIFICACION c
                         WHERE c.id_trabajador = t.id_trabajador) AS calificacion_promedio,
                       (SELECT COUNT(*) FROM CALIFICACION c
                         WHERE c.id_trabajador = t.id_trabajador) AS cantidad_calificaciones
                  FROM TRABAJADOR t
                  JOIN USUARIO u ON u.id_usuario = t.id_usuario
                 WHERE t.id_trabajador = ?
                   AND t.estado_habilitacion = 'habilitado'
                   AND u.estado = 'activo'
                 LIMIT 1";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idTrabajador]);

        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    // =====================================================================
    // FASE 5 - Apoyo para solicitudes y chats
    // =====================================================================

    /** id_trabajador de un usuario con rol trabajador (null si no tiene perfil). */
    public function obtenerIdPorUsuario(int $idUsuario): ?int
    {
        $consulta = $this->pdo->prepare('SELECT id_trabajador FROM TRABAJADOR WHERE id_usuario = ? LIMIT 1');
        $consulta->execute([$idUsuario]);
        $id = $consulta->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /** true si el trabajador ofrece sus servicios en esa localidad (precondición de CU-06). */
    public function cubreLocalidad(int $idTrabajador, int $idLocalidad): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT 1 FROM TRABAJADOR_LOCALIDAD WHERE id_trabajador = ? AND id_localidad = ? LIMIT 1'
        );
        $consulta->execute([$idTrabajador, $idLocalidad]);
        return $consulta->fetchColumn() !== false;
    }
}
