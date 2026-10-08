<?php
/**
 * API: Get DHA Multan Phases & Blocks
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

$phasesStmt = $pdo->query("SELECT * FROM phases ORDER BY id ASC");
$phases = $phasesStmt->fetchAll(PDO::FETCH_ASSOC);

$blocksStmt = $pdo->query("SELECT * FROM blocks ORDER BY name ASC");
$blocks = $blocksStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'phases' => $phases,
    'blocks' => $blocks
]);
