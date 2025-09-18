<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/Database.php';

class PagoGetnetService {

    /**
     * Guarda el pago en la base de datos usando el procedure insertarPagoGetnet
     */
    public static function insertarPago(array $data): void {
        $pdo = Database::getConnection();

        $sql = "CALL insertarPagoGetnet(
            :order_id, :payment_intent_id, :payment_id, :status,
            :monto, :currency, :brand, :customer_name, :document, :raw
        )";

        try {
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':order_id'          => $data['order_id'] ?? null,
                ':payment_intent_id' => $data['payment_intent_id'] ?? null,
                ':payment_id'        => $data['payment_id'] ?? null,
                ':status'            => $data['status'] ?? null,
                ':monto'             => $data['amount'] ?? null,
                ':currency'          => $data['currency'] ?? null,
                ':brand'             => $data['brand'] ?? null,
                ':customer_name'     => $data['customer'] ?? null,
                ':document'          => $data['document'] ?? null,
                ':raw'               => json_encode($data['raw'], JSON_UNESCAPED_UNICODE)
            ]);

            self::logDebug("✅ Pago insertado OK: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        } catch (\PDOException $e) {
            self::logDebug("❌ Error al insertar pago: " . $e->getMessage() . 
                           " | Datos: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    /**
     * Log simple en logs/getnet.log
     */
    private static function logDebug(string $msg): void {
        $dir = __DIR__ . '/../../logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . '/getnet.log';
        $line = date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND);
    }
}
