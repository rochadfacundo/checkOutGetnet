<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar config
    $configPath = '/home/ubuntu/motorasistant/config/config.php';

    if (!file_exists($configPath)) {
        throw new Exception('No se encontró el archivo de configuración.');
    }

    $config = require $configPath;

    $client_id     = $config['GETNET_CLIENT_ID'] ?? null;
    $client_secret = $config['GETNET_CLIENT_SECRET'] ?? null;

    if (!$client_id || !$client_secret) {
        throw new Exception('Faltan credenciales de Getnet.');
    }

    // Preparar request

    $url = 'https://api.pre.globalgetnet.com/authentication/oauth2/access_token';

    $payload = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => $client_id,
        'client_secret' => $client_secret
    ]);

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

    // Validar si es JSON válido
    $json = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Respuesta inválida (no es JSON): ' . json_last_error_msg());
    }

    http_response_code($code);
    echo json_encode($json);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error token',
        'message' => $e->getMessage()
    ]);
}
