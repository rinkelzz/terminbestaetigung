<?php
/**
 * AutoNotify webhook endpoint.
 *
 * Expects JSON payload with keys:
 * - name: Kunde Name
 * - datum: Termin- oder Bestelldatum
 * - phone: WhatsApp Telefonnummer im internationalen Format
 * - template (optional): Nachrichtenvorlage mit Platzhaltern {{name}} und {{datum}}
 * - extra (optional): Assoziatives Array für weitere Platzhalter
 */

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

$required = ['name', 'datum', 'phone'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Missing field: {$field}"]);
        exit;
    }
}

$template = $input['template'] ?? 'Hallo {{name}}, dein Termin am {{datum}} wurde bestätigt!';
$placeholders = array_merge([
    'name' => $input['name'],
    'datum' => $input['datum'],
], isset($input['extra']) && is_array($input['extra']) ? $input['extra'] : []);

$msg = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($placeholders) {
    $key = $matches[1];
    return $placeholders[$key] ?? $matches[0];
}, $template);

$query = http_build_query([
    'msg' => $msg,
    'to' => preg_replace('/\D+/', '', $input['phone']),
]);

$nodeRedUrl = getenv('NODERED_URL') ?: 'http://localhost:1880/whatsapp';

$response = @file_get_contents($nodeRedUrl . '?' . $query);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Failed to contact Node-RED endpoint']);
    exit;
}

echo json_encode(['status' => 'ok']);
