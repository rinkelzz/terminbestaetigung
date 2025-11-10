<?php
/**
 * AutoNotify webhook endpoint.
 *
 * Erwartet JSON Payload mit Feldern:
 * - name: Kundenname
 * - datum: Termin- oder Kaufdatum (frei formatiert)
 * - phone: WhatsApp Telefonnummer im internationalen Format
 * - template (optional): Nachrichtenvorlage mit Platzhaltern {{name}}, {{datum}} etc.
 * - extra (optional): Assoziatives Array für weitere Platzhalter
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$shopId = isset($_GET['shopid']) ? trim((string) $_GET['shopid']) : null;
$allowedShopIds = getenv('ALLOWED_SHOP_IDS');
if ($allowedShopIds !== false && $allowedShopIds !== '') {
    $allowed = array_filter(array_map('trim', explode(',', $allowedShopIds)));
    if ($allowed && (!$shopId || !in_array($shopId, $allowed, true))) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Shop ID not allowed']);
        exit;
    }
}

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

$message = preg_replace_callback('/{{\s*(\w+)\s*}}/', static function ($matches) use ($placeholders) {
    $key = $matches[1];
    return $placeholders[$key] ?? $matches[0];
}, $template);

$recipient = preg_replace('/\D+/', '', (string) $input['phone']);

if ($recipient === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Phone number must contain digits']);
    exit;
}

$accessToken = getenv('WHATSAPP_ACCESS_TOKEN');
$phoneId = getenv('WHATSAPP_PHONE_ID');
$apiVersion = getenv('WHATSAPP_API_VERSION') ?: 'v18.0';

if (!$accessToken || !$phoneId) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'WhatsApp credentials are not configured. Set WHATSAPP_ACCESS_TOKEN and WHATSAPP_PHONE_ID.',
    ]);
    exit;
}

$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $recipient,
    'type' => 'text',
    'text' => [
        'preview_url' => false,
        'body' => $message,
    ],
];

$endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', rawurlencode($apiVersion), rawurlencode($phoneId));

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 15,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . $curlError]);
    exit;
}

$decoded = json_decode($responseBody, true);

if ($statusCode >= 400) {
    http_response_code(502);
    $errorMessage = $decoded['error']['message'] ?? 'Unknown error from WhatsApp API';
    echo json_encode(['status' => 'error', 'message' => $errorMessage, 'details' => $decoded]);
    exit;
}

echo json_encode(['status' => 'ok', 'response' => $decoded]);
