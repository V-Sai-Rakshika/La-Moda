<?php
ob_start();
session_start();
include "db.php";
include "auth.php";

function send(array $d): void { ob_end_clean(); header('Content-Type: application/json'); echo json_encode($d); exit(); }

$q = clean($_GET['q'] ?? '', 100);
if (strlen($q) < 1) send([]);

$cursor = $products->find(
    ['name' => ['$regex' => preg_quote($q, '/'), '$options' => 'i']],
    ['projection' => ['name' => 1, 'category' => 1], 'limit' => 8]
);

$results = [];
foreach ($cursor as $row) {
    $results[] = [
        'name'     => (string)$row['name'],
        'category' => (string)($row['category'] ?? ''),
    ];
}

send($results);