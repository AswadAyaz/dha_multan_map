<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

$pdo = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE' || $method === 'POST') {
    $id = 0;
    if ($method === 'DELETE') {
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        $id = intval($qs['id'] ?? $_GET['id'] ?? 0);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
        exit;
    }

    $check = $pdo->prepare("SELECT id, plot_number FROM properties WHERE id = ?");
    $check->execute([$id]);
    $prop = $check->fetch(PDO::FETCH_ASSOC);

    if (!$prop) {
        echo json_encode(['success' => false, 'error' => 'Property not found']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => "Plot {$prop['plot_number']} has been deleted from the map.",
        'id' => $id,
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
