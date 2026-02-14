<?php
require_once __DIR__ . '/../config.php';

// ===== UTILITIES =====
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_list[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ===== LOGGING =====
function ensure_log_dir(): void {
    if (LOG_ENABLED && !is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0755, true);
    }
}

function log_message(string $message, string $level = 'info', array $context = []): void {
    if (!LOG_ENABLED) {
        return;
    }

    ensure_log_dir();

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = [
        'timestamp' => $timestamp,
        'level' => $level,
        'message' => $message,
        'ip' => get_client_ip(),
        'context' => $context
    ];

    $log_file = LOG_DIR . '/' . date('Y-m-d') . '.log';
    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($log_file, $log_line, FILE_APPEND);
}

// ===== VALIDATION =====
function validate_form_data(array $data): array {
    $errors = [];

    $vorname = trim($data['vorname'] ?? '');
    $nachname = trim($data['nachname'] ?? '');

    if (empty($vorname)) {
        $errors['vorname'] = 'Vorname ist erforderlich';
    } elseif (strlen($vorname) < VALIDATION_MIN_FIRSTNAME_LENGTH) {
        $errors['vorname'] = 'Vorname muss mindestens ' . VALIDATION_MIN_FIRSTNAME_LENGTH . ' Zeichen lang sein';
    }

    if (empty($nachname)) {
        $errors['nachname'] = 'Nachname ist erforderlich';
    } elseif (strlen($nachname) < VALIDATION_MIN_LASTNAME_LENGTH) {
        $errors['nachname'] = 'Nachname muss mindestens ' . VALIDATION_MIN_LASTNAME_LENGTH . ' Zeichen lang sein';
    }

    if (!empty($data['geburtsdatum'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['geburtsdatum'])) {
            $errors['geburtsdatum'] = 'Ungültiges Datumsformat';
        } else {
            try {
                $geburtsdatum_ts = strtotime($data['geburtsdatum']);
                $heute_ts = strtotime(date('Y-m-d'));
                if ($geburtsdatum_ts && $heute_ts && $geburtsdatum_ts > $heute_ts) {
                    $errors['geburtsdatum'] = 'Geburtsdatum kann nicht in der Zukunft liegen';
                }
            } catch (Exception $e) {
                $errors['geburtsdatum'] = 'Ungültiges Datumsformat';
            }
        }
    }

    if (!empty($data['termin_tag'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['termin_tag'])) {
            $errors['termin_tag'] = 'Ungültiges Datumsformat';
        }
    }

    if (!empty($data['uhrzeit'])) {
        if (!preg_match('/^\d{2}:\d{2}$/', $data['uhrzeit'])) {
            $errors['uhrzeit'] = 'Ungültiges Zeitformat';
        }
    }

    $notiz = $data['notiz'] ?? '';
    if (strlen($notiz) > VALIDATION_MAX_TEXT_LENGTH) {
        $errors['notiz'] = 'Notiz zu lang (max. ' . VALIDATION_MAX_TEXT_LENGTH . ' Zeichen)';
    }

    return $errors;
}

// ===== RATE LIMITING =====
function check_rate_limit(string $ip): bool {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }

    ensure_log_dir();
    $rate_limit_file = RATE_LIMIT_FILE;
    $now = time();
    $window_start = $now - RATE_LIMIT_WINDOW;

    $handle = @fopen($rate_limit_file, 'c+');
    if ($handle === false) {
        return true;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return true;
    }

    $contents = stream_get_contents($handle);
    $data = json_decode($contents ?: '{}', true) ?? [];

    if (!isset($data[$ip])) {
        $data[$ip] = [];
    }

    $data[$ip] = array_filter($data[$ip], fn($ts) => $ts > $window_start);
    $count = count($data[$ip]);

    if ($count >= RATE_LIMIT_REQUESTS) {
        flock($handle, LOCK_UN);
        fclose($handle);
        log_message('Rate limit exceeded', 'warning', ['ip' => $ip, 'count' => $count]);
        return false;
    }

    $data[$ip][] = $now;

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
}

// ===== CSRF TOKEN =====
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    if ($token !== $_SESSION['csrf_token']) {
        return false;
    }

    $token_age = time() - ($_SESSION['csrf_token_time'] ?? 0);
    if ($token_age > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return true;
}

// ===== SECURE EXPORT =====
function get_export_encryption_key(): ?string {
    $raw_key = DATA_EXPORT_ENCRYPTION_KEY;
    if ($raw_key === '') {
        return null;
    }

    $decoded = base64_decode($raw_key, true);
    if ($decoded === false || strlen($decoded) !== 32) {
        return null;
    }

    return $decoded;
}

function encrypt_export_payload(array $payload): ?array {
    $key = get_export_encryption_key();
    if ($key === null) {
        return null;
    }

    if (!function_exists('openssl_encrypt')) {
        log_message('OpenSSL not available for export encryption', 'error');
        return null;
    }

    $nonce = random_bytes(12);
    $plaintext = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($plaintext === false) {
        return null;
    }

    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag
    );

    if ($ciphertext === false) {
        return null;
    }

    return [
        'alg' => 'AES-256-GCM',
        'nonce' => base64_encode($nonce),
        'tag' => base64_encode($tag),
        'ciphertext' => base64_encode($ciphertext)
    ];
}

