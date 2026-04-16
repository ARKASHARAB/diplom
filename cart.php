<?php
// cart.php - страница корзины
require_once 'config.php';

$db = getDB();
$message = '';
$error = '';

// Получаем ID сессии
if (!isset($_SESSION['cart_session'])) {
    $_SESSION['cart_session'] = session_id();
}
$session_id = $_SESSION['cart_session'];
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Добавление в корзину
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        $selected_color = clean($_POST['color'] ?? '');
        $selected_size = clean($_POST['size'] ?? '');
        
        // Получаем информацию о товаре
        $stmt = $db->prepare("SELECT * FROM " . TABLE_PRODUCTS . " WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Проверяем, есть ли уже такой товар в корзине
            $stmt = $db->prepare("
                SELECT id, quantity FROM admindoor1_cart 
                WHERE (user_id = ? OR session_id = ?) 
                AND product_id = ? 
                AND selected_color = ? 
                AND selected_size = ?
            ");
            $stmt->execute([$user_id, $session_id, $product_id, $selected_color, $selected_size]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Обновляем количество
                $new_quantity = $existing['quantity'] + $quantity;
                $stmt = $db->prepare("UPDATE admindoor1_cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $existing['id']]);
                $message = 'Количество товара обновлено';
            } else {
                // Добавляем новый товар
                $stmt = $db->prepare("
                    INSERT INTO admindoor1_cart 
                    (user_id, session_id, product_id, quantity, selected_color, selected_size, price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $session_id,
                    $product_id,
                    $quantity,
                    $selected_color,
                    $selected_size,
                    $product['price']
                ]);
                $message = 'Товар добавлен в корзину';
            }
        }
        
        // Возвращаем JSON для AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $cart_count = getCartCount($db, $user_id, $session_id);
            echo json_encode(['success' => true, 'message' => $message, 'cart_count' => $cart_count]);
            exit;
        }
        
    } elseif ($action === 'update') {
        // Обновление количества
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $stmt = $db->prepare("UPDATE admindoor1_cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM admindoor1_cart WHERE id = ?");
            $stmt->execute([$cart_id]);
        }
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $cart_total = getCartTotal($db, $user_id, $session_id);
            $cart_count = getCartCount($db, $user_id, $session_id);
            echo json_encode(['success' => true, 'total' => $cart_total, 'cart_count' => $cart_count]);
            exit;
        }
        
    } elseif ($action === 'remove') {
        // Удаление из корзины
        $cart_id = intval($_POST['cart_id']);
        $stmt = $db->prepare("DELETE FROM admindoor1_cart WHERE id = ?");
        $stmt->execute([$cart_id]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $cart_total = getCartTotal($db, $user_id, $session_id);
            $cart_count = getCartCount($db, $user_id, $session_id);
            echo json_encode(['success' => true, 'total' => $cart_total, 'cart_count' => $cart_count]);
            exit;
        }
        
    } elseif ($action === 'clear') {
        // Очистка корзины
        if ($user_id) {
            $stmt = $db->prepare("DELETE FROM admindoor1_cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM admindoor1_cart WHERE session_id = ?");
            $stmt->execute([$session_id]);
        }
        redirect('cart.php');
    }
}

// Получение товаров в корзине
function getCartItems($db, $user_id, $session_id) {
    if ($user_id) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.picture, p.article, p.model 
            FROM admindoor1_cart c
            JOIN " . TABLE_PRODUCTS . " p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.picture, p.article, p.model 
            FROM admindoor1_cart c
            JOIN " . TABLE_PRODUCTS . " p ON c.product_id = p.id
            WHERE c.session_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$session_id]);
    }
    return $stmt->fetchAll();
}

function getCartCount($db, $user_id, $session_id) {
    if ($user_id) {
        $stmt = $db->prepare("SELECT SUM(quantity) FROM admindoor1_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?: 0;
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) FROM admindoor1_cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
        return $stmt->fetchColumn() ?: 0;
    }
}

