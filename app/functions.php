<?php
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function only_ajax(): void {
    if (
        empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
    ) {
        json_response(['error' => 'AJAX only'], 403);
    }
}

function validate_csrf(string $token): bool {
    start_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generate_csrf(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function get_imbalance(array $muscle_intensities): array {
    if (empty($muscle_intensities)) return [];
    $avg = array_sum(array_column($muscle_intensities, 'intensity')) / count($muscle_intensities);
    $undertrained = [];
    foreach ($muscle_intensities as $m) {
        if ((float)$m['intensity'] < $avg * 0.6) {
            $undertrained[] = $m;
        }
    }
    return $undertrained;
}