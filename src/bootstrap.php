<?php
// src/bootstrap.php
// ---------------------------------------------------------------------------
// Punto único de carga del backend. Cada página en public/ hace:
//     require_once __DIR__ . '/../src/bootstrap.php';
// como PRIMERA línea, y con eso ya tiene disponibles todas las clases
// del proyecto, en el orden correcto, y la sesión ya iniciada.
// ---------------------------------------------------------------------------

// Configuración / conexión a la base de datos
require_once __DIR__ . '/config/Database.php';

// Helpers
require_once __DIR__ . '/helpers/Sesion.php';
require_once __DIR__ . '/helpers/Vista.php';          // (Fase 4)

// Modelos (uno por tabla)
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Trabajador.php';
require_once __DIR__ . '/models/Localidad.php';
require_once __DIR__ . '/models/Servicio.php';        // (Fase 4)
require_once __DIR__ . '/models/Imagen.php';          // (Fase 4)
require_once __DIR__ . '/models/Calificacion.php';    // (Fase 4)

// Controladores (orquestan los modelos para resolver un caso de uso)
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BusquedaController.php';  // (Fase 4)
require_once __DIR__ . '/controllers/PerfilController.php';    // (Fase 4)

// Arranca la sesión de PHP para toda la aplicación.
Sesion::iniciar();
