<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$form_data = [
    'partnernummer' => $_POST['partnernummer'] ?? '',
    'anrede' => $_POST['anrede'] ?? '',
    'vorname' => $_POST['vorname'] ?? '',
    'nachname' => $_POST['nachname'] ?? '',
    'geburtsdatum' => $_POST['geburtsdatum'] ?? '',
    'ansprechpartner' => $_POST['ansprechpartner'] ?? '',
    'notiz' => $_POST['notiz'] ?? '',
    'thema' => $_POST['thema'] ?? '',
    'wunschberater' => $_POST['wunschberater'] ?? '',
    'termin_tag' => $_POST['termin_tag'] ?? '',
    'uhrzeit' => $_POST['uhrzeit'] ?? '',
];

$waiting_number = get_waiting_number();
$form_data['notiz'] = append_waiting_number($form_data['notiz'], $waiting_number);

$client_ip = get_client_ip();
$email_html = generate_email_html($form_data, $client_ip);
$email_text = generate_email_text($form_data, $client_ip);

$email_sent = false;
if (EMAIL_SEND_ENABLED && EMAIL_TO !== '' && EMAIL_FROM !== '') {
    $subject = EMAIL_SUBJECT_PREFIX . ' #' . $waiting_number;
    $email_sent = send_email_plain(EMAIL_TO, $subject, $email_text, EMAIL_FROM);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Formular erfolgreich verarbeitet',
    'waiting_number' => $waiting_number,
    'email_html' => $email_html,
    'email_sent' => $email_sent,
    'client_ip' => $client_ip,
    'timestamp' => date('d.m.Y H:i:s')
]);
