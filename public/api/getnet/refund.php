<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar config
    $configPath = __DIR__ . '/../../../config/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('No se encontró el archivo de configuración.');
    }
    $config = require $configPath;

    $client_id     = $config['GETNET_CLIENT_ID'] ?? null;
    $client_secret = $config['GETNET_CLIENT_SECRET'] ?? null;

    if (!$client_id || !$client_secret) {
        throw new Exception('Faltan credenciales de Getnet.');
    }

    // Parámetros recibidos (puedes pasarlos por POST o GET)
    $payment_id = $_POST['payment_id'] ?? $_GET['payment_id'] ?? null;
    $amount     = $_POST['amount'] ?? $_GET['amount'] ?? null;

    if (!$payment_id || !$amount) {
        throw new Exception('Faltan parámetros: payment_id y amount.');
    }

    // 1. Obtener token de acceso
    $urlToken = 'https://auth.use1.preprod.getnet.com.br/auth/oauth/v2/token';
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

    // 2. Ejecutar devolución
    $urlRefund = "https://api-mtls.preprod.getnet.com.br/v1/payments/{$payment_id}/refunds";
    $refundPayload = json_encode([
        "amount" => (int)$amount,
        "currency" => "ARS",
        "reason" => "Devolución prueba D+1"
    ]);

    $ch = curl_init($urlRefund);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $refundPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$access_token}"
    ]);

    $refundResponse = curl_exec($ch);
    if ($refundResponse === false) {
        throw new Exception('Error en refund: ' . curl_error($ch));
    }
    curl_close($ch);

    echo $refundResponse;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => "Error refund", "message" => $e->getMessage()]);
}
