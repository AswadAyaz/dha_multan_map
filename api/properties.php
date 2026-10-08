<?php
/**
 * API: Get Properties with dynamic filters and search
 * Output: JSON
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();

$params = [];
$where = ["1=1"];

// Filter: Search Keyword
if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $where[] = "(p.title LIKE ? OR p.plot_number LIKE ? OR p.street LIKE ? OR b.name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Filter: Property Type
if (!empty($_GET['property_type'])) {
    $where[] = "p.property_type = ?";
    $params[] = $_GET['property_type'];
}

// Filter: Status
if (!empty($_GET['status'])) {
    $where[] = "p.status = ?";
    $params[] = $_GET['status'];
}

// Filter: Phase
if (!empty($_GET['phase_id'])) {
    $where[] = "p.phase_id = ?";
    $params[] = intval($_GET['phase_id']);
}

// Filter: Block
if (!empty($_GET['block_id'])) {
    $where[] = "p.block_id = ?";
    $params[] = intval($_GET['block_id']);
}

// Filter: Plot Size
if (!empty($_GET['plot_size'])) {
    $where[] = "p.plot_size = ?";
    $params[] = $_GET['plot_size'];
}

// Filter: Price Range
if (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where[] = "p.price >= ?";
    $params[] = floatval($_GET['min_price']);
}

if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where[] = "p.price <= ?";
    $params[] = floatval($_GET['max_price']);
}

$sql = "SELECT 
            p.*, 
            ph.name as phase_name, 
            b.name as block_name,
            b.sector_type as sector_type
        FROM properties p
        JOIN phases ph ON p.phase_id = ph.id
        JOIN blocks b ON p.block_id = b.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Color scheme mapping based on status
$statusColors = [
    'Available' => '#10b981', // Emerald Green
    'Sold'      => '#ef4444', // Crimson Red
    'Reserved'  => '#f59e0b', // Amber Orange
    'Hold'      => '#3b82f6', // Dodger Blue
];

$properties = array_map(function($p) use ($statusColors) {
    $poly = null;
    if (!empty($p['polygon_coordinates'])) {
        $decoded = json_decode($p['polygon_coordinates'], true);
        if (is_array($decoded)) {
            $poly = $decoded;
        }
    }

    $color = $statusColors[$p['status']] ?? '#6b7280';

    return [
        'id'                  => (int)$p['id'],
        'title'               => $p['title'],
        'plot_number'         => $p['plot_number'],
        'phase_id'            => (int)$p['phase_id'],
        'phase_name'          => $p['phase_name'],
        'block_id'            => (int)$p['block_id'],
        'block_name'          => $p['block_name'],
        'sector_type'         => $p['sector_type'],
        'street'              => $p['street'],
        'property_type'       => $p['property_type'],
        'plot_size'           => $p['plot_size'],
        'price'               => (float)$p['price'],
        'formatted_price'     => formatPKR($p['price']),
        'status'              => $p['status'],
        'status_color'        => $color,
        'description'         => $p['description'],
        'latitude'            => (float)$p['latitude'],
        'longitude'           => (float)$p['longitude'],
        'has_polygon'         => !empty($poly),
        'polygon_coordinates' => $poly,
        'featured_image'      => !empty($p['featured_image']) ? $p['featured_image'] : 'uploads/properties/default-plot.svg',
        'owner_dealer_name'   => $p['owner_dealer_name'],
        'phone'               => $p['phone'],
        'whatsapp'            => $p['whatsapp'],
        'whatsapp_url'        => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $p['whatsapp']) . '?text=' . urlencode('Hi, I am interested in DHA Multan ' . $p['block_name'] . ' ' . $p['plot_number'] . ' (' . $p['title'] . ') listed on DHA Multan Property Map.'),
        'tel_url'             => 'tel:' . preg_replace('/[^0-9+]/', '', $p['phone']),
        'created_at'          => $p['created_at']
    ];
}, $rows);

echo json_encode([
    'success' => true,
    'count' => count($properties),
    'data' => $properties
]);
