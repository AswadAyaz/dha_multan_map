<?php
/**
 * API: Get Single Property Details with image gallery
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT 
            p.*, 
            ph.name as phase_name, 
            b.name as block_name,
            b.sector_type as sector_type
        FROM properties p
        JOIN phases ph ON p.phase_id = ph.id
        JOIN blocks b ON p.block_id = b.id
        WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Property not found']);
    exit;
}

// Fetch images
$imgStmt = $pdo->prepare("SELECT image_path, is_cover FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, id ASC");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

$poly = null;
if (!empty($p['polygon_coordinates'])) {
    $decoded = json_decode($p['polygon_coordinates'], true);
    if (is_array($decoded)) {
        $poly = $decoded;
    }
}

$statusColors = [
    'Available' => '#10b981',
    'Sold'      => '#ef4444',
    'Reserved'  => '#f59e0b',
    'Hold'      => '#3b82f6',
];

$cleanPhone = preg_replace('/[^0-9+]/', '', $p['phone']);
$cleanWhatsApp = preg_replace('/[^0-9]/', '', $p['whatsapp']);

$data = [
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
    'status_color'        => $statusColors[$p['status']] ?? '#6b7280',
    'description'         => $p['description'],
    'latitude'            => (float)$p['latitude'],
    'longitude'           => (float)$p['longitude'],
    'has_polygon'         => !empty($poly),
    'polygon_coordinates' => $poly,
    'featured_image'      => !empty($p['featured_image']) ? $p['featured_image'] : 'uploads/properties/plot-102.svg',
    'images'              => !empty($images) ? $images : [['image_path' => $p['featured_image'] ?? 'uploads/properties/plot-102.svg', 'is_cover' => 1]],
    'owner_dealer_name'   => $p['owner_dealer_name'],
    'phone'               => $p['phone'],
    'whatsapp'            => $p['whatsapp'],
    'whatsapp_url'        => 'https://wa.me/' . $cleanWhatsApp . '?text=' . urlencode('Hi, I want to inquire about DHA Multan ' . $p['block_name'] . ' ' . $p['plot_number'] . ' (' . $p['title'] . ').'),
    'tel_url'             => 'tel:' . $cleanPhone,
    'created_at'          => $p['created_at']
];

echo json_encode([
    'success' => true,
    'data' => $data
]);
