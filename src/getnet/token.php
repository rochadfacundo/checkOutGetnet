<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $client_id     = $_ENV['CLIENT_ID']     ?? null;
    $client_secret = $_ENV['CLIENT_SECRET'] ?? null;
    $url           = $_ENV['URL_TOKEN']     ?? null;

    if (!$client_id || !$client_secret || !$url) {
        throw new Exception('Faltan variables de entorno.');
    }

    // Payload para Getnet
    $payload = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => $client_id,
        'client_secret' => $client_secret
    ]);

    // Request con cURL
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT        => 15
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Error en CURL: ' . $err);
    }

    // Validar JSON
    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Respuesta inválida (no es JSON): ' . json_last_error_msg());
    }

    http_response_code($code);
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error token',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
