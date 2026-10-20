<?php
// src/helpers/Seguridad.php
// ---------------------------------------------------------------------------
// Fase 8 - Endurecimiento de seguridad (no implementa un caso de uso: protege
// a todos). Reúne en UN solo lugar dos cosas que antes no existían:
//   1) las CABECERAS HTTP de seguridad que se envían en todas las páginas;
//   2) la configuración segura de la COOKIE de sesión.
// Se llama desde src/bootstrap.php, que ya es el único require de las páginas,
// así que ninguna pantalla se puede "olvidar" de aplicarlas.
// ---------------------------------------------------------------------------

class Seguridad
{
    /**
     * Envía las cabeceras de seguridad. Tiene que ejecutarse ANTES de que la
     * página imprima cualquier HTML (por eso se llama desde bootstrap.php).
     */
    public static function enviarCabeceras(): void
    {
        // En el servidor de línea de comandos (pruebas) no hay cabeceras que enviar.
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        // PHP anuncia su versión en cada respuesta (X-Powered-By): se la quitamos
        // al atacante para que no sepa qué versión buscar vulnerabilidades.
        header_remove('X-Powered-By');

        // El navegador NO debe "adivinar" el tipo de un archivo: un .jpg que
        // en realidad es código no se ejecuta como script.
        header('X-Content-Type-Options: nosniff');

        // Nadie puede mostrar FixNow dentro de un <iframe> en otro sitio
        // (evita el "clickjacking": engañar para que hagas clic en botones ocultos).
        header('X-Frame-Options: DENY');

        // Al salir hacia otro sitio, solo se informa el dominio, no la URL completa.
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // El sitio no necesita cámara, micrófono ni geolocalización del navegador.
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy: lista blanca de DE DÓNDE puede cargar cosas la
        // página. Solo permitimos nuestro propio sitio, Bootstrap (jsDelivr) y las
        // fuentes de Google. 'unsafe-inline' hace falta porque algunas pantallas
        // usan estilos y confirmaciones escritos dentro del HTML.
        header("Content-Security-Policy: default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
             . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
             . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
             . "img-src 'self' data: https:; "
             . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

        // Si algún día el sitio se publica con HTTPS, obliga al navegador a usar
        // siempre HTTPS. Solo se envía cuando la conexión YA es segura.
        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=15552000');
        }
    }

    /**
     * Ajusta la cookie de sesión ANTES de session_start() (lo llama Sesion::iniciar).
     *   - httponly : JavaScript no puede leer la cookie (frena el robo por XSS).
     *   - samesite : el navegador no la manda cuando otro sitio dispara la
     *                petición (segunda barrera contra CSRF, además del token).
     *   - secure   : solo viaja por HTTPS cuando el sitio usa HTTPS.
     *   - strict_mode: PHP rechaza ids de sesión inventados por un atacante.
     */
    public static function configurarSesion(): void
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,            // se borra al cerrar el navegador
            'path'     => '/',
            'secure'   => self::esHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** true si la petición llegó por HTTPS (también detrás de un proxy). */
    public static function esHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    /**
     * Devuelve el valor solo si es un TEXTO; si no (por ejemplo una lista que
     * alguien armó a mano con "campo[]=x"), devuelve ''. Evita errores 500 de PHP
     * al pasar datos del formulario a funciones que esperan un string.
     */
    public static function texto($valor): string
    {
        return is_string($valor) ? $valor : '';
    }
}
