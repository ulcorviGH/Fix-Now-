<?php
// src/helpers/Vista.php
// ---------------------------------------------------------------------------
// Pequeñas funciones para dibujar HTML repetido en varias pantallas
// (estrellas de calificación, avatar del trabajador). Siempre escapan el
// texto con htmlspecialchars para evitar ataques XSS.
// ---------------------------------------------------------------------------

class Vista
{
    /** Escapa texto para mostrarlo seguro dentro de HTML. */
    public static function e(?string $texto): string
    {
        return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Dibuja las estrellas de un promedio. Ej: 4.3 -> ★★★★☆ 4.3
     * Si el trabajador todavía no tiene calificaciones: "Sin calificaciones".
     */
    public static function estrellas($promedio): string
    {
        if ($promedio === null) {
            return '<span class="text-muted small">Sin calificaciones</span>';
        }

        $promedio = (float) $promedio;
        $llenas = (int) round($promedio);
        $texto = str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);

        return '<span class="text-warning">' . $texto . '</span> '
             . '<span class="small text-muted">' . number_format($promedio, 1) . '</span>';
    }

    /**
     * Foto de perfil redonda. Si el trabajador no tiene foto, se muestra un
     * círculo con sus iniciales (CU-04 excepción 2.1: perfil incompleto).
     */
    public static function avatar(?string $foto, string $nombre, string $apellido, int $tamano = 64): string
    {
        $estilo = "width:{$tamano}px;height:{$tamano}px;";

        if (!empty($foto)) {
            return '<img src="' . self::e($foto) . '" alt="Foto de ' . self::e($nombre) . '" '
                 . 'class="rounded-circle object-fit-cover" style="' . $estilo . '">';
        }

        $iniciales = mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
        return '<div class="rounded-circle bg-secondary text-white d-flex align-items-center '
             . 'justify-content-center fw-bold" style="' . $estilo . 'font-size:' . ($tamano / 2.5) . 'px;">'
             . self::e($iniciales) . '</div>';
    }

    // -----------------------------------------------------------------
    // Fase 5
    // -----------------------------------------------------------------

    /** Campo oculto con el código CSRF; va dentro de todo <form method="post">. */
    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="csrf" value="' . self::e(Sesion::csrfToken()) . '">';
    }

    /** Fecha y hora legibles: 08/10/2026 14:30 */
    public static function fecha(?string $fecha): string
    {
        return $fecha ? date('d/m/Y H:i', strtotime($fecha)) : '';
    }

    /** Dirección de la página actual (sin la barra inicial), para volver a ella tras una acción. */
    public static function urlActual(): string
    {
        return ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
    }

    /**
     * Dibuja un botón que envía una acción a public/accion.php
     * (aceptar, rechazar, contratar, etc.). $confirmar: texto opcional
     * para pedir confirmación antes de enviar.
     */
    public static function formAccion(string $accion, int $idSolicitud, string $texto,
                                      string $clase = 'btn-primary btn-sm', string $confirmar = ''): string
    {
        $onsubmit = $confirmar !== ''
            ? ' onsubmit="return confirm(\'' . self::e(addslashes($confirmar)) . '\');"'
            : '';

        return '<form method="post" action="accion.php" class="d-inline"' . $onsubmit . '>'
             . self::campoCsrf()
             . '<input type="hidden" name="accion" value="' . self::e($accion) . '">'
             . '<input type="hidden" name="id_solicitud" value="' . $idSolicitud . '">'
             . '<input type="hidden" name="volver" value="' . self::e(self::urlActual()) . '">'
             . '<button type="submit" class="btn ' . self::e($clase) . '">' . self::e($texto) . '</button>'
             . '</form>';
    }

    // -----------------------------------------------------------------
    // Fase 7 - Panel del Administrador
    // -----------------------------------------------------------------

    /**
     * Botón de acción del Administrador: envía POST a public/accion_admin.php.
     * $accion es una de las acciones de AdminController; $id es el id del
     * objeto (usuario, trabajador, servicio, imagen o calificación).
     * $confirmar: texto opcional para pedir confirmación antes de enviar.
     */
    public static function formAdmin(string $accion, int $id, string $texto,
                                     string $clase = 'btn-primary btn-sm', string $confirmar = ''): string
    {
        $onsubmit = $confirmar !== ''
            ? ' onsubmit="return confirm(\'' . self::e(addslashes($confirmar)) . '\');"'
            : '';

        return '<form method="post" action="accion_admin.php" class="d-inline"' . $onsubmit . '>'
             . self::campoCsrf()
             . '<input type="hidden" name="accion" value="' . self::e($accion) . '">'
             . '<input type="hidden" name="id" value="' . $id . '">'
             . '<input type="hidden" name="volver" value="' . self::e(self::urlActual()) . '">'
             . '<button type="submit" class="btn ' . self::e($clase) . '">' . self::e($texto) . '</button>'
             . '</form>';
    }

    /**
     * Igual que formAdmin() pero con un campo de texto obligatorio para el
     * MOTIVO (rechazos). El campo va en el mismo formulario que el botón.
     */
    public static function formAdminMotivo(string $accion, int $id, string $texto,
                                           string $clase = 'btn-outline-danger btn-sm',
                                           string $placeholder = 'Motivo (obligatorio)'): string
    {
        return '<form method="post" action="accion_admin.php" class="d-flex gap-1 flex-grow-1">'
             . self::campoCsrf()
             . '<input type="hidden" name="accion" value="' . self::e($accion) . '">'
             . '<input type="hidden" name="id" value="' . $id . '">'
             . '<input type="hidden" name="volver" value="' . self::e(self::urlActual()) . '">'
             . '<input type="text" name="motivo" class="form-control form-control-sm" maxlength="255" required '
             . 'placeholder="' . self::e($placeholder) . '">'
             . '<button type="submit" class="btn ' . self::e($clase) . ' text-nowrap">' . self::e($texto) . '</button>'
             . '</form>';
    }

    /** Etiqueta de color para un estado (activo, pendiente, bloqueado, rechazado, aprobado...). */
    public static function badgeEstado(string $estado): string
    {
        $colores = [
            'activo'     => 'success', 'habilitado' => 'success', 'aprobado' => 'success', 'aprobada' => 'success',
            'pendiente'  => 'warning', 'bloqueado'  => 'danger',  'rechazado' => 'danger',  'rechazada' => 'danger',
        ];
        $color = $colores[$estado] ?? 'secondary';
        $texto = ucfirst($estado);
        return '<span class="badge text-bg-' . $color . '">' . self::e($texto) . '</span>';
    }

    /**
     * Fase 8b (cambio 1.1): botón del "ojito" que se dibuja DENTRO de un campo de
     * contraseña. Va dentro de un <div class="pw-wrap"> junto al <input>; el
     * JavaScript (assets/js/password.js) alterna el input entre password y text.
     * Son dos íconos SVG: ojo abierto (contraseña oculta) y ojo tachado (visible).
     */
    public static function ojoPassword(): string
    {
        return '<button type="button" class="pw-ojo" aria-label="Mostrar contraseña" title="Mostrar u ocultar la contraseña">'
            . '<svg class="ojo-abierto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>'
            . '<svg class="ojo-cerrado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94"/>'
            . '<path d="M9.9 4.24A10.4 10.4 0 0 1 12 5c7 0 11 7 11 7a19.7 19.7 0 0 1-3.17 4.19"/>'
            . '<path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>'
            . '</button>';
    }
}
