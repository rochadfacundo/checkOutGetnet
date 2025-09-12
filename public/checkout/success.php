<?php
declare(strict_types=1);
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pago Exitoso</title>
  <style>
    body {
      font-family: sans-serif;
      background-color: #e0ffe0;
      color: #006600;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
      text-align: center;
    }
    code {
      background: #fff;
      padding: 6px 12px;
      border: 1px solid #ccc;
      margin-top: 15px;
      font-size: 1.1em;
      display: inline-block;
    }
  </style>
</head>
<body>
  <h1>✅ ¡Pago realizado con éxito!</h1>
  <p>Gracias por tu compra. Recibirás un correo con los detalles de la transacción.</p>

  <?php if ($id): ?>
    <p><strong>ID de transacción:</strong></p>
    <code><?= htmlspecialchars($id) ?></code>
  <?php else: ?>
    <p style="color:red;">No se recibió el ID de la transacción.</p>
  <?php endif; ?>
</body>
</html>
