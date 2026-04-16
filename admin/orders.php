<?php
// admin/orders.php - управление заказами
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = clean($_POST['status']);
    $payment_status = clean($_POST['payment_status']);
    $admin_comment = clean($_POST['admin_comment']);

    try {
        $db->beginTransaction();

        // Обновление заказа
        $stmt = $db->prepare("
            UPDATE admindoor1_orders 
            SET status = ?, payment_status = ?, admin_comment = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $payment_status, $admin_comment, $order_id]);

        // Запись в историю
        $stmt = $db->prepare("
            INSERT INTO admindoor1_order_status_history (order_id, status, comment, changed_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $status, "Статус изменен на: $status", $_SESSION['user_id']]);

        // Логирование
        $log_stmt = $db->prepare("
            INSERT INTO admindoor1_logs (user_id, action, details, ip_address) 
            VALUES (?, 'order_updated', ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            "Заказ #$order_id, новый статус: $status",
            $_SERVER['REMOTE_ADDR']
        ]);

        $db->commit();
        $message = 'Статус заказа обновлен';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Фильтры
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($status_filter) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if ($search) {
    $where[] = "(order_number LIKE :search OR customer_name LIKE :search OR customer_email LIKE :search OR customer_phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Пагинация
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Общее количество
$count_sql = "SELECT COUNT(*) FROM admindoor1_orders $where_sql";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Получение заказов
$sql = "
    SELECT o.*, 
           (SELECT COUNT(*) FROM admindoor1_order_items WHERE order_id = o.id) as items_count
    FROM admindoor1_orders o
    $where_sql
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

// Статистика по статусам
$stats = $db->query("
    SELECT status, COUNT(*) as count, SUM(total) as total_sum 
    FROM admindoor1_orders 
    GROUP BY status
")->fetchAll();

$stats_by_status = [];
foreach ($stats as $stat) {
    $stats_by_status[$stat['status']] = $stat;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: #3D3D3D; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu a { display: block; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-menu a.active { background: #2d2d2d; border-left: 4px solid #e74c3c; }
        .main-content { margin-left: 25px; padding: 20px; }
        .navbar { background: white; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .orders-table { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: normal; }
        .status-new { background: #17a2b8; color: white; }
        .status-processing { background: #ffc107; color: black; }
        .status-confirmed { background: #28a745; color: white; }
        .status-shipped { background: #007bff; color: white; }
        .status-delivered { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .payment-pending { background: #ffc107; color: black; }
        .payment-paid { background: #28a745; color: white; }
        .payment-failed { background: #dc3545; color: white; }
        .stats-card { background: white; border-radius: 10px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0"><i class="bi bi-cart"></i> Управление заказами</h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span>Всего заказов: <?= $total_items ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <small class="text-muted">Новые</small>
                    <h3><?= $stats_by_status['new']['count'] ?? 0 ?></h3>
                    <small><?= number_format($stats_by_status['new']['total_sum'] ?? 0, 0, ',', ' ') ?> ₽</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <small class="text-muted">В обработке</small>
                    <h3><?= $stats_by_status['processing']['count'] ?? 0 ?></h3>
                    <small><?= number_format($stats_by_status['processing']['total_sum'] ?? 0, 0, ',', ' ') ?> ₽</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <small class="text-muted">Доставлено</small>
                    <h3><?= $stats_by_status['delivered']['count'] ?? 0 ?></h3>
                    <small><?= number_format($stats_by_status['delivered']['total_sum'] ?? 0, 0, ',', ' ') ?> ₽</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <small class="text-muted">Отменено</small>
                    <h3><?= $stats_by_status['cancelled']['count'] ?? 0 ?></h3>
                    <small><?= number_format($stats_by_status['cancelled']['total_sum'] ?? 0, 0, ',', ' ') ?> ₽</small>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="GET" class="row g-2">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Все статусы</option>
                            <option value="new" <?= $status_filter == 'new' ? 'selected' : '' ?>>Новые</option>
                            <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>В обработке</option>
                            <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                            <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                            <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Поиск по номеру, имени, email, телефону" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-admin w-100">
                            <i class="bi bi-search"></i> Применить
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($action === 'view' && $id > 0): ?>
            <?php
            // Детальный просмотр заказа
            $stmt = $db->prepare("SELECT * FROM admindoor1_orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                redirect('orders.php');
            }

            // Товары в заказе
            $stmt = $db->prepare("SELECT * FROM admindoor1_order_items WHERE order_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            // История статусов
            $stmt = $db->prepare("
                SELECT h.*, u.username 
                FROM admindoor1_order_status_history h
                LEFT JOIN admindoor1_users u ON h.changed_by = u.id
                WHERE h.order_id = ?
                ORDER BY h.created_at DESC
            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll();
            ?>
            <div class="card">
                <div class="card-header">
                    <h5>Заказ #<?= htmlspecialchars($order['order_number']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Информация о заказе</h6>
                            <table class="table table-sm">
                                <tr><th>Номер:</th><td><?= $order['order_number'] ?></td></tr>
                                <tr><th>Дата:</th><td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td></tr>
                                <tr><th>Статус:</th>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Оплата:</th>
                                    <td>
                                        <span class="status-badge payment-<?= $order['payment_status'] ?>">
                                            <?= $order['payment_status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Сумма:</th><td><strong><?= number_format($order['total'], 0, ',', ' ') ?> ₽</strong></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Информация о клиенте</h6>
                            <table class="table table-sm">
                                <tr><th>Имя:</th><td><?= htmlspecialchars($order['customer_name']) ?></td></tr>
                                <tr><th>Email:</th><td><?= htmlspecialchars($order['customer_email']) ?></td></tr>
                                <tr><th>Телефон:</th><td><?= htmlspecialchars($order['customer_phone']) ?></td></tr>
                                <tr><th>Адрес:</th><td><?= nl2br(htmlspecialchars($order['customer_address'] ?? '-')) ?></td></tr>
                                <tr><th>Доставка:</th><td><?= $order['delivery_method'] ?></td></tr>
                                <tr><th>Оплата:</th><td><?= $order['payment_method'] ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="mt-4">Товары в заказе</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Артикул</th>
                                <th>Цвет/Размер</th>
                                <th>Кол-во</th>
                                <th>Цена</th>
                                <th>Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= htmlspecialchars($item['product_article']) ?></td>
                                <td>
                                    <?= $item['selected_color'] ? "Цвет: {$item['selected_color']}<br>" : '' ?>
                                    <?= $item['selected_size'] ? "Размер: {$item['selected_size']}" : '' ?>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['price'], 0, ',', ' ') ?> ₽</td>
                                <td><?= number_format($item['total'], 0, ',', ' ') ?> ₽</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end">Итого:</td>
                                <td><?= number_format($order['total'], 0, ',', ' ') ?> ₽</td>
                            </tr>
                        </tfoot>
                    </table>

                    <?php if ($order['comment']): ?>
                        <h6>Комментарий клиента</h6>
                        <div class="alert alert-info"><?= nl2br(htmlspecialchars($order['comment'])) ?></div>
                    <?php endif; ?>

                    <h6 class="mt-4">История статусов</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Комментарий</th>
                                <th>Изменил</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></td>
                                <td><span class="status-badge status-<?= $h['status'] ?>"><?= $h['status'] ?></span></td>
                                <td><?= htmlspecialchars($h['comment'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($h['username'] ?? 'Система') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h6 class="mt-4">Изменить статус</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                                <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="payment_status" class="form-select">
                                <option value="pending" <?= $order['payment_status'] == 'pending' ? 'selected' : '' ?>>Ожидает оплаты</option>
                                <option value="paid" <?= $order['payment_status'] == 'paid' ? 'selected' : '' ?>>Оплачен</option>
                                <option value="failed" <?= $order['payment_status'] == 'failed' ? 'selected' : '' ?>>Ошибка оплаты</option>
                                <option value="refunded" <?= $order['payment_status'] == 'refunded' ? 'selected' : '' ?>>Возврат</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="admin_comment" class="form-control" 
                                   placeholder="Комментарий" value="<?= htmlspecialchars($order['admin_comment'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="update_status" class="btn btn-success w-100">
                                Обновить
                            </button>
                        </div>
                    </form>

                    <div class="mt-3">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Назад к списку
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Список заказов -->
            <div class="orders-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Номер</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Оплата</th>
                            <th>Товаров</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <?= htmlspecialchars($order['customer_name']) ?><br>
                                <small><?= htmlspecialchars($order['customer_phone']) ?></small>
                            </td>
                            <td><strong><?= number_format($order['total'], 0, ',', ' ') ?> ₽</strong></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge payment-<?= $order['payment_status'] ?>">
                                    <?= $order['payment_status'] ?>
                                </span>
                            </td>
                            <td><?= $order['items_count'] ?></td>
                            <td>
                                <a href="?action=view&id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Пагинация -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?p=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>