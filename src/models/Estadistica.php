<?php
// src/models/Estadistica.php
// ---------------------------------------------------------------------------
// Consultas de solo lectura para el Administrador:
//   - metricas()          -> pantalla "Reportes y estadísticas" (CU-16)
//   - pendientesAdmin()   -> globitos de aviso del menú y del panel
//
// No hay una tabla "ESTADISTICA": todas las cifras se CALCULAN con COUNT/AVG
// sobre las tablas reales cada vez que se piden, así nunca quedan desactualizadas
// (misma idea que "trabajos completados" y el promedio de calificaciones).
// ---------------------------------------------------------------------------

class Estadistica
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Ejecuta un SELECT de un solo valor (con parámetros ? opcionales) y lo devuelve. */
    private function valor(string $sql, array $params = [])
    {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($params);
        return $consulta->fetchColumn();
    }

    /** Cuántas cosas están esperando al Administrador (para los globitos del menú). */
    public function pendientesAdmin(): array
    {
        return [
            'trabajadores'   => (int) $this->valor("SELECT COUNT(*) FROM TRABAJADOR WHERE estado_habilitacion = 'pendiente'"),
            'contenido'      => (int) $this->valor("SELECT (SELECT COUNT(*) FROM SERVICIO WHERE estado_revision = 'pendiente')
                                                         + (SELECT COUNT(*) FROM IMAGEN WHERE estado_revision = 'pendiente')"),
            'calificaciones' => (int) $this->valor('SELECT COUNT(*) FROM CALIFICACION WHERE reportada = TRUE'),
        ];
    }

    /** Todas las métricas de CU-16 paso 2. */
    public function metricas(): array
    {
        $promedio = $this->valor('SELECT AVG(puntuacion) FROM CALIFICACION');

        return [
            'clientes'    => (int) $this->valor("SELECT COUNT(*) FROM USUARIO WHERE rol = 'cliente'"),
            'trabajadores' => (int) $this->valor("SELECT COUNT(*) FROM USUARIO WHERE rol = 'trabajador'"),
            // "Activo" = inició sesión en los últimos 30 días (USUARIO.ultimo_acceso).
            'activos_mes' => (int) $this->valor(
                "SELECT COUNT(*) FROM USUARIO WHERE rol <> 'admin' AND ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ),
            'pendientes_habilitacion' => (int) $this->valor("SELECT COUNT(*) FROM TRABAJADOR WHERE estado_habilitacion = 'pendiente'"),
            'servicios_publicados'    => (int) $this->valor("SELECT COUNT(*) FROM SERVICIO WHERE estado_revision = 'aprobado'"),

            // Solicitudes de chat (CU-06): estado_contacto
            'chat_aceptadas'  => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_contacto = 'aceptada'"),
            'chat_rechazadas' => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_contacto = 'rechazada'"),
            'chat_pendientes' => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_contacto = 'pendiente'"),

            // Solicitudes de trabajo (CU-21): estado_trabajo ('no_solicitado' todavía no es una solicitud)
            'trabajo_aceptadas'  => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_trabajo = 'aceptado'"),
            'trabajo_rechazadas' => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_trabajo = 'rechazado'"),
            'trabajo_pendientes' => (int) $this->valor("SELECT COUNT(*) FROM SOLICITUD WHERE estado_trabajo = 'pendiente'"),

            // Trabajos con DOBLE confirmación (mismo criterio que el perfil público).
            'trabajos_completados' => (int) $this->valor(
                'SELECT COUNT(*) FROM SOLICITUD WHERE confirmado_cliente = TRUE AND confirmado_trabajador = TRUE'
            ),
            'calificacion_promedio'   => $promedio !== null ? (float) $promedio : null,
            'calificaciones_reportadas' => (int) $this->valor('SELECT COUNT(*) FROM CALIFICACION WHERE reportada = TRUE'),
        ];
    }
}
