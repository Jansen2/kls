<?php
/**
 * Konfiguration für AOK KLS Empfangsanwendung
 */

// ===== ERROR HANDLING =====
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ===== MAIL CONFIGURATION =====
define('MAIL_ENABLED', true);
define('MAIL_FROM', 'noreply@aok-niedersachsen.de');
define('MAIL_FROM_NAME', 'AOK Niedersachsen KLS');
define('MAIL_TO', 'kls@aok-niedersachsen.de');
define('MAIL_SUBJECT_PREFIX', 'AOK KLS Warteliste');

// SMTP-Konfiguration (optional, Fallback auf mail() wenn deaktiviert)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // 'tls', 'ssl', oder ''
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

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

// ===== VALIDATION CONFIGURATION =====
define('VALIDATION_MIN_FIRSTNAME_LENGTH', 2);
define('VALIDATION_MIN_LASTNAME_LENGTH', 2);
define('VALIDATION_MAX_TEXT_LENGTH', 5000);

// ===== EMAIL FORMAT =====
define('EMAIL_FORMAT', 'multipart'); // 'html', 'plain', 'multipart'
