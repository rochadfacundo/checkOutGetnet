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

    $urlBaseCancel = $_ENV['URL_REFUND_CANCEL'] ?? null;
    if (!$urlBaseCancel) {
        throw new Exception("Falta URL_REFUND_CANCEL en .env");
    }

    // 📌 Parámetro recibido
    $payment_id = $_POST['payment_id'] ?? $_GET['payment_id'] ?? null;
    if (!$payment_id) {
        throw new Exception('Falta parámetro: payment_id');
    }

    // 📌 Reason opcional
    $reason = $_POST['reason'] ?? $_GET['reason'] ?? "Cancelación manual";

    // 📌 1. Obtener token desde token.php local
    $tokenEndpoint = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/getnet/token.php";
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

    // 📌 2. Ejecutar cancelación
    $urlCancel = rtrim($urlBaseCancel, '/') . "/{$payment_id}/cancellation";
    $cancelPayload = json_encode([
        "reason" => $reason
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($urlCancel);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $cancelPayload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer {$access_token}"
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $cancelResponse = curl_exec($ch);
    if ($cancelResponse === false) {
        throw new Exception('Error en cancelación: ' . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($code);
    echo $cancelResponse;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error"   => "Error cancel",
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
