<?php
// public/api/localidades.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 7). API del AUTOCOMPLETADO de localidades. La usa
// assets/js/localidades.js mientras se escribe en el buscador de localidades.
//
//   GET api/localidades.php?q=ros   ->   [{"id":123,"nombre":"Rosario","provincia":"Santa Fe"}, ...]
//
// Es PÚBLICA (el registro la necesita antes de tener sesión) pero solo LEE el
// catálogo de localidades: no expone datos de usuarios.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode(
    CatalogoController::sugerirLocalidades($_GET['q'] ?? ''),
    JSON_UNESCAPED_UNICODE
);
