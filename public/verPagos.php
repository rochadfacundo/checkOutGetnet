<?php
// Conexión a tu DB local
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=motorasistant;charset=utf8",
        "root", // ⚠️ Ajusta usuario si no es root
        "",     // ⚠️ Ajusta contraseña si la definiste en XAMPP
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SELECT * FROM pagosGetnet ORDER BY fecha_creacion DESC LIMIT 50");
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("❌ Error al conectar DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pagos Getnet</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }
    h1 { color: #333; }
    table {
      border-collapse: collapse;
      width: 100%;
      background: #fff;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px 12px;
      text-align: left;
    }
    th {
      background: #333;
      color: #fff;
    }
    tr:nth-child(even) { background: #f9f9f9; }
    code { font-size: 0.8em; white-space: pre-wrap; }
  </style>
</head>
<body>
  <h1>📋 Últimos Pagos Getnet</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Order ID</th>
        <th>Payment Intent</th>
        <th>Payment ID</th>
        <th>Status</th>
        <th>Monto</th>
        <th>Fecha</th>
        <th>Raw</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($pagos): ?>
        <?php foreach ($pagos as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><?= htmlspecialchars($p['order_id']) ?></td>
            <td><code><?= htmlspecialchars($p['payment_intent_id']) ?></code></td>
            <td><?= htmlspecialchars($p['payment_id'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
            <td>$<?= number_format((float)$p['monto'], 2, ',', '.') ?></td>
            <td><?= $p['fecha_creacion'] ?></td>
            <td>
              <?php 
                $raw = $p['raw_response'] ?? '';
                echo $raw ? '<code>'.substr($raw, 0, 80).'...</code>' : '-';
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8">No hay pagos registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
