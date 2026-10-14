<?php
// src/models/Solicitud.php
// ---------------------------------------------------------------------------
// Modelo de la tabla SOLICITUD. Una fila representa UNA relación cliente <->
// trabajador con DOS etapas separadas (ver "Notas de diseño" del documento):
//
//   1) CONTACTO  -> estado_contacto: pendiente | aceptada | rechazada   (CU-06)
//                   Si se acepta, se habilita el chat (CU-08).
//   2) TRABAJO   -> estado_trabajo: no_solicitado | pendiente | aceptado | rechazado (CU-21)
//                   Si se acepta, arranca el seguimiento de finalización
//                   (confirmado_cliente / confirmado_trabajador, CU-21 y CU-22).
//
// Este modelo solo sabe leer y escribir esos datos. Las REGLAS de negocio
// (quién puede hacer qué) viven en SolicitudController.
//
// Casi todos los UPDATE tienen una condición sobre el estado anterior
// (ej. "WHERE estado_contacto = 'pendiente'"). Así, si alguien aprieta dos
// veces el botón, o dos pestañas actúan a la vez, la segunda vez no hace nada
// y el método devuelve false (CU-11 excepción 4.1: "ya fue respondida").
// ---------------------------------------------------------------------------

class Solicitud
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * SELECT base reutilizado por casi todas las consultas: trae la solicitud
     * junto con los nombres de las dos personas y el último mensaje del chat.
     */
    private function base(): string
    {
        return "SELECT s.*,
                       uc.nombre   AS cliente_nombre,
                       uc.apellido AS cliente_apellido,
                       t.id_usuario AS id_usuario_trabajador,
                       t.oficio,
                       ut.nombre   AS trabajador_nombre,
                       ut.apellido AS trabajador_apellido,
                       (SELECT m.contenido FROM MENSAJE m
                         WHERE m.id_solicitud = s.id_solicitud
                         ORDER BY m.id_mensaje DESC LIMIT 1) AS ultimo_mensaje,
                       (SELECT cal.id_calificacion FROM CALIFICACION cal
                         WHERE cal.id_solicitud = s.id_solicitud) AS id_calificacion
                  FROM SOLICITUD s
                  JOIN USUARIO uc ON uc.id_usuario = s.id_usuario
                  JOIN TRABAJADOR t ON t.id_trabajador = s.id_trabajador
                  JOIN USUARIO ut ON ut.id_usuario = t.id_usuario";
    }

    // =====================================================================
    // Lecturas
    // =====================================================================

    /**
     * Devuelve la solicitud SOLO si el usuario participa en ella (como cliente
     * o como trabajador). Agrega 'mi_rol' => 'cliente' | 'trabajador'.
     * Es la barrera de seguridad: nadie puede abrir chats ajenos.
     */
    public function obtenerSiParticipa(int $idSolicitud, int $idUsuario): ?array
    {
        $consulta = $this->pdo->prepare(
            $this->base() . ' WHERE s.id_solicitud = ? AND (s.id_usuario = ? OR t.id_usuario = ?) LIMIT 1'
        );
        $consulta->execute([$idSolicitud, $idUsuario, $idUsuario]);

        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }
        $fila['mi_rol'] = ((int) $fila['id_usuario'] === $idUsuario) ? 'cliente' : 'trabajador';
        return $fila;
    }

    /**
     * Relación "en curso" entre un cliente y un trabajador: contacto pendiente
     * o aceptado, y trabajo todavía no finalizado por ambas partes.
     * Sirve para no crear solicitudes duplicadas y para que el perfil muestre
     * "Ir al chat" en vez de "Enviar mensaje".
     */
    public function vigenteEntre(int $idUsuarioCliente, int $idTrabajador): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id_solicitud, estado_contacto
               FROM SOLICITUD
              WHERE id_usuario = ? AND id_trabajador = ?
                AND estado_contacto IN ('pendiente', 'aceptada')
                AND NOT (confirmado_cliente = TRUE AND confirmado_trabajador = TRUE)
              ORDER BY id_solicitud DESC LIMIT 1"
        );
        $consulta->execute([$idUsuarioCliente, $idTrabajador]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    /** Todas las solicitudes de un usuario (para la pantalla "Chats"). */
    public function listarPorUsuario(int $idUsuario, string $rol): array
    {
        $campo = ($rol === 'cliente') ? 's.id_usuario' : 't.id_usuario';
        $consulta = $this->pdo->prepare($this->base() . " WHERE {$campo} = ? ORDER BY s.id_solicitud DESC");
        $consulta->execute([$idUsuario]);
        return $consulta->fetchAll();
    }

    /** Pestaña "Solicitudes de chat" del trabajador (CU-11). */
    public function listarContactosPendientes(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            $this->base() . " WHERE s.id_trabajador = ? AND s.estado_contacto = 'pendiente' ORDER BY s.fecha_creacion ASC"
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    /** Pestaña "Solicitudes de trabajo" del trabajador: confirmaciones por responder (CU-11 / CU-21). */
    public function listarTrabajosPendientes(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            $this->base() . " WHERE s.id_trabajador = ? AND s.estado_contacto = 'aceptada'
                              AND s.estado_trabajo = 'pendiente' ORDER BY s.id_solicitud ASC"
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    /** Trabajos aceptados que todavía no terminaron (ahí va el botón manual de CU-22). */
    public function listarTrabajosEnCurso(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            $this->base() . " WHERE s.id_trabajador = ? AND s.estado_trabajo = 'aceptado'
                              AND NOT (s.confirmado_cliente = TRUE AND s.confirmado_trabajador = TRUE)
                              ORDER BY s.fecha_confirmacion_trabajo ASC"
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    /** Cantidades para los globitos de la barra de navegación del trabajador. */
    public function contarPendientes(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM SOLICITUD WHERE id_trabajador = ? AND estado_contacto = 'pendiente') AS contactos,
                (SELECT COUNT(*) FROM SOLICITUD WHERE id_trabajador = ? AND estado_contacto = 'aceptada'
                                                    AND estado_trabajo = 'pendiente') AS trabajos"
        );
        $consulta->execute([$idTrabajador, $idTrabajador]);
        return $consulta->fetch();
    }

    /**
     * Trabajos sobre los que hay que preguntarle a ESTE usuario
     * "¿Ya se completó el trabajo?" (CU-21 pasos 6-7). Se cumple si:
     *   - el trabajo fue aceptado y esta parte todavía no confirmó,
     *   - pasaron los días configurados desde que se aceptó, y
     *   - no respondió "Todavía no" hace poco (posponer_..._hasta).
     *
     * No hay ningún proceso programado en segundo plano: la pregunta se
     * calcula en el momento en que el usuario abre cualquier pantalla.
     */
    public function preguntasFinalizacionPendientes(int $idUsuario, string $rol): array
    {
        $dias = (int) Config::DIAS_PRIMERA_PREGUNTA;

        // Condición de "ya corresponde preguntar": pasaron los días configurados...
        $pasaronLosDias = "NOW() >= DATE_ADD(s.fecha_confirmacion_trabajo, INTERVAL {$dias} DAY)";

        if ($rol === 'cliente') {
            $dueno = 's.id_usuario';
            $confirmado = 's.confirmado_cliente';
            $posponer = 's.posponer_cliente_hasta';
            // ...o el trabajador YA marcó el trabajo como terminado (CU-22: "o si el
            // cliente confirma antes"). Sin esto el cliente esperaría 2 días para
            // poder confirmar algo que el trabajador ya declaró terminado.
            $pasaronLosDias = "({$pasaronLosDias} OR s.confirmado_trabajador = TRUE)";
        } else {
            $dueno = 't.id_usuario';
            $confirmado = 's.confirmado_trabajador';
            $posponer = 's.posponer_trabajador_hasta';
        }

        $consulta = $this->pdo->prepare(
            $this->base() . " WHERE {$dueno} = ?
                AND s.estado_trabajo = 'aceptado'
                AND {$confirmado} = FALSE
                AND {$pasaronLosDias}
                AND ({$posponer} IS NULL OR NOW() >= {$posponer})
              ORDER BY s.fecha_confirmacion_trabajo ASC"
        );
        $consulta->execute([$idUsuario]);
        return $consulta->fetchAll();
    }

    // =====================================================================
    // Escrituras
    // =====================================================================

    /** CU-06: registra la solicitud de contacto con estado 'pendiente'. */
    public function crear(int $idUsuarioCliente, int $idTrabajador, string $mensajeInicial): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO SOLICITUD (id_usuario, id_trabajador, mensaje_inicial) VALUES (?, ?, ?)'
        );
        $consulta->execute([$idUsuarioCliente, $idTrabajador, $mensajeInicial]);
        return (int) $this->pdo->lastInsertId();
    }

    /** CU-06 paso 6: el trabajador acepta o rechaza el contacto. false = ya estaba respondida. */
    public function resolverContacto(int $idSolicitud, bool $aceptar): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE SOLICITUD SET estado_contacto = ?
              WHERE id_solicitud = ? AND estado_contacto = 'pendiente'"
        );
        $consulta->execute([$aceptar ? 'aceptada' : 'rechazada', $idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    /** CU-21 paso 2: el cliente presiona "Contratar trabajador". */
    public function pedirTrabajo(int $idSolicitud): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE SOLICITUD SET estado_trabajo = 'pendiente'
              WHERE id_solicitud = ? AND estado_contacto = 'aceptada'
                AND estado_trabajo IN ('no_solicitado', 'rechazado')"
        );
        $consulta->execute([$idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    /**
     * CU-21 paso 4: el trabajador acepta o rechaza el trabajo.
     * Al ACEPTAR se guarda la fecha: desde ahí corre el contador de 2 días.
     */
    public function resolverTrabajo(int $idSolicitud, bool $aceptar): bool
    {
        $sql = $aceptar
            ? "UPDATE SOLICITUD SET estado_trabajo = 'aceptado', fecha_confirmacion_trabajo = NOW()
                WHERE id_solicitud = ? AND estado_trabajo = 'pendiente'"
            : "UPDATE SOLICITUD SET estado_trabajo = 'rechazado'
                WHERE id_solicitud = ? AND estado_trabajo = 'pendiente'";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    /**
     * Una de las partes confirma "Sí, está terminado" (CU-21 / CU-22).
     * $parte es 'cliente' o 'trabajador'. Cuando las dos columnas quedan en
     * TRUE el trabajo cuenta como completado y se habilita la calificación.
     */
    public function confirmarFinalizacion(int $idSolicitud, string $parte): bool
    {
        [$confirmado, $posponer] = $this->columnasDeParte($parte);

        $consulta = $this->pdo->prepare(
            "UPDATE SOLICITUD SET {$confirmado} = TRUE, {$posponer} = NULL
              WHERE id_solicitud = ? AND estado_trabajo = 'aceptado' AND {$confirmado} = FALSE"
        );
        $consulta->execute([$idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    /** "Todavía no": no se vuelve a preguntar a esta parte hasta dentro de N días. */
    public function posponerPregunta(int $idSolicitud, string $parte): bool
    {
        [$confirmado, $posponer] = $this->columnasDeParte($parte);
        $dias = (int) Config::DIAS_REINTENTO;

        $consulta = $this->pdo->prepare(
            "UPDATE SOLICITUD SET {$posponer} = DATE_ADD(NOW(), INTERVAL {$dias} DAY)
              WHERE id_solicitud = ? AND estado_trabajo = 'aceptado' AND {$confirmado} = FALSE"
        );
        $consulta->execute([$idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    /**
     * Traduce 'cliente'/'trabajador' a los nombres de columna. Los nombres de
     * columna NO se pueden pasar como parámetro (?), por eso se elige de una
     * lista cerrada: nunca se arma SQL con texto que venga del usuario.
     */
    private function columnasDeParte(string $parte): array
    {
        return $parte === 'cliente'
            ? ['confirmado_cliente', 'posponer_cliente_hasta']
            : ['confirmado_trabajador', 'posponer_trabajador_hasta'];
    }

    // =====================================================================
    // FASE 6 - Calificaciones
    // =====================================================================

    /**
     * Trabajos del cliente (los que el trabajador aceptó), para la pantalla
     * "Mis trabajos" (CU-07 paso 1). 'id_calificacion' viene NULL si todavía
     * no los calificó.
     */
    public function listarTrabajosDeCliente(int $idUsuarioCliente): array
    {
        $consulta = $this->pdo->prepare(
            $this->base() . " WHERE s.id_usuario = ? AND s.estado_trabajo = 'aceptado'
                              ORDER BY s.id_solicitud DESC"
        );
        $consulta->execute([$idUsuarioCliente]);
        return $consulta->fetchAll();
    }

    /** Trabajos completados = solicitudes con doble confirmación (no es un campo guardado). */
    public function contarCompletados(int $idTrabajador): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM SOLICITUD
              WHERE id_trabajador = ? AND confirmado_cliente = TRUE AND confirmado_trabajador = TRUE'
        );
        $consulta->execute([$idTrabajador]);
        return (int) $consulta->fetchColumn();
    }
}
