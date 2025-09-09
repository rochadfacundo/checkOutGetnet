<?php
declare(strict_types=1);
header('Access-Control-Allow-Origin: https://tu-front.netlify.app');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';

$CLIENT_ID     = $config['GETNET_CLIENT_ID'] ?? '';
$CLIENT_SECRET = $config['GETNET_CLIENT_SECRET'] ?? '';
if ($CLIENT_ID === '' || $CLIENT_SECRET === '') {
  http_response_code(500);
  echo json_encode(['error' => 'Faltan GETNET_CLIENT_ID / GETNET_CLIENT_SECRET']);
  exit;
}

$CACHE = __DIR__ . '/../../var/getnet_token.json';  // en htdocs/var/
$now = time();

// 1) devolver desde cache si sirve
if (file_exists($CACHE)) {
  $cached = json_decode(file_get_contents($CACHE), true);
  if (is_array($cached) && isset($cached['access_token'], $cached['expires_at']) && $cached['expires_at'] > $now + 60) {
    echo json_encode(['access_token' => $cached['access_token']]);
    exit;
  }
}

// 2) pedir token a pre
$url = 'https://api.pre.globalgetnet.com/authentication/oauth2/access_token';
$payload = http_build_query([
  'grant_type' => 'client_credentials',
  'client_id' => $CLIENT_ID,
  'client_secret' => $CLIENT_SECRET,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
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

if ($code < 200 || $code >= 300) {
  http_response_code($code);
  echo $res;
  exit;
}

$json = json_decode($res, true);
if (!isset($json['access_token'], $json['expires_in'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Respuesta inesperada de token', 'raw' => $json]);
  exit;
}

// Guardar cache (expires_at ~ ahora + expires_in)
$json['expires_at'] = $now + (int)$json['expires_in'];
file_put_contents($CACHE, json_encode($json));

echo json_encode(['access_token' => $json['access_token']]);
