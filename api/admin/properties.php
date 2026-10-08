<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$pdo = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT p.*, ph.name as phase_name, b.name as block_name, b.sector_type
            FROM properties p
            LEFT JOIN phases ph ON p.phase_id = ph.id
            LEFT JOIN blocks b ON p.block_id = b.id
            ORDER BY p.id DESC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statusColors = [
        'Available' => '#10b981',
        'Sold' => '#ef4444',
        'Reserved' => '#f59e0b',
        'Hold' => '#3b82f6',
    ];

    $properties = array_map(function($p) use ($statusColors) {
        $poly = null;
        if (!empty($p['polygon_coordinates'])) {
            $decoded = json_decode($p['polygon_coordinates'], true);
            if (is_array($decoded)) $poly = $decoded;
        }
        $color = $statusColors[$p['status']] ?? '#6b7280';
        return [
            'id' => (int)$p['id'],
            'title' => $p['title'],
            'plot_number' => $p['plot_number'],
            'phase_id' => (int)$p['phase_id'],
            'phase_name' => $p['phase_name'] ?? '',
            'block_id' => (int)$p['block_id'],
            'block_name' => $p['block_name'] ?? '',
            'sector_type' => $p['sector_type'] ?? '',
            'street' => $p['street'],
            'property_type' => $p['property_type'],
            'plot_size' => $p['plot_size'],
            'price' => (float)$p['price'],
            'status' => $p['status'],
            'status_color' => $color,
            'description' => $p['description'],
            'latitude' => (float)$p['latitude'],
            'longitude' => (float)$p['longitude'],
            'has_polygon' => !empty($poly),
            'polygon_coordinates' => $poly,
            'featured_image' => !empty($p['featured_image']) ? $p['featured_image'] : 'uploads/properties/default-plot.svg',
            'owner_dealer_name' => $p['owner_dealer_name'],
            'phone' => $p['phone'],
            'whatsapp' => $p['whatsapp'],
            'created_at' => $p['created_at'],
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $properties]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        exit;
    }

    $required = ['plot_number', 'block_id', 'plot_size', 'price', 'latitude', 'longitude'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }

    $sql = "INSERT INTO properties (title, plot_number, phase_id, block_id, street, property_type, plot_size, price, status, description, latitude, longitude, polygon_coordinates, owner_dealer_name, phone, whatsapp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $phaseId = $input['phase_id'] ?? 1;
    $status = $input['status'] ?? 'Available';
    $propertyType = $input['property_type'] ?? 'Residential Plot';
    $street = $input['street'] ?? null;
    $desc = $input['description'] ?? null;
    $dealer = $input['owner_dealer_name'] ?? 'Authorized DHA Multan Realtor';
    $phone = $input['phone'] ?? '+92 300 1234567';
    $whatsapp = $input['whatsapp'] ?? '923001234567';
    $polygon = !empty($input['polygon_coordinates']) ? json_encode($input['polygon_coordinates']) : null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['title'] ?? $input['plot_number'],
        $input['plot_number'],
        $phaseId,
        $input['block_id'],
        $street,
        $propertyType,
        $input['plot_size'],
        $input['price'],
        $status,
        $desc,
        $input['latitude'],
        $input['longitude'],
        $polygon,
        $dealer,
        $phone,
        $whatsapp,
    ]);

    $newId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => (int)$newId, 'message' => 'Plot published successfully']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
