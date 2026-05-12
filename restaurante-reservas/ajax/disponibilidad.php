<?php
// ============================================
// ajax/disponibilidad.php
// Endpoint que devuelve la disponibilidad de un día.
//
// Uso: GET disponibilidad.php?id_bar=1&fecha=2025-12-20[&excluir=15]
// Respuesta JSON: { total, limite, disponible }
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/reservas.php';

header('Content-Type: application/json');

// Solo usuarios logueados pueden consultar
if (!estaLogueado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$idBar   = (int)($_GET['id_bar'] ?? 0);
$fecha   = trim($_GET['fecha'] ?? '');
$excluir = isset($_GET['excluir']) ? (int)$_GET['excluir'] : null;

if ($idBar <= 0 || $fecha === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

// Comprobar formato de fecha (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido']);
    exit;
}

$total = contarReservasDelDia($pdo, $idBar, $fecha, $excluir);

echo json_encode([
    'total'      => $total,
    'limite'     => LIMITE_RESERVAS_DIARIAS,
    'disponible' => $total < LIMITE_RESERVAS_DIARIAS,
]);
