<?php
// public/panel_cliente.php
// ---------------------------------------------------------------------------
// Pantalla principal del Cliente (CU-03 "Ver trabajadores cercanos y buscar").
// Fase 8b: en el menú se llama "Inicio" y también la usa el trabajador cuando
// actúa como cliente (cambio 3.4). El buscador filtra por RADIO en km (slider)
// usando la ubicación del navegador (assets/js/buscar.js) y la fórmula de
// Haversine en el modelo Trabajador.
//   - Sin parámetros en la URL: muestra la lista por cercanía.
//   - Con ?buscar=1 (el formulario usa method="get"): muestra los resultados
//     de la búsqueda, ordenados por calificación.
// Se usa GET (y no POST) para que la URL de una búsqueda se pueda copiar,
// recargar o volver atrás sin que el navegador pida reenviar el formulario.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

// Fase 8b (cambio 3.4): cliente Y trabajador (un trabajador puede contratar a otro).
Sesion::requerirRoles(['cliente', 'trabajador']);

$idUsuario = Sesion::idUsuarioActual();
$pdo = Database::getConexion();

$esBusqueda = isset($_GET['buscar']);

if ($esBusqueda) {
    $resultado = BusquedaController::buscar($idUsuario, $_GET);
    $trabajadores = $resultado['trabajadores'];
    $filtros = $resultado['filtros'];
    $modo = 'busqueda';
} else {
    $resultado = BusquedaController::listaInicial($idUsuario);
    $trabajadores = $resultado['trabajadores'];
    $modo = $resultado['modo']; // 'cercania' o 'mejor_calificados'
    $filtros = ['oficio' => '', 'localidades' => [], 'calificacion_minima' => null,
                'radio' => null, 'origen' => null, 'lat' => null, 'lng' => null];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5">

    <!-- ================= BARRA DE BÚSQUEDA (CU-03 paso 2) ================= -->
    <form method="get" class="card card-body shadow-sm mb-4" id="formBusqueda">
        <input type="hidden" name="buscar" value="1">
        <!-- Fase 8b: coordenadas del navegador; las completa assets/js/buscar.js con navigator.geolocation -->
        <input type="hidden" name="lat" id="lat" value="<?= $filtros['lat'] !== null ? Vista::e((string) $filtros['lat']) : '' ?>">
        <input type="hidden" name="lng" id="lng" value="<?= $filtros['lng'] !== null ? Vista::e((string) $filtros['lng']) : '' ?>">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <!-- Cambio 2.1: el texto indicativo del buscador central -->
                <label class="form-label small mb-1" for="oficio">Buscar trabajador</label>
                <input type="text" name="oficio" id="oficio" class="form-control"
                       placeholder="Ej: plomero, destape de cañerías"
                       value="<?= Vista::e($filtros['oficio']) ?>">
            </div>
            <div class="col-md-4">
                <!-- Cambio 2.2: slider de radio en km (reemplaza al desplegable de localidades) -->
                <label class="form-label small mb-1" for="radio">
                    Radio de búsqueda: <strong><output id="radioValor"><?= (int) ($filtros['radio'] ?? 25) ?></output> km</strong>
                </label>
                <input type="range" class="form-range" name="radio" id="radio" min="1" max="100" step="1"
                       value="<?= (int) ($filtros['radio'] ?? 25) ?>">
                <div class="small text-muted" id="estadoUbicacion">📍 Buscando tu ubicación…</div>
                <div class="form-check form-check-inline mt-1 small">
                    <input class="form-check-input" type="checkbox" name="origen" value="localidad" id="usarLocalidad"
                           <?= ($filtros['origen'] ?? null) === 'localidad' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="usarLocalidad">Usar mi localidad en vez del GPS</label>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Calificación mínima</label>
                <select name="calificacion_minima" class="form-select">
                    <option value="">Cualquiera</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>"
                            <?= ($filtros['calificacion_minima'] !== null && (int) $filtros['calificacion_minima'] === $i) ? 'selected' : '' ?>>
                            <?= $i ?>+ ★
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </div>
    </form>

    <!-- ================= TÍTULO DE LA LISTA ================= -->
    <h2 class="h5 mb-3">
        <?php if ($modo === 'busqueda'): ?>
            Resultados de la búsqueda
            <?php if (!empty($filtros['radio'])): ?>
                <small class="text-muted fw-normal">· hasta <?= (int) $filtros['radio'] ?> km desde
                    <?= ($filtros['origen'] ?? '') === 'gps' ? 'tu ubicación actual' : 'tu localidad' ?></small>
            <?php endif; ?>
            <a href="panel_cliente.php" class="btn btn-link btn-sm">Ver cercanos</a>
        <?php elseif ($modo === 'cercania'): ?>
            Trabajadores cerca tuyo
        <?php else: ?>
            Trabajadores mejor calificados
            <small class="text-muted fw-normal">(todavía no hay trabajadores cerca de tu zona)</small>
        <?php endif; ?>
    </h2>

    <!-- ================= LISTA ================= -->
    <?php if (empty($trabajadores)): ?>
        <!-- CU-03 excepción 5.1 -->
        <div class="alert alert-info">
            <?php if (!empty($filtros['radio'])): ?>
                No se encontraron trabajadores en un radio de <?= (int) $filtros['radio'] ?> km.
                Probá ampliar el radio o tildar "Usar mi localidad".
            <?php else: ?>
                No se encontraron trabajadores en las zonas elegidas.
            <?php endif; ?>
            <a href="panel_cliente.php" class="alert-link">Ver trabajadores cercanos</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($trabajadores as $t): ?>
                <div class="col-md-6">
                    <a href="perfil_trabajador.php?id=<?= (int) $t['id_trabajador'] ?>"
                       class="text-decoration-none text-reset">
                        <div class="card shadow-sm h-100 tarjeta-trabajador">
                            <div class="card-body d-flex gap-3">
                                <?= Vista::avatar($t['foto_perfil'], $t['nombre'], $t['apellido'], 64) ?>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <?= Vista::e($t['nombre'] . ' ' . $t['apellido']) ?>
                                    </div>
                                    <div class="text-primary small"><?= Vista::e($t['oficio']) ?></div>
                                    <div class="small text-muted">📍 <?= Vista::e($t['localidades']) ?></div>
                                    <div class="mt-1"><?= Vista::estrellas($t['calificacion_promedio']) ?></div>
                                </div>
                                <?php if (isset($t['distancia_km']) && $t['distancia_km'] !== null): ?>
                                    <div class="text-end small text-muted">
                                        a ~<?= (int) round((float) $t['distancia_km']) ?> km
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/buscar.js"></script>
</body>
</html>
