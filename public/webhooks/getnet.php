<?php
// Config (ruta absoluta para evitar problemas con ../)
$config = require '/home/ubuntu/motorasistant/config/config.php';

// --- Autenticación esperada ---
$expected_user = $config['GETNET_WEBHOOK_USER'] ?? '';
$expected_pass = $config['GETNET_WEBHOOK_PASS'] ?? '';

// Detectar credenciales de distintas formas (según servidor/proxy)
$user = null;
$pass = null;

if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // Ejemplo: "Basic bW90b3Jhc2lzdGFudDpNb3RvckFzaXN0YW50MjAyNw=="
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6));
        if ($decoded !== false) {
            [$user, $pass] = explode(':', $decoded, 2);
        }
    }
}

if ($user !== $expected_user || $pass !== $expected_pass) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// --- Leer el cuerpo del webhook ---
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// --- Logs fuera de public ---
$logDir = '/home/ubuntu/motorasistant/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

if ($data) {
    // Guardar último pago para debug
    $lastPaymentPath = $logDir . '/last_payment.json';
    file_put_contents($lastPaymentPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Log histórico
    $logLine = date('Y-m-d H:i:s') . " - " . $input . PHP_EOL;
    file_put_contents($logDir . '/getnet_webhook_log.txt', $logLine, FILE_APPEND);

    // Actualizar en DB remota (InfinityFree)
    if (isset($data['payment_intent_id'], $data['status'])) {
        $intentId  = $data['payment_intent_id'];
        $status    = $data['status'];
        $paymentId = $data['payment_id'] ?? null;

        try {
            $pdo = new PDO(
                "mysql:host=sql101.infinityfree.com;dbname=if0_39760207_motorasistant;charset=utf8",
                "if0_39760207",
                "Lobo7414",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("
                UPDATE pagosGetnet
                SET status = :status, payment_id = :payment_id
                WHERE payment_intent_id = :intent_id
            ");
            $stmt->execute([
                ':intent_id'  => $intentId,
                ':status'     => $status,
                ':payment_id' => $paymentId
            ]);

            $rowCount = $stmt->rowCount();
            error_log("UPDATE pagosGetnet rows=$rowCount para intent_id=$intentId");

        } catch (Exception $e) {
            error_log("❌ Error actualizando pago en DB remota: " . $e->getMessage());
        }
    }
}

// Responder a Getnet
http_response_code(200);
echo json_encode(["status" => "received"]);
