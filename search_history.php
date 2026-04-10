<?php
/**
 * search_history.php — saves/retrieves user search history to MongoDB.
 * Keeps last 10 unique searches per logged-in user.
 * GET  → returns history
 * POST → saves a search term
 */
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

header('Content-Type: application/json');

if (!is_logged_in()) { ob_end_clean(); echo json_encode(['history'=>[]]); exit; }

$username = current_user()['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term = clean($_POST['term'] ?? '', 100);
    if ($term) {
        $u = $users->findOne(['username'=>$username]);
        $hist = is_array($u['search_history'] ?? null) ? $u['search_history'] : [];
        // Remove if already exists (dedup), add to front
        $hist = array_values(array_filter($hist, fn($h) => $h !== $term));
        array_unshift($hist, $term);
        $hist = array_slice($hist, 0, 10);
        $users->updateOne(['username'=>$username], ['$set'=>['search_history'=>$hist]]);
    }
    ob_end_clean();
    echo json_encode(['ok'=>true]);
    exit;
}

$u    = $users->findOne(['username'=>$username]);
$hist = is_array($u['search_history'] ?? null) ? $u['search_history'] : [];
ob_end_clean();
echo json_encode(['history'=>$hist]);