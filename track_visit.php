<?php
// track_visit.php — call once per page load via JS fetch (fire-and-forget)
ob_start();
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/auth.php";

function send(array $d): void { ob_end_clean(); header('Content-Type: application/json'); echo json_encode($d); exit(); }

$page = clean($_POST['page'] ?? 'unknown', 100);

$visits->insertOne([
    'page'       => $page,
    'username'   => is_logged_in() ? current_user()['username'] : null,
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
    'visited_at' => date('Y-m-d H:i:s'),
]);

send(['ok' => true]);