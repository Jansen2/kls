<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!DATA_EXPORT_ENABLED) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Export disabled']);
    exit;
}

$export_key = get_export_encryption_key();
if ($export_key === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export encryption key not configured']);
    exit;
}

if (DATA_EXPORT_API_KEY === 'change-me' || strlen(DATA_EXPORT_API_KEY) < 20) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export API key not configured']);
    exit;
}

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');
if (!hash_equals(DATA_EXPORT_API_KEY, $api_key)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

cleanup_expired_exports();

$token = $_GET['token'] ?? '';
if ($token === '' && DATA_EXPORT_ALLOW_POLL) {
    $token = find_oldest_export_token();
}

if ($token === '' || $token === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No data available']);
    exit;
}

if (!is_valid_export_token($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$record = read_export_record($token);
if ($record === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

$expires_at = (int)($record['expires_at'] ?? 0);
if ($expires_at > 0 && $expires_at < time()) {
    delete_export_record($token);
    http_response_code(410);
    echo json_encode(['success' => false, 'message' => 'Record expired']);
    exit;
}

if (DATA_EXPORT_CONSUME_ON_READ) {
    delete_export_record($token);
}

echo json_encode([
    'success' => true,
    'token' => $token,
    'record' => $record
]);
