<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    $overlayFile = __DIR__ . '/../../database/master-plan-overlay.json';
    $dir = dirname($overlayFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Handle both old format (single overlay) and new format (parts array)
    if (isset($input['parts']) && is_array($input['parts'])) {
        // New format: saving all parts
        $overlay = [
            'parts' => [],
            'opacity' => $input['opacity'] ?? 0.85,
            'locked' => $input['locked'] ?? true,
            'enabled' => $input['enabled'] ?? true,
            'visible_public' => $input['visible_public'] ?? true,
        ];
        
        foreach ($input['parts'] as $part) {
            $imgUrl = $part['image_url'] ?? '/uploads/master_plan.png';
            if ($imgUrl && $imgUrl[0] !== '/') $imgUrl = '/' . $imgUrl;
            
            $overlay['parts'][] = [
                'id' => $part['id'] ?? 0,
                'name' => $part['name'] ?? 'Part',
                'image_url' => $imgUrl,
                'bounds' => $part['bounds'] ?? [
                    'north' => 30.335,
                    'south' => 30.275,
                    'east' => 71.595,
                    'west' => 71.525,
                ],
                'opacity' => $part['opacity'] ?? $overlay['opacity'],
                'enabled' => $part['enabled'] ?? true,
                'visible_public' => $part['visible_public'] ?? true,
            ];
        }
    } else if (isset($input['bounds'])) {
        // Old format: single overlay (backward compatibility) - convert to parts array with 1 part
        $imgUrl = $input['image_url'] ?? '/uploads/master_plan.png';
        if ($imgUrl && $imgUrl[0] !== '/') $imgUrl = '/' . $imgUrl;

        $overlay = [
            'parts' => [[
                'id' => 1,
                'name' => 'Part 1',
                'image_url' => $imgUrl,
                'bounds' => $input['bounds'],
                'opacity' => $input['opacity'] ?? 0.85,
                'enabled' => $input['enabled'] ?? true,
                'visible_public' => $input['visible_public'] ?? true,
            ]],
            'opacity' => $input['opacity'] ?? 0.85,
            'locked' => $input['locked'] ?? true,
            'enabled' => $input['enabled'] ?? true,
            'visible_public' => $input['visible_public'] ?? true,
        ];
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid overlay data']);
        exit;
    }

    file_put_contents($overlayFile, json_encode($overlay, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo json_encode(['success' => true, 'message' => 'Master plan overlay position saved']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);