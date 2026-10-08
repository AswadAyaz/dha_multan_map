<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if (verifyCsrfToken($csrf) && $id > 0) {
        $pdo = getDBConnection();

        // Delete property record (cascading deletes images)
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header('Location: /admin/dashboard.php');
exit;
