<?php
declare(strict_types=1);
ini_set('display_errors', 0); // ocultar warnings/notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

header('Content-Type: application/json; charset=utf-8');

// 🔹 Guardar logs en var/
function log_debug($msg): void {
    $logFile = __DIR__ . "/../../var/intent_error.log";
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $msg . PHP_EOL, FILE_APPEND);
}

try {
    // 1) Cargar variables de entorno
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $urlIntent = $_ENV['URL_INTENT'] ?? null;
    if (!$urlIntent) {
        throw new Exception("Falta URL_INTENT en .env");
    }

    // 2) Leer body JSON
    $raw = file_get_contents('php://input');
    log_debug("RAW body: " . $raw);

    log_debug("Respuesta cruda Getnet: " . $res);

    $body = json_decode($raw, true) ?: [];

    $amount  = $body['amount']  ?? null;
    $orderId = $body['orderId'] ?? null;
    $customer = $body['customer'] ?? [];

    if ($amount === null || $orderId === null) {
        throw new Exception("Faltan amount / orderId");
    }

    // 3) Obtener token desde token.php local
    $tokenEndpoint = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/getnet/public/getnet/token.php";
    $ch = curl_init($tokenEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception("Error cURL token: " . curl_error($ch));
    }
    curl_close($ch);

    $tokenJson = json_decode($response, true);
    if (!isset($tokenJson['access_token'])) {
        throw new Exception("No se pudo obtener access_token");
    }
    $accessToken = $tokenJson['access_token'];

    // 4) Crear intent
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
                "value"    => (int)$amount,
                "quantity" => 1
            ]
        ],
        "customer" => [
            "customer_id"     => "123",
            "first_name"      => $customer['first_name'] ?? "N/A",
            "last_name"       => $customer['last_name'] ?? "N/A",
            "name"            => trim(($customer['first_name'] ?? "") . " " . ($customer['last_name'] ?? "")),
            "email"           => $customer['email'] ?? "N/A",
            "document_type"   => $customer['document_type'] ?? "dni",
            "document_number" => $customer['document_number'] ?? "0",
            "phone_number"    => $customer['telefono'] ?? null
        ]
    ];

    $ch = curl_init($urlIntent);
    curl_setopt_array($ch, [
        CURLOPT_POST        => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER  => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_POSTFIELDS  => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT     => 30,
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
