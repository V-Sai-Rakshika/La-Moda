<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validate required env variables
$required = [
    'MONGODB_URI',
    'MONGODB_DB',
    'GMAIL_USER',
    'GMAIL_PASS',
    'CASHFREE_APP_ID',
    'CASHFREE_SECRET_KEY'
];

foreach ($required as $key) {
    if (!isset($_ENV[$key])) {
        die("Missing environment variable: $key");
    }
}

// Central config
$config = [
    'db' => [
        'uri' => $_ENV['MONGODB_URI'],
        'name' => $_ENV['MONGODB_DB'],
    ],
    'mail' => [
        'user' => $_ENV['GMAIL_USER'],
        'pass' => $_ENV['GMAIL_PASS'],
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'La Moda'
    ],
    'cashfree' => [
        'app_id' => $_ENV['CASHFREE_APP_ID'],
        'secret' => $_ENV['CASHFREE_SECRET_KEY']
    ]
];

return $config;