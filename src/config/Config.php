<?php
// src/config/Config.php
// ---------------------------------------------------------------------------
// Valores de configuración de la aplicación que conviene poder cambiar en un
// solo lugar, sin buscarlos por todo el código.
// ---------------------------------------------------------------------------

class Config
{
    // Días que deben pasar desde que el trabajador ACEPTA un trabajo hasta que
    // el sistema pregunta a ambas partes "¿Ya se completó el trabajo?"
    // (CU-21 paso 6).
    //   >>> PARA PROBAR: poné 0 y la pregunta aparece enseguida. <<<
    public const DIAS_PRIMERA_PREGUNTA = 2;

    // Días que el sistema espera para volver a preguntar después de un
    // "Todavía no" (CU-21 paso 7: "cada 2 o 3 días").
    public const DIAS_REINTENTO = 2;

    // -----------------------------------------------------------------
    // Fase 7
    // -----------------------------------------------------------------

    // CU-02 excepción 3.2: cuántos intentos fallidos seguidos se permiten y
    // cuántos minutos queda bloqueada la cuenta después.
    //   >>> PARA PROBAR: poné MINUTOS_BLOQUEO_LOGIN = 1. Con 0 se desactiva el bloqueo. <<<
    public const MAX_INTENTOS_LOGIN = 3;
    public const MINUTOS_BLOQUEO_LOGIN = 15;

    // CU-17: tamaño máximo de cada foto (5 MB, como pide el caso de uso).
    public const MAX_BYTES_FOTO = 5 * 1024 * 1024;

    // Topes técnicos para que nadie llene el disco (NO están en los casos de
    // uso: son una protección agregada, se pueden cambiar acá).
    public const MAX_FOTOS_PORTFOLIO = 12;
    public const MAX_SERVICIOS = 20;

    // CU-14: cuántas calificaciones de un cliente hacia el MISMO trabajador puede
    // eliminar el Administrador antes de bloquearle la posibilidad de calificarlo.
    // Con 2: la primera baja permite volver a calificar; la segunda lo bloquea.
    public const MAX_BAJAS_CALIFICACION = 2;

    // -----------------------------------------------------------------
    // Fase 8 (seguridad). Son topes agregados que NO figuran en los casos de uso.
    // -----------------------------------------------------------------

    // Largo mínimo de la contraseña al registrarse (CU-01). El máximo es 72 BYTES
    // porque bcrypt ignora en silencio todo lo que pase de ese largo.
    public const MIN_LARGO_CONTRASENA = 8;
    public const MAX_BYTES_CONTRASENA = 72;

    // Antispam del chat (CU-08): máximo de mensajes que una persona puede enviar
    // en una ventana de 60 segundos. Con 0 se desactiva el límite.
    public const MAX_MENSAJES_POR_MINUTO = 30;
}
