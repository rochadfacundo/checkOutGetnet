<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
    // 📌 Cargar config
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

    // 📌 Parámetro recibido
    $payment_id = $_POST['payment_id'] ?? $_GET['payment_id'] ?? null;
    if (!$payment_id) {
        throw new Exception('Falta parámetro: payment_id');
    }

    // 📌 1. Obtener token de acceso
    $urlToken = 'https://api.pre.globalgetnet.com/authentication/oauth2/access_token';
    $payload = http_build_query([
        'scope' => 'oob',
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ]);

    $ch = curl_init($urlToken);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception('Error al obtener token: ' . curl_error($ch));
    }

    $tokenData = json_decode($response, true);
    curl_close($ch);

    if (!isset($tokenData['access_token'])) {
        throw new Exception('No se pudo obtener access_token.');
    }
    $access_token = $tokenData['access_token'];

    // 📌 2. Ejecutar cancelación
    $urlCancel = "https://api.pre.globalgetnet.com/digital-checkout/v1/payments/{$payment_id}/cancel";
    $cancelPayload = json_encode([
        "reason" => "Cancelación prueba <24h"
    ]);

    $ch = curl_init($urlCancel);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $cancelPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$access_token}"
    ]);

    $cancelResponse = curl_exec($ch);
    if ($cancelResponse === false) {
        throw new Exception('Error en cancelación: ' . curl_error($ch));
    }
    curl_close($ch);

    echo $cancelResponse;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error" => "Error cancel",
        "message" => $e->getMessage()
    ]);
}
