<?php
// src/helpers/Sesion.php
// ---------------------------------------------------------------------------
// Encapsula el manejo de la sesión PHP (quién está logueado, con qué rol).
// Ninguna otra parte del código debería usar $_SESSION directamente:
// siempre a través de esta clase, para que si el día de mañana cambiamos
// cómo guardamos la sesión, solo hay que tocar este archivo.
// ---------------------------------------------------------------------------

class Sesion
{
    /**
     * Arranca la sesión de PHP si todavía no fue iniciada.
     * Hay que llamarla al principio de CADA página pública.
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Guarda los datos del usuario logueado en la sesión.
     * Se llama justo después de validar mail y contraseña correctamente.
     */
    public static function login(array $usuario): void
    {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol']; // 'cliente' | 'trabajador' | 'admin'
    }

    /** Cierra la sesión actual (CU: cerrar sesión / logout). */
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /** true si hay alguien logueado en este momento. */
    public static function estaLogueado(): bool
    {
        return isset($_SESSION['id_usuario']);
    }

    /** Devuelve el rol del usuario logueado, o null si no hay sesión. */
    public static function rolActual(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    /** Devuelve el id del usuario logueado, o null si no hay sesión. */
    public static function idUsuarioActual(): ?int
    {
        return $_SESSION['id_usuario'] ?? null;
    }

    /**
     * Corta la ejecución y manda al login si nadie inició sesión.
     * Se usa al principio de cualquier página que requiera estar logueado
     * (paneles, chat, perfil, etc.).
     */
    public static function requerirLogin(): void
    {
        if (!self::estaLogueado()) {
            header('Location: index.php');
            exit;
        }
    }

    /**
     * Corta la ejecución si el usuario logueado no tiene el rol pedido.
     * Ejemplo: Sesion::requerirRol('admin') en el panel de administración.
     */
    public static function requerirRol(string $rolEsperado): void
    {
        self::requerirLogin();
        if (self::rolActual() !== $rolEsperado) {
            header('Location: index.php');
            exit;
        }
    }
}
