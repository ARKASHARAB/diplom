<?php
// orders.php - страница заказов пользователя
require_once 'config.php';
require_once 'auth.php';

// Проверяем, авторизован ли пользователь
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'orders.php';
    redirect('login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Получаем список заказов пользователя
$stmt = $db->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM admindoor1_order_items WHERE order_id = o.id) as items_count,
           (SELECT SUM(quantity) FROM admindoor1_order_items WHERE order_id = o.id) as total_items
    FROM admindoor1_orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Статистика по заказам
$stats = [
    'total' => count($orders),
    'total_sum' => array_sum(array_column($orders, 'total')),
    'completed' => count(array_filter($orders, fn($o) => $o['status'] === 'delivered')),
    'in_progress' => count(array_filter($orders, fn($o) => in_array($o['status'], ['new', 'processing', 'confirmed', 'shipped'])))
];

// Функция для получения статуса на русском
function getStatusText($status) {
    $statuses = [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'confirmed' => 'Подтвержден',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменен'
    ];
    return $statuses[$status] ?? $status;
}

// Функция для получения цвета статуса
function getStatusColor($status) {
    $colors = [
        'new' => 'info',
        'processing' => 'warning',
        'confirmed' => 'primary',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

// Функция для статуса оплаты
function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'failed' => 'Ошибка оплаты',
        'refunded' => 'Возврат'
    ];
    return $statuses[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        
        
        .main-container {
            min-height: calc(100vh - 150px);
            padding: 30px 0;
        }
        
        .page-title {
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 40px;
            color: #3D3D3D;
            margin-bottom: 10px;
        }
        
        .stats-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .orders-table {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #3D3D3D;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-new { background: #cff4fc; color: #055160; }
        .status-processing { background: #fff3cd; color: #664d03; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .status-shipped { background: #cfe2ff; color: #084298; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #842029; }
        
        .payment-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            background: #e9ecef;
            color: #495057;
        }
        
        .payment-paid {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .product-image-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .order-total {
            font-size: 18px;
            font-weight: bold;
            color: #d32f2f;
        }
        
        .btn-outline-primary {
            border-color: #3D3D3D;
            color: #3D3D3D;
        }
        
        .btn-outline-primary:hover {
            background: #3D3D3D;
            color: white;
        }
        
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .empty-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .footer {
            background: #3D3D3D;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
        
        /* Модальное окно */
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: #3D3D3D;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .order-detail-item {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-detail-item:last-child {
            border-bottom: none;
        }
        
        .history-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .history-date {
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include "header.php" ?>

    <div class="main-container">
        <div class="container">
            <div class="page-title">
                <h2><i class="bi bi-box"></i> Мои заказы</h2>
                <p class="text-muted">История и статусы ваших заказов</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Статистика -->
            <?php if (!empty($orders)): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-box"></i></div>
                        <div class="stats-value"><?= $stats['total'] ?></div>
                        <div class="stats-label">Всего заказов</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-clock-history"></i></div>
                        <div class="stats-value"><?= $stats['in_progress'] ?></div>
                        <div class="stats-label">В обработке</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stats-value"><?= $stats['completed'] ?></div>
                        <div class="stats-label">Доставлено</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-currency-ruble"></i></div>
                        <div class="stats-value"><?= number_format($stats['total_sum'], 0, ',', ' ') ?> ₽</div>
                        <div class="stats-label">На сумму</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Список заказов -->
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <div class="empty-icon">
                        <i class="bi bi-box"></i>
                    </div>
                    <h3>У вас пока нет заказов</h3>
                    <p class="text-muted">Перейдите в каталог, чтобы выбрать товары</p>
                    <a href="index.php" class="btn btn-primary btn-lg mt-3">
                        <i class="bi bi-door-closed"></i> Перейти в каталог
                    </a>
                </div>
            <?php else: ?>
                <div class="orders-table">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" id="order-<?= $order['id'] ?>">
                            <div class="order-header">
                                <div>
                                    <span class="order-number">Заказ #<?= htmlspecialchars($order['order_number']) ?></span>
                                    <span class="order-date ms-3">
                                        <i class="bi bi-calendar"></i> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= getStatusText($order['status']) ?>
                                    </span>
                                    <span class="payment-badge <?= $order['payment_status'] === 'paid' ? 'payment-paid' : '' ?> ms-2">
                                        <?= getPaymentStatusText($order['payment_status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center">
                                        <?php
                                        // Получаем первые 3 товара из заказа для превью
                                        $stmt = $db->prepare("SELECT * FROM admindoor1_order_items WHERE order_id = ? LIMIT 3");
                                        $stmt->execute([$order['id']]);
                                        $preview_items = $stmt->fetchAll();
                                        ?>
                                        <?php foreach ($preview_items as $item): ?>
                                            <div class="me-3 text-center">
                                                <div class="bg-light rounded p-2" style="width: 70px;">
                                                    <i class="bi bi-box" style="font-size: 30px; color: #6c757d;"></i>
                                                    <small class="d-block text-truncate"><?= htmlspecialchars($item['product_name']) ?></small>
                                                    <small class="text-muted">x<?= $item['quantity'] ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($order['items_count'] > 3): ?>
                                            <div class="text-muted">
                                                +<?= $order['items_count'] - 3 ?> еще
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-muted small">Товаров:</div>
                                    <div class="fw-bold"><?= $order['total_items'] ?> шт.</div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="text-muted small">Сумма:</div>
                                    <div class="order-total"><?= number_format($order['total'], 0, ',', ' ') ?> ₽</div>
                                    <button class="btn btn-sm btn-outline-primary mt-2" 
                                            onclick="showOrderDetails(<?= $order['id'] ?>)">
                                        <i class="bi bi-eye"></i> Подробнее
                                    </button>
                                </div>
                            </div>

                            <?php if ($order['comment']): ?>
                                <div class="mt-3 p-2 bg-light rounded">
                                    <small><i class="bi bi-chat"></i> <?= nl2br(htmlspecialchars($order['comment'])) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно деталей заказа -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Детали заказа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderModalBody">
                    <!-- Данные загрузятся через AJAX -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Обновление счетчика корзины
    function updateCartCount() {
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count;
                    cartCount.style.display = data.count > 0 ? 'inline-flex' : 'none';
                }
            });
    }

    // Показ деталей заказа
    function showOrderDetails(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderModal'));
        const modalBody = document.getElementById('orderModalBody');
        
        // Показываем загрузку
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Загружаем данные
        fetch(`get-order-details.php?id=${orderId}`)
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(error => {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        Ошибка загрузки данных заказа
                    </div>
                `;
            });
    }

    // Запуск при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
    </script>
</body>
</html>