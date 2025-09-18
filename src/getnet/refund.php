<?php
declare(strict_types=1);
ini_set('display_errors', 0); // ocultar warnings/notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // 📌 Cargar variables de entorno
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $urlBaseRefund = $_ENV['URL_REFUND_CANCEL'] ?? null;
    if (!$urlBaseRefund) {
        throw new Exception("Falta URL_REFUND_CANCEL en .env");
    }

    // 📌 Parámetros
    $payment_id = $_POST['payment_id'] ?? $_GET['payment_id'] ?? null;
    $amount     = $_POST['amount'] ?? $_GET['amount'] ?? null;

    if (!$payment_id || !$amount) {
        throw new Exception('Faltan parámetros: payment_id y amount.');
    }

    // 📌 1. Obtener token desde token.php local
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

    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception("No se pudo obtener access_token");
    }
    $access_token = $tokenData['access_token'];

    // 📌 2. Refund
    $urlRefund = rtrim($urlBaseRefund, '/') . "/{$payment_id}/refund";
    $refundPayload = [
        "amount" => (int)$amount,
        "reason" => $_POST['reason'] ?? $_GET['reason'] ?? "Refund manual"
    ];

    $ch = curl_init($urlRefund);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($refundPayload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer {$access_token}"
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);


    $refundResponse = curl_exec($ch);
    if ($refundResponse === false) {
        throw new Exception('Error en refund: ' . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($code);
    echo $refundResponse;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error"   => "Error refund",
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
