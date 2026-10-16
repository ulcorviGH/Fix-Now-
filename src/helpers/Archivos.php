<?php
// src/helpers/Archivos.php
// ---------------------------------------------------------------------------
// Manejo seguro de las fotos que suben los trabajadores
// (CU-09 foto de perfil, CU-17 fotos de portfolio).
//
// Reglas de seguridad que aplica:
//   1) El tipo de archivo se detecta mirando el CONTENIDO real (finfo), nunca
//      la extensión ni el tipo que declara el navegador, porque ambos se pueden
//      falsificar (ej. un .php renombrado a .jpg).
//   2) Además se comprueba que sea una imagen de verdad (getimagesize).
//   3) El nombre final lo genera el servidor al azar: nunca se usa el nombre
//      que trae el archivo (evita pisar archivos o nombres maliciosos).
//   4) Solo se borran archivos que estén dentro de las carpetas de imágenes.
// ---------------------------------------------------------------------------

class Archivos
{
    /** Tipos permitidos -> extensión con la que se guardan. */
    private const EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Carpetas (dentro de public/) donde se guardan las imágenes. */
    public const CARPETA_PERFILES  = 'assets/img/perfiles';
    public const CARPETA_PORTFOLIO = 'assets/img/portfolio';

    /**
     * Valida y guarda una imagen subida.
     *   $archivo     : el elemento de $_FILES (ej. $_FILES['foto'])
     *   $carpeta     : una de las constantes CARPETA_* de arriba
     *   $tiposValidos: lista de tipos MIME aceptados (ej. ['image/jpeg','image/png'])
     *   $mensajeFormato: texto del error si el formato no sirve (lo define cada CU)
     * Devuelve ['ok' => true, 'ruta' => 'assets/img/...'] o ['ok' => false, 'error' => '...'].
     */
    public static function guardarImagen(?array $archivo, string $carpeta, array $tiposValidos, string $mensajeFormato): array
    {
        if ($archivo === null || !isset($archivo['error'])) {
            return ['ok' => false, 'error' => 'No se recibió ningún archivo.'];
        }

        // Errores que informa PHP al recibir el archivo.
        switch ($archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'error' => 'La imagen es demasiado grande. Máximo 5 MB.'];
            case UPLOAD_ERR_NO_FILE:
                return ['ok' => false, 'error' => 'No se recibió ningún archivo.'];
            default:
                return ['ok' => false, 'error' => 'No se pudo subir la imagen. Intentá de nuevo.'];
        }

        // Tamaño (CU-17 excepción 5.2).
        if ($archivo['size'] > Config::MAX_BYTES_FOTO) {
            return ['ok' => false, 'error' => 'La imagen es demasiado grande. Máximo 5 MB.'];
        }

        // Tipo REAL del archivo, según su contenido.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        if (!in_array($mime, $tiposValidos, true) || !isset(self::EXTENSIONES[$mime])) {
            return ['ok' => false, 'error' => $mensajeFormato];
        }

        // Debe poder leerse como imagen (descarta archivos truchos con cabecera falsa).
        if (@getimagesize($archivo['tmp_name']) === false) {
            return ['ok' => false, 'error' => $mensajeFormato];
        }

        if (!in_array($carpeta, [self::CARPETA_PERFILES, self::CARPETA_PORTFOLIO], true)) {
            return ['ok' => false, 'error' => 'Carpeta de destino inválida.'];
        }

        // Nombre aleatorio: 32 caracteres hexadecimales + extensión según el tipo real.
        $nombre = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONES[$mime];
        $destino = __DIR__ . '/../../public/' . $carpeta . '/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['ok' => false, 'error' => 'No se pudo guardar la imagen en el servidor.'];
        }

        return ['ok' => true, 'ruta' => $carpeta . '/' . $nombre];
    }

    /**
     * Borra del disco una imagen que guardamos nosotros. Si la ruta es otra
     * cosa (por ejemplo una URL externa de los datos de prueba), no hace nada.
     */
    public static function borrar(?string $ruta): void
    {
        if ($ruta === null || $ruta === '') {
            return;
        }
        // Solo rutas con la forma exacta que genera guardarImagen().
        if (!preg_match('#^assets/img/(perfiles|portfolio)/[a-f0-9]{32}\.(jpg|png|webp)$#', $ruta)) {
            return;
        }
        $archivo = __DIR__ . '/../../public/' . $ruta;
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}
