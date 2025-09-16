<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// --- Credenciales esperadas (hardcode) ---
$expected_user = ''; //completar
$expected_pass = '';

// --- Autenticación ---
$user = null;
$pass = null;

if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6));
        if ($decoded !== false) {
            [$user, $pass] = explode(':', $decoded, 2);
        }
    }
}

if ($user !== $expected_user || $pass !== $expected_pass) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// --- Leer cuerpo del webhook ---
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// --- Directorio de almacenamiento ---
$storageDir = __DIR__ . '/../var';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

if ($data) {
    // Archivo JSON acumulativo
    $jsonFile = $storageDir . '/pagos_getnet.json';
    $pagos = [];

    if (file_exists($jsonFile)) {
        $pagos = json_decode(file_get_contents($jsonFile), true) ?: [];
    }

    // Extraer datos clave
    $registro = [
        "fecha"             => date('Y-m-d H:i:s'),
        "payment_intent_id" => $data['payment_intent_id'] ?? null,
        "payment_id"        => $data['payment']['result']['payment_id'] ?? null,
        "status"            => $data['payment']['result']['status'] ?? null,
        "amount"            => $data['payment']['amount'] ?? null,
        "currency"          => $data['payment']['currency'] ?? null,
        "brand"             => $data['payment']['brand'] ?? null,
        "customer"          => $data['customer']['name'] ?? null,
        "document"          => ($data['customer']['document_type'] ?? '') . " " . ($data['customer']['document_number'] ?? ''),
        "raw"               => $data
    ];

    // Agregar al array
    $pagos[] = $registro;

    // Guardar JSON formateado
    file_put_contents($jsonFile, json_encode($pagos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Guardar log histórico simple
    file_put_contents(
        $storageDir . '/webhook_debug.log',
        date('Y-m-d H:i:s') . " - Guardado pago intent={$registro['payment_intent_id']} status={$registro['status']} payment_id={$registro['payment_id']}" . PHP_EOL,
        FILE_APPEND
    );
}

// --- Responder siempre ---
http_response_code(200);
echo json_encode(["status" => "received"]);
