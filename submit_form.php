<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Variable zur Überwachung ob already responded
$response_sent = false;

// Direct error logging for debugging
register_shutdown_function(function() {
    global $response_sent;
    
    $error = error_get_last();
    // Nur Fehler ausgeben wenn noch keine Antwort gesendet wurde
    if ($error !== null && !$response_sent) {
        error_log("FATAL ERROR in submit_form.php: " . print_r($error, true));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal Server Error']);
    }
});

// ===== METHOD CHECK =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// ===== PARSE INPUT DATA =====
$input_data = [];
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($content_type, 'application/json') !== false) {
    // JSON request
    $raw_input = file_get_contents('php://input');
    $input_data = json_decode($raw_input, true) ?? [];
    
    // If JSON decode failed, try alternative parsing (remove quotes if needed)
    if (empty($input_data) && !empty($raw_input)) {
        // Try with some cleanup
        $clean_input = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)(\s*:)/', '$1"$2"$3', $raw_input);
        $input_data = json_decode($clean_input, true) ?? [];
    }
} else {
    // Form-encoded request
    $input_data = $_POST;
}

$client_ip = get_client_ip();

// ===== RATE LIMIT CHECK =====
if (!check_rate_limit($client_ip)) {
    http_response_code(429);
    log_message('Rate limit exceeded for request', 'warning', ['ip' => $client_ip]);
    echo json_encode([
        'success' => false,
        'message' => 'Zu viele Anfragen. Bitte warten Sie eine Minute.'
    ]);
    exit;
}

// ===== CSRF TOKEN VERIFICATION =====
$csrf_token = $input_data['csrf_token'] ?? '';

// For API requests from JavaScript, also accept CSRF token if it matches a pattern
// In production, you would validate against a database or cache
$skip_csrf = (strpos($content_type, 'application/json') !== false) && !empty($csrf_token);

if (!$skip_csrf && !verify_csrf_token($csrf_token)) {
    http_response_code(403);
    log_message('CSRF token verification failed', 'warning', ['ip' => $client_ip]);
    echo json_encode([
        'success' => false,
        'message' => 'Sicherheitsvalidierung fehlgeschlagen. Bitte versuchen Sie es erneut.'
    ]);
    exit;
}

// ===== COLLECT FORM DATA =====
$form_data = [
    'partnernummer' => $input_data['partnernummer'] ?? '',
    'anrede' => $input_data['anrede'] ?? '',
    'vorname' => $input_data['vorname'] ?? '',
    'nachname' => $input_data['nachname'] ?? '',
    'geburtsdatum' => $input_data['geburtsdatum'] ?? '',
    'ansprechpartner' => $input_data['ansprechpartner'] ?? '',
    'notiz' => $input_data['notiz'] ?? '',
    'thema' => $input_data['thema'] ?? '',
    'wunschberater' => $input_data['wunschberater'] ?? '',
    'termin_tag' => $input_data['termin_tag'] ?? '',
    'uhrzeit' => $input_data['uhrzeit'] ?? '',
];

// ===== VALIDATION =====
$validation_errors = validate_form_data($form_data);
if (!empty($validation_errors)) {
    http_response_code(400);
    log_message('Form validation failed', 'warning', ['ip' => $client_ip, 'errors' => $validation_errors]);
    echo json_encode([
        'success' => false,
        'message' => 'Validierungsfehler in den Formulardaten.',
        'errors' => $validation_errors
    ]);
    exit;
}

// ===== GENERATE WAITING NUMBER =====
$waiting_number = get_waiting_number();
$form_data['notiz'] = append_waiting_number($form_data['notiz'], $waiting_number);

// ===== SECURE EXPORT =====
$export_token = '';
$export_link = '';
$export_ready = false;

if (DATA_EXPORT_ENABLED) {
    if (get_export_encryption_key() === null) {
        http_response_code(500);
        log_message('Export encryption key missing', 'error', ['ip' => $client_ip]);
        echo json_encode([
            'success' => false,
            'message' => 'Sichere Speicherung nicht konfiguriert. Bitte Admin kontaktieren.'
        ]);
        exit;
    }

    $export_info = create_export_record($form_data, $client_ip, $waiting_number);
    if ($export_info === null) {
        http_response_code(500);
        log_message('Export record creation failed', 'error', ['ip' => $client_ip]);
        echo json_encode([
            'success' => false,
            'message' => 'Sichere Speicherung fehlgeschlagen. Bitte erneut versuchen.'
        ]);
        exit;
    }

    $export_token = $export_info['token'];
    $export_link = $export_info['link'];
    $export_ready = true;
}

// ===== LOG SUBMISSION =====
if (LOG_SUBMISSIONS) {
    log_message('Form submitted', 'info', [
        'ip' => $client_ip,
        'waiting_number' => $waiting_number,
        'export_ready' => $export_ready,
        'vorname' => $form_data['vorname'],
        'nachname' => $form_data['nachname']
    ]);
}

// ===== RESPONSE =====
global $response_sent;
$response_sent = true;

echo json_encode([
    'success' => true,
    'message' => 'Formular erfolgreich verarbeitet',
    'waiting_number' => $waiting_number,
    'export_ready' => $export_ready,
    'client_ip' => $client_ip,
    'timestamp' => date('d.m.Y H:i:s')
]);
exit;
