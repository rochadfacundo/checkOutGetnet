<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// 1) Leer body JSON
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$amount  = $body['amount']  ?? null;
$orderId = $body['orderId'] ?? null;

if ($amount === null || $orderId === null) {
  http_response_code(400);
  echo json_encode(['error' => 'Faltan amount / orderId']);
  exit;
}

// 2) Pedir token
$tokenEndpoint = 'https://motorasistant.infinityfree.me/api/getnet/token.php';
$tokenJson = json_decode(file_get_contents($tokenEndpoint), true);
if (!isset($tokenJson['access_token'])) {
  http_response_code(500);
  echo json_encode(['error' => 'No se pudo obtener access_token', 'detail' => $tokenJson]);
  exit;
}
$accessToken = $tokenJson['access_token'];

// 3) Crear intent
$url = 'https://api.pre.globalgetnet.com/digital-checkout/v1/payment-intent';

$payload = [
  "payment" => [
    "amount"   => $amount,
    "currency" => "ARS",
    "brand"    => "VISA"
  ],
  "order" => [
    "id"          => (string)$orderId,
    "description" => "Compra en Averia Motor SRL"
  ],
  "product" => [
    [
      "title"    => "Servicio Motor",
      "value"    => $amount,
      "quantity" => 1
    ]
  ],
  "customer" => [
    "customer_id"    => uniqid("cli_"), // o cualquier string único
    "first_name"     => "Carlos",
    "last_name"      => "Perez",
    "name"           => "Carlos Perez",
    "email"          => "carlosperez@test.com",
    "document_type"  => "dni",
    "document_number"=> "27999888",
    "billing_address" => [
      "street"      => "Av. Pasco",
      "number"      => "4000",
      "country"     => "AR",
      "postal_code" => "1844"
    ]
  ],
  "shipping" => [
    "first_name" => "Carlos",
    "last_name"  => "Perez",
    "address" => [
      "street"      => "Av. Pasco",
      "number"      => "4000",
      "postal_code" => "1844",
      "country"     => "AR"
    ]
  ],
  "checkout" => [
    "type" => "iframe"
  ],
  "metadata" => [
    "external_reference" => "ref-" . $orderId
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
  http_response_code(502);
  echo json_encode(['error' => 'curl_error', 'detail' => curl_error($ch)]);
  curl_close($ch);
  exit;
}
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $res;
