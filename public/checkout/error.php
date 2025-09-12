<?php
declare(strict_types=1);
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Error en el Pago</title>
  <style>
    body {
      font-family: sans-serif;
      background-color: #ffe0e0;
      color: #990000;
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
  <h1>❌ Hubo un error en el pago</h1>
  <p>Por favor, volvé a intentarlo o contactá con soporte si el problema persiste.</p>

  <?php if ($id): ?>
    <p><strong>ID asociado:</strong></p>
    <code><?= htmlspecialchars($id) ?></code>
  <?php endif; ?>
</body>
</html>
