<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function log_debug($msg) {
    file_put_contents(__DIR__ . "/intent_error.log", date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json; charset=utf-8');

try {
    // 1) Leer body JSON
    $raw = file_get_contents('php://input');
    log_debug("RAW body: " . $raw);
    $body = json_decode($raw, true) ?: [];

    $amount  = $body['amount']  ?? null;
    $orderId = $body['orderId'] ?? null;

    if ($amount === null || $orderId === null) {
        throw new Exception("Faltan amount / orderId");
    }

    // 2) Obtener token
    $tokenEndpoint = 'http://3.149.136.15:8080/api/getnet/token.php';
    $ch = curl_init($tokenEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception("Error cURL token: " . curl_error($ch));
    }
    curl_close($ch);

    log_debug("Token response: " . $response);
    $tokenJson = json_decode($response, true);
    if (!isset($tokenJson['access_token'])) {
        throw new Exception("No se pudo obtener access_token");
    }
    $accessToken = $tokenJson['access_token'];

    // 3) Crear intent con todos los campos requeridos
    $url = 'https://api.pre.globalgetnet.com/digital-checkout/v1/payment-intent';
    $payload = [
        "payment" => [
            "amount"   => (int)$amount,
            "currency" => "ARS",
            "brand"    => "VISA"
        ],
        "order" => [
            "id"          => (string)$orderId,
            "description" => "Compra en Averia Motor SRL"
        ],
        "product" => [
            [
                "code"     => "001",
                "name"     => "Seguro de Auto",
                "title"    => "Póliza básica",
                "value"    => (int)$amount,  // usamos el mismo monto
                "quantity" => 1
            ]
        ],
        "customer" => [
            "customer_id"     => "123",
            "first_name"      => "Juan",
            "last_name"       => "Pérez",
            "name"            => "Juan Pérez",   // campo combinado
            "email"           => "juan.perez@example.com",
            "document_type"   => "dni",          // válido: rfc, cpf, cnpj, dni, rut
            "document_number" => "12345678"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        throw new Exception("Error cURL intent: " . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_debug("Intent response ($code): " . $res);
    http_response_code($code);
    echo $res;

} catch (Exception $e) {
    log_debug("❌ EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
