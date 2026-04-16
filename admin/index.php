<?php
// admin/index.php - главная страница админ-панели
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Статистика
$total_products = $db->query("SELECT COUNT(*) FROM " . TABLE_PRODUCTS)->fetchColumn();
$total_users = $db->query("SELECT COUNT(*) FROM admindoor1_users")->fetchColumn();
$total_logs = $db->query("SELECT COUNT(*) FROM admindoor1_logs")->fetchColumn();

// Товары по производителям
$by_manufacturer = $db->query("
    SELECT attributes_manufacturer, COUNT(*) as count 
    FROM " . TABLE_PRODUCTS . " 
    WHERE attributes_manufacturer IS NOT NULL 
    GROUP BY attributes_manufacturer 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll();

// Последние логи
$recent_logs = $db->query("
    SELECT l.*, u.username 
    FROM admindoor1_logs l 
    LEFT JOIN admindoor1_users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
")->fetchAll();

// Последние добавленные товары
$recent_products = $db->query("
    SELECT id, name, article, price, picture, created_at 
    FROM " . TABLE_PRODUCTS . " 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: #3D3D3D; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: block; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-menu a.active { background: #2d2d2d; border-left: 4px solid #e74c3c; }
        .main-content { margin-left: 25px; padding: 20px; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 15px; border-radius: 10px; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-icon { font-size: 40px; color: #3D3D3D; }
        .stat-value { font-size: 32px; font-weight: bold; }
        .recent-table { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .product-image { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>


    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Дашборд</h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span><i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon"><i class="bi bi-box"></i></div>
                    <div class="stat-value"><?= $total_products ?></div>
                    <div class="stat-label">Товаров</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Пользователей</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                    <div class="stat-value"><?= $total_logs ?></div>
                    <div class="stat-label">Записей логов</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon"><i class="bi bi-database"></i></div>
                    <div class="stat-value"><?= count($by_manufacturer) ?></div>
                    <div class="stat-label">Производителей</div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="recent-table">
                    <h5><i class="bi bi-trophy"></i> Топ производителей</h5>
                    <table class="table">
                        <?php foreach ($by_manufacturer as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['attributes_manufacturer']) ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?= min(100, ($m['count'] / $total_products * 100)) ?>%">
                                        <?= $m['count'] ?> шт.
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="recent-table">
                    <h5><i class="bi bi-clock-history"></i> Последние действия</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Пользователь</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($log['created_at'])) ?></td>
                                <td><?= htmlspecialchars($log['username'] ?? 'Гость') ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="recent-table">
                    <h5><i class="bi bi-plus-circle"></i> Последние добавленные товары</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Артикул</th>
                                <th>Цена</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_products as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($p['picture'] ?? 'https://via.placeholder.com/40') ?>" 
                                         class="product-image" alt="">
                                </td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['article']) ?></td>
                                <td><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
                                <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>