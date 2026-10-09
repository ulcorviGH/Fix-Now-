<?php
// public/panel_cliente.php
// ---------------------------------------------------------------------------
// Pantalla principal del Cliente (CU-03 "Ver trabajadores cercanos y buscar").
//   - Sin parámetros en la URL: muestra la lista por cercanía.
//   - Con ?buscar=1 (el formulario usa method="get"): muestra los resultados
//     de la búsqueda, ordenados por calificación.
// Se usa GET (y no POST) para que la URL de una búsqueda se pueda copiar,
// recargar o volver atrás sin que el navegador pida reenviar el formulario.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../src/bootstrap.php';

Sesion::requerirRol('cliente');

$idUsuario = Sesion::idUsuarioActual();
$pdo = Database::getConexion();
$localidades = (new Localidad($pdo))->listarTodas(); // para el filtro de zonas

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
    $filtros = ['oficio' => '', 'localidades' => [], 'calificacion_minima' => null];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FixNow - Buscar trabajadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../src/views/navbar.php'; ?>

<div class="container pb-5">

    <!-- ================= BARRA DE BÚSQUEDA (CU-03 paso 2) ================= -->
    <form method="get" class="card card-body shadow-sm mb-4">
        <input type="hidden" name="buscar" value="1">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Oficio o servicio</label>
                <input type="text" name="oficio" class="form-control"
                       placeholder="Ej: plomero, destape de cañerías"
                       value="<?= Vista::e($filtros['oficio']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Localidades (Ctrl+clic para varias)</label>
                <select name="localidades[]" class="form-select" multiple size="3">
                    <?php foreach ($localidades as $loc): ?>
                        <option value="<?= (int) $loc['id_localidad'] ?>"
                            <?= in_array((int) $loc['id_localidad'], $filtros['localidades'], true) ? 'selected' : '' ?>>
                            <?= Vista::e($loc['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
            No se encontraron trabajadores en las zonas elegidas.
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
                                <?php if ($modo === 'cercania' && isset($t['distancia_km'])): ?>
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
</body>
</html>
