<?php
$config = require __DIR__ . '/../../config/config.php';

// Autenticación esperada desde el portal de Getnet
$expected_user = $config['GETNET_WEBHOOK_USER'] ?? '';
$expected_pass = $config['GETNET_WEBHOOK_PASS'] ?? '';


$user = $_SERVER['PHP_AUTH_USER'] ?? '';
$pass = $_SERVER['PHP_AUTH_PW'] ?? '';

if ($user !== $expected_user || $pass !== $expected_pass) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Leer el cuerpo del webhook
$input = file_get_contents('php://input');

// Guardar en log para depuración
file_put_contents(__DIR__ . '/getnet_webhook_log.txt', date('Y-m-d H:i:s') . " - " . $input . PHP_EOL, FILE_APPEND);

// Responder a Getnet
http_response_code(200);
echo json_encode(["status" => "received"]);