function ensure_export_dir(): void {
    if (DATA_EXPORT_ENABLED && !is_dir(DATA_EXPORT_DIR)) {
        @mkdir(DATA_EXPORT_DIR, 0755, true);
    }
}

function is_valid_export_token(string $token): bool {
    return (bool)preg_match('/^[a-f0-9]{32}$/', $token);
}

function build_export_link(string $token): string {
    if (!DATA_EXPORT_BASE_URL) {
        return '';
    }

    return rtrim(DATA_EXPORT_BASE_URL, '/') . '?token=' . $token;
}

function create_export_record(array $form_data, string $client_ip, string $waiting_number): ?array {
    if (!DATA_EXPORT_ENABLED) {
        return null;
    }

    ensure_export_dir();

    $token = bin2hex(random_bytes(16));
    $now = time();

    $plaintext_payload = [
        'waiting_number' => $waiting_number,
        'client_ip' => $client_ip,
        'form_data' => $form_data
    ];

    $encrypted_payload = encrypt_export_payload($plaintext_payload);
    if ($encrypted_payload === null) {
        return null;
    }

    $record = [
        'token' => $token,
        'created_at' => $now,
        'expires_at' => $now + DATA_EXPORT_TTL,
        'payload' => $encrypted_payload
    ];

    $file_path = DATA_EXPORT_DIR . '/' . $token . '.json';
    $result = @file_put_contents(
        $file_path,
        json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    if ($result === false) {
        return null;
    }

    return [
        'token' => $token,
        'link' => build_export_link($token),
        'path' => $file_path
    ];
}

function read_export_record(string $token): ?array {
    if (!is_valid_export_token($token)) {
        return null;
    }

    $file_path = DATA_EXPORT_DIR . '/' . $token . '.json';
    if (!is_file($file_path)) {
        return null;
    }

    $contents = @file_get_contents($file_path);
    if ($contents === false) {
        return null;
    }

    $data = json_decode($contents, true);
    if (!is_array($data)) {
        return null;
    }

    return $data;
}

function delete_export_record(string $token): void {
    if (!is_valid_export_token($token)) {
        return;
    }

    $file_path = DATA_EXPORT_DIR . '/' . $token . '.json';
    if (is_file($file_path)) {
        @unlink($file_path);
    }
}

function cleanup_expired_exports(): void {
    if (!DATA_EXPORT_ENABLED) {
        return;
    }

    ensure_export_dir();

    $files = glob(DATA_EXPORT_DIR . '/*.json') ?: [];
    $now = time();

    foreach ($files as $file_path) {
        $contents = @file_get_contents($file_path);
        if ($contents === false) {
            continue;
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            continue;
        }

        $expires_at = (int)($data['expires_at'] ?? 0);
        if ($expires_at > 0 && $expires_at < $now) {
            @unlink($file_path);
        }
    }
}

function find_oldest_export_token(): ?string {
    if (!DATA_EXPORT_ENABLED) {
        return null;
    }

    ensure_export_dir();

    $files = glob(DATA_EXPORT_DIR . '/*.json') ?: [];
    if (empty($files)) {
        return null;
    }

    usort($files, function ($a, $b) {
        return filemtime($a) <=> filemtime($b);
    });

    $oldest = $files[0] ?? '';
    if (!$oldest) {
        return null;
    }

    return basename($oldest, '.json');
}

// ===== QUEUE MANAGEMENT =====
function format_waiting_number(int $number): string {
    return str_pad((string)$number, 3, '0', STR_PAD_LEFT);
}

function get_waiting_number(): string {
    $today = date('Y-m-d');
    $counter = 1;

    $counter_file = QUEUE_COUNTER_FILE;
    
    // Erstelle das data-Directory falls es nicht existiert
    $data_dir = dirname($counter_file);
    if (!is_dir($data_dir)) {
        @mkdir($data_dir, 0755, true);
    }

    $handle = fopen($counter_file, 'c+');
    if ($handle === false) {
        return format_waiting_number($counter);
    }

    if (flock($handle, LOCK_EX)) {
        $contents = stream_get_contents($handle);
        $data = json_decode($contents ?: '', true);

        if (is_array($data) && ($data['date'] ?? '') === $today) {
            $counter = (int)($data['counter'] ?? 0) + 1;
        }

        $data = [
            'date' => $today,
            'counter' => $counter
        ];

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
        flock($handle, LOCK_UN);
    }

    fclose($handle);
    return format_waiting_number($counter);
}

function append_waiting_number(string $notiz, string $waiting_number): string {
    $label = 'Wartennummer: ' . $waiting_number;
    if (trim($notiz) === '') {
        return $label;
    }

    return $notiz . "\n\n" . $label;
}

