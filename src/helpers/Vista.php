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
}