function getCartTotal($db, $user_id, $session_id) {
    if ($user_id) {
        $stmt = $db->prepare("SELECT SUM(price * quantity) FROM admindoor1_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("SELECT SUM(price * quantity) FROM admindoor1_cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    return $stmt->fetchColumn() ?: 0;
}

$cart_items = getCartItems($db, $user_id, $session_id);
$cart_total = getCartTotal($db, $user_id, $session_id);
$cart_count = getCartCount($db, $user_id, $session_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .header { background: #3D3D3D; color: white; padding: 15px 0; position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; text-decoration: none; color: white; display: flex; align-items: center; gap: 10px; }
        .nav a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 12px; border-radius: 5px; }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .cart-link { position: relative; }
        .cart-count { background: #e74c3c; color: white; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; position: absolute; top: -8px; right: -8px; }
        .main-container { min-height: calc(100vh - 150px); padding: 30px 0; }
        .cart-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .cart-table { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .cart-item { border-bottom: 1px solid #dee2e6; padding: 15px 0; }
        .cart-item:last-child { border-bottom: none; }
        .product-image { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; }
        .quantity-input { width: 80px; text-align: center; }
        .cart-summary { background: #f8f9fa; border-radius: 10px; padding: 20px; }
        .btn-checkout { background: #3D3D3D; color: white; padding: 15px; font-size: 18px; border-radius: 10px; text-decoration: none; display: block; text-align: center; }
        .btn-checkout:hover { background: #2d2d2d; color: white; }
        .footer { background: #3D3D3D; color: white; padding: 30px 0; text-align: center; }
    </style>
</head>
<body>
    <?php include "header.php" ?>

    <div class="main-container">
        <div class="cart-container">
            <h2 class="mb-4"><i class="bi bi-cart"></i> Корзина</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-cart-x" style="font-size: 5rem; color: #dee2e6;"></i>
                    <h3 class="mt-3">Корзина пуста</h3>
                    <p class="text-muted">Добавьте товары из каталога</p>
                    <a href="index.php" class="btn btn-primary btn-lg">Перейти в каталог</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="cart-table">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" id="cart-item-<?= $item['id'] ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?= htmlspecialchars($item['picture'] ?? 'https://via.placeholder.com/100') ?>" 
                                                 class="product-image" alt="">
                                        </div>
                                        <div class="col-md-4">
                                            <h5><?= htmlspecialchars($item['name']) ?></h5>
                                            <small class="text-muted">Арт: <?= htmlspecialchars($item['article']) ?></small>
                                            <?php if ($item['selected_color']): ?>
                                                <div><small>Цвет: <?= htmlspecialchars($item['selected_color']) ?></small></div>
                                            <?php endif; ?>
                                            <?php if ($item['selected_size']): ?>
                                                <div><small>Размер: <?= htmlspecialchars($item['selected_size']) ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control quantity-input" 
                                                   value="<?= $item['quantity'] ?>" min="1"
                                                   onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                        </div>
                                        <div class="col-md-2">
                                            <strong class="price"><?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽</strong>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeItem(<?= $item['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h4>Сумма заказа</h4>
                            <table class="table table-borderless">
                                <tr>
                                    <td>Товары (<?= $cart_count ?> шт.)</td>
                                    <td class="text-end" id="cart-subtotal"><?= number_format($cart_total, 0, ',', ' ') ?> ₽</td>
                                </tr>
                                <tr>
                                    <td>Доставка</td>
                                    <td class="text-end">0 ₽</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Итого</td>
                                    <td class="text-end" id="cart-total"><?= number_format($cart_total, 0, ',', ' ') ?> ₽</td>
                                </tr>
                            </table>
                            <a href="checkout.php" class="btn-checkout mt-3">
                                <i class="bi bi-credit-card"></i> Оформить заказ
                            </a>
                            <button class="btn btn-outline-secondary w-100 mt-2" onclick="clearCart()">
                                <i class="bi bi-cart-x"></i> Очистить корзину
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "footer.php" ?>

    <script>
    function updateQuantity(cartId, quantity) {
        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `action=update&cart_id=${cartId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.cart-count').textContent = data.cart_count;
                document.getElementById('cart-subtotal').textContent = data.total.toLocaleString('ru-RU') + ' ₽';
                document.getElementById('cart-total').textContent = data.total.toLocaleString('ru-RU') + ' ₽';
            }
        });
    }

    function removeItem(cartId) {
        if (confirm('Удалить товар из корзины?')) {
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `action=remove&cart_id=${cartId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`cart-item-${cartId}`).remove();
                    document.querySelector('.cart-count').textContent = data.cart_count;
                    document.getElementById('cart-subtotal').textContent = data.total.toLocaleString('ru-RU') + ' ₽';
                    document.getElementById('cart-total').textContent = data.total.toLocaleString('ru-RU') + ' ₽';
                    
                    if (data.cart_count == 0) {
                        location.reload();
                    }
                }
            });
        }
    }

    function clearCart() {
        if (confirm('Очистить корзину?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="clear">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>