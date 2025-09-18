<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/PagoGetnetService.php';

use Dotenv\Dotenv;

try {
    // 📌 Cargar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $expected_user = $_ENV['GETNET_WEBHOOK_USER'] ?? null;
    $expected_pass = $_ENV['GETNET_WEBHOOK_PASS'] ?? null;

    // 📌 Método válido
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
    }

    // 📌 Auth básica
    $user = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

    if (!$user && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (stripos($auth, 'basic ') === 0) {
            [$user, $pass] = explode(':', base64_decode(substr($auth, 6)), 2);
        }
    }

    if ($user !== $expected_user || $pass !== $expected_pass) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    // 📌 Parsear webhook
    $input = file_get_contents('php://input');
    $data  = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    }

    // 📌 Mapear datos al formato esperado
    $registro = [
        "order_id"          => $data['order_id'] ?? null,
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

    // 📌 Guardar con el procedure
    PagoGetnetService::insertarPago($registro);

    http_response_code(200);
    echo json_encode(["status" => "received"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error"   => "Webhook error",
        "message" => $e->getMessage()
    ]);
}
