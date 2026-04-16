<?php
// order-success.php - страница успешного заказа
require_once 'config.php';

$order_number = $_SESSION['order_success'] ?? '';
unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .success-container { max-width: 600px; margin: 100px auto; text-align: center; }
        .success-icon { font-size: 5rem; color: #28a745; }
        .order-number { font-size: 24px; font-weight: bold; color: #3D3D3D; }
    </style>
</head>
<body>
    <div class="container success-container">
        <div class="success-icon"><i class="bi bi-check-circle-fill"></i></div>
        <h2 class="mt-3">Спасибо за заказ!</h2>
        <p class="lead">Ваш заказ успешно оформлен</p>
        <p>Номер заказа: <span class="order-number"><?= htmlspecialchars($order_number) ?></span></p>
        <p class="text-muted">Мы отправили подтверждение на ваш email</p>
        <a href="index.php" class="btn btn-primary btn-lg mt-3">
            <i class="bi bi-house"></i> Вернуться на главную
        </a>
    </div>
</body>
</html>