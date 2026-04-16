<?php
// checkout.php - оформление заказа
require_once 'config.php';
require_once 'auth.php';

$db = getDB();

if (!isset($_SESSION['cart_session'])) {
    $_SESSION['cart_session'] = session_id();
}
$session_id = $_SESSION['cart_session'];
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Получаем товары из корзины
function getCartItems($db, $user_id, $session_id) {
    if ($user_id) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.article, p.model 
            FROM admindoor1_cart c
            JOIN " . TABLE_PRODUCTS . " p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.article, p.model 
            FROM admindoor1_cart c
            JOIN " . TABLE_PRODUCTS . " p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$session_id]);
    }
    return $stmt->fetchAll();
}

$cart_items = getCartItems($db, $user_id, $session_id);
$cart_total = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cart_items));

if (empty($cart_items)) {
    redirect('cart.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $phone = clean($_POST['phone']);
    $address = clean($_POST['address']);
    $delivery = clean($_POST['delivery']);
    $payment = clean($_POST['payment']);
    $comment = clean($_POST['comment']);

    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'Заполните обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email';
    } else {
        try {
            $db->beginTransaction();

            // Генерация номера заказа
            $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            // Создание заказа
            $stmt = $db->prepare("
                INSERT INTO admindoor1_orders 
                (order_number, user_id, session_id, customer_name, customer_email, customer_phone, 
                 customer_address, delivery_method, payment_method, subtotal, total, status, payment_status, comment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', 'pending', ?)
            ");
            $stmt->execute([
                $order_number,
                $user_id,
                $session_id,
                $name,
                $email,
                $phone,
                $address,
                $delivery,
                $payment,
                $cart_total,
                $cart_total,
                $comment
            ]);
            
            $order_id = $db->lastInsertId();

            // Добавление товаров в заказ
            $stmt = $db->prepare("
                INSERT INTO admindoor1_order_items 
                (order_id, product_id, product_name, product_article, quantity, price, total, selected_color, selected_size)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $total = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['name'],
                    $item['article'],
                    $item['quantity'],
                    $item['price'],
                    $total,
                    $item['selected_color'],
                    $item['selected_size']
                ]);
            }

            // Очистка корзины
            if ($user_id) {
                $db->prepare("DELETE FROM admindoor1_cart WHERE user_id = ?")->execute([$user_id]);
            } else {
                $db->prepare("DELETE FROM admindoor1_cart WHERE session_id = ?")->execute([$session_id]);
            }

            // Логирование
            $db->prepare("
                INSERT INTO admindoor1_logs (user_id, action, details, ip_address) 
                VALUES (?, 'order_created', ?, ?)
            ")->execute([
                $user_id,
                "Заказ #$order_number на сумму $cart_total руб.",
                $_SERVER['REMOTE_ADDR']
            ]);

            $db->commit();

            // Отправка email
            sendOrderEmail($order_id, $db);

            $_SESSION['order_success'] = $order_number;
            redirect('order-success.php');

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}

function sendOrderEmail($order_id, $db) {
    // Здесь можно добавить отправку email
    // Для простоты пропускаем
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .header { background: #3D3D3D; color: white; padding: 15px 0; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: bold; text-decoration: none; color: white; }
        .main-container { min-height: calc(100vh - 150px); padding: 30px 0; }
        .checkout-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .checkout-form { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .order-summary { background: #f8f9fa; border-radius: 10px; padding: 20px; }
        .order-item { border-bottom: 1px solid #dee2e6; padding: 10px 0; }
        .footer { background: #3D3D3D; color: white; padding: 30px 0; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo"><i class="bi bi-door-closed"></i> <?= SITE_NAME ?></a>
        </div>
    </header>

    <div class="main-container">
        <div class="checkout-container">
            <h2 class="mb-4"><i class="bi bi-credit-card"></i> Оформление заказа</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="checkout-form">
                        <form method="POST" action="">
                            <h4>1. Контактная информация</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Имя *</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_name'] ?? '') : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Телефон *</label>
                                    <input type="tel" name="phone" class="form-control" required 
                                           placeholder="+7 (999) 123-45-67">
                                </div>
                            </div>

                            <h4>2. Доставка</h4>
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="delivery" id="delivery1" value="pickup" checked>
                                    <label class="form-check-label" for="delivery1">
                                        Самовывоз (бесплатно)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="delivery" id="delivery2" value="courier">
                                    <label class="form-check-label" for="delivery2">
                                        Доставка курьером (500 ₽)
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Адрес доставки</label>
                                    <textarea name="address" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                            <h4>3. Оплата</h4>
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment" id="payment1" value="cash" checked>
                                    <label class="form-check-label" for="payment1">
                                        Наличными при получении
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment" id="payment2" value="card">
                                    <label class="form-check-label" for="payment2">
                                        Банковской картой онлайн
                                    </label>
                                </div>
                            </div>

                            <h4>4. Комментарий</h4>
                            <div class="mb-4">
                                <textarea name="comment" class="form-control" rows="3" 
                                          placeholder="Дополнительная информация к заказу"></textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-circle"></i> Подтвердить заказ
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="order-summary">
                        <h4>Ваш заказ</h4>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        <small class="d-block text-muted"><?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', ' ') ?> ₽</small>
                                        <?php if ($item['selected_color']): ?>
                                            <small>Цвет: <?= htmlspecialchars($item['selected_color']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['selected_size']): ?>
                                            <small>Размер: <?= htmlspecialchars($item['selected_size']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <strong><?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Итого:</span>
                            <span><?= number_format($cart_total, 0, ',', ' ') ?> ₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p>&copy; 2024 <?= SITE_NAME ?>. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>