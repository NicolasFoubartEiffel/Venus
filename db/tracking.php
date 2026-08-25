<?php
require_once __DIR__ . '/functions.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && ($_GET['export'] ?? '') === 'csv') {
    exportTileInteractionStatsCsv();
}

if ($method !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Methode non autorisee',
    ]);
    exit;
}

$tileId = (int)($_POST['tile_id'] ?? 0);
$tabKey = (string)($_POST['tab_key'] ?? 'tile');

$success = recordTileInteraction($tileId, $tabKey);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => $success,
]);
