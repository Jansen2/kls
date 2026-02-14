<?php
/**
 * Konfiguration für AOK KLS Empfangsanwendung
 */

// ===== ERROR HANDLING =====
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ===== QUEUE CONFIGURATION =====
define('QUEUE_COUNTER_FILE', __DIR__ . '/data/queue_counter.json');

// ===== LOGGING CONFIGURATION =====
define('LOG_ENABLED', true);
define('LOG_DIR', __DIR__ . '/logs');
define('LOG_SUBMISSIONS', true);
define('LOG_ERRORS', true);
define('LOG_LEVEL', 'info'); // 'debug', 'info', 'warning', 'error'

// ===== SECURITY CONFIGURATION =====
define('RATE_LIMIT_ENABLED', false);
define('RATE_LIMIT_REQUESTS', 5);
define('RATE_LIMIT_WINDOW', 3600); // 1 Stunde in Sekunden
define('RATE_LIMIT_FILE', __DIR__ . '/data/rate_limits.json');
define('CSRF_TOKEN_LIFETIME', 3600);

// ===== SECURE EXPORT CONFIGURATION =====
define('DATA_EXPORT_ENABLED', true);
define('DATA_EXPORT_DIR', __DIR__ . '/data/exports');
define('DATA_EXPORT_TTL', 3600); // 1 Stunde in Sekunden
define('DATA_EXPORT_API_KEY', 'change-me');
define('DATA_EXPORT_BASE_URL', '');
define('DATA_EXPORT_ALLOW_POLL', true);
define('DATA_EXPORT_CONSUME_ON_READ', true);
define('DATA_EXPORT_ENCRYPTION_KEY', 'Wp8wWco164fiVqJOZ/esHcC2HFiqV16aibKn61l0W7c='); // Base64-encoded 32-byte key

// ===== VALIDATION CONFIGURATION =====
define('VALIDATION_MIN_FIRSTNAME_LENGTH', 2);
define('VALIDATION_MIN_LASTNAME_LENGTH', 2);
define('VALIDATION_MAX_TEXT_LENGTH', 5000);

