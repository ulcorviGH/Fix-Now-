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
}
