<?php
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

define('EMAIL_SEND_ENABLED', false);
define('EMAIL_TO', '');
define('EMAIL_FROM', '');
define('EMAIL_SUBJECT_PREFIX', 'AOK KLS Warteliste');
define('QUEUE_COUNTER_FILE', __DIR__ . '/../data/queue_counter.json');

function format_waiting_number(int $number): string {
    return str_pad((string)$number, 3, '0', STR_PAD_LEFT);
}

function get_waiting_number(string $counter_file = QUEUE_COUNTER_FILE): string {
    $today = date('Y-m-d');
    $counter = 1;

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

function generate_email_text(array $form_data, string $client_ip): string {
    $timestamp = date('d.m.Y H:i:s');

    $lines = [
        'Einchecken.-Kundencenter',
        'AOK Niedersachsen - Wartelisten-Anmeldung',
        '',
        'PERSOENLICHE DATEN',
        'Krankenkassenkartennummer: ' . ($form_data['partnernummer'] ?? ''),
        'Anrede: ' . ($form_data['anrede'] ?? ''),
        'Vorname: ' . ($form_data['vorname'] ?? ''),
        'Nachname: ' . ($form_data['nachname'] ?? ''),
        'Geburtsdatum: ' . ($form_data['geburtsdatum'] ?? ''),
        'Ansprechpartner/Betreuer: ' . ($form_data['ansprechpartner'] ?? ''),
        '',
        'NOTIZ',
        ($form_data['notiz'] ?? ''),
        '',
        'KONTAKTDATEN',
        'Thema: ' . ($form_data['thema'] ?? ''),
        'Wunschberater: ' . ($form_data['wunschberater'] ?? ''),
        '',
        'TERMINDATEN',
        'Termin Tag: ' . ($form_data['termin_tag'] ?? ''),
        'Uhrzeit: ' . ($form_data['uhrzeit'] ?? ''),
        '',
        'ZUSAETZLICHE INFORMATIONEN',
        'Absender IP-Adresse: ' . $client_ip,
        'Zeitstempel: ' . $timestamp
    ];

    return implode("\n", $lines);
}

function send_email_plain(string $to, string $subject, string $body, string $from): bool {
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/plain; charset=UTF-8'
    ];

    return mail($to, $subject, $body, implode("\r\n", $headers));
}

function generate_email_html(array $form_data, string $client_ip): string {
    $timestamp = date('d.m.Y H:i:s');

    $partnernummer = h($form_data['partnernummer'] ?? '');
    $anrede = h($form_data['anrede'] ?? '');
    $vorname = h($form_data['vorname'] ?? '');
    $nachname = h($form_data['nachname'] ?? '');
    $geburtsdatum = h($form_data['geburtsdatum'] ?? '');
    $ansprechpartner = h($form_data['ansprechpartner'] ?? '');
    $notiz = h($form_data['notiz'] ?? '');
    $thema = h($form_data['thema'] ?? '');
    $wunschberater = h($form_data['wunschberater'] ?? '');
    $termin_tag = h($form_data['termin_tag'] ?? '');
    $uhrzeit = h($form_data['uhrzeit'] ?? '');

    $client_ip_safe = h($client_ip);

    $email_html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .email-header {
            background-color: #009B50;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 20px;
        }
        .section {
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-weight: bold;
            color: #009B50;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .form-field {
            margin-bottom: 8px;
            font-size: 13px;
        }
        .form-field label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
            color: #333;
        }
        .form-field-value {
            color: #666;
            word-wrap: break-word;
        }
        .notiz {
            background-color: #f9f9f9;
            border-left: 4px solid #009B50;
            padding: 10px;
            margin-top: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .contact-info {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            font-size: 12px;
            color: #666;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 15px;
            font-size: 11px;
            color: #999;
            text-align: center;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Einchecken.-Kundencenter</h1>
            <p>AOK Niedersachsen - Wartelisten-Anmeldung</p>
        </div>

        <div class="email-body">
            <div class="section">
                <div class="section-title">📋 PERSÖNLICHE DATEN</div>
                <div class="form-field">
                    <label>Krankenkassenkartennummer:</label>
                    <span class="form-field-value">{$partnernummer}</span>
                </div>
                <div class="form-field">
                    <label>Anrede:</label>
                    <span class="form-field-value">{$anrede}</span>
                </div>
                <div class="form-field">
                    <label>Vorname:</label>
                    <span class="form-field-value">{$vorname}</span>
                </div>
                <div class="form-field">
                    <label>Nachname:</label>
                    <span class="form-field-value">{$nachname}</span>
                </div>
                <div class="form-field">
                    <label>Geburtsdatum:</label>
                    <span class="form-field-value">{$geburtsdatum}</span>
                </div>
                <div class="form-field">
                    <label>Ansprechpartner/Betreuer:</label>
                    <span class="form-field-value">{$ansprechpartner}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">📝 NOTIZ</div>
                <div class="notiz">{$notiz}</div>
            </div>

            <div class="section">
                <div class="section-title">📞 KONTAKTDATEN</div>
                <div class="form-field">
                    <label>Thema:</label>
                    <span class="form-field-value">{$thema}</span>
                </div>
                <div class="form-field">
                    <label>Wunschberater:</label>
                    <span class="form-field-value">{$wunschberater}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">📅 TERMINDATEN</div>
                <div class="form-field">
                    <label>Termin Tag:</label>
                    <span class="form-field-value">{$termin_tag}</span>
                </div>
                <div class="form-field">
                    <label>Uhrzeit:</label>
                    <span class="form-field-value">{$uhrzeit}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">ℹ️ ZUSÄTZLICHE INFORMATIONEN</div>
                <div class="contact-info">
                    <div><strong>Absender IP-Adresse:</strong> {$client_ip_safe}</div>
                    <div><strong>Zeitstempel:</strong> {$timestamp}</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Diese E-Mail wurde automatisch generiert von der AOK Niedersachsen Empfangsanwendung.</p>
            <p>© AOK Niedersachsen - Alle Rechte vorbehalten</p>
        </div>
    </div>
</body>
</html>
HTML;

    return $email_html;
}
