<?php //shared authentication & CSRF(cross-site request forgery) helpers

// CSRF 
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// Auth helpers 
function is_logged_in(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function require_login(string $redirect = 'index.php'): void {
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit();
    }
}

// Input sanitisation 
function clean(string $val, int $maxLen = 255): string {
    return substr(trim($val), 0, $maxLen);
}