<?php
header('Content-Type: application/json; charset=utf-8');

$overlayFile = __DIR__ . '/../database/master-plan-overlay.json';

$default = [
    'parts' => [],
    'opacity' => 0.85,
    'locked' => true,
    'enabled' => true,
    'visible_public' => true,
];

if (file_exists($overlayFile)) {
    $raw = file_get_contents($overlayFile);
    $data = json_decode($raw, true);
    if ($data) {
        $merged = array_merge($default, $data);
        if (!isset($merged['enabled'])) $merged['enabled'] = true;
        if (!isset($merged['visible_public'])) $merged['visible_public'] = true;
        if (!isset($merged['parts'])) $merged['parts'] = [];
        // Ensure each part has required fields
        foreach ($merged['parts'] as &$part) {
            $part['opacity'] = $part['opacity'] ?? $merged['opacity'];
            $part['enabled'] = $part['enabled'] ?? true;
            $part['visible_public'] = $part['visible_public'] ?? true;
        }
        echo json_encode(['success' => true, 'overlay' => $merged]);
        exit;
    }
}

echo json_encode(['success' => true, 'overlay' => $default]);