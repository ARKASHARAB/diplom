<?php
// admin/logs.php - просмотр логов
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$total = $db->query("SELECT COUNT(*) FROM admindoor1_logs")->fetchColumn();
$total_pages = ceil($total / $limit);

$logs = $db->query("
    SELECT l.*, u.username 
    FROM admindoor1_logs l 
    LEFT JOIN admindoor1_users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT $limit OFFSET $offset
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи действий</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: #3D3D3D; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .navbar { background: white; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .log-table { background: white; border-radius: 10px; padding: 20px; }
        .action-badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .action-login { background: #28a745; color: white; }
        .action-logout { background: #dc3545; color: white; }
        .action-product { background: #17a2b8; color: white; }
        .action-user { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0"><i class="bi bi-journal-text"></i> Логи действий</h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span>Всего записей: <?= $total ?></span>
            </div>
        </div>

        <div class="log-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Детали</th>
                        <th>IP адрес</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <?= htmlspecialchars($log['username'] ?? 'Гость') ?>
                            <?php if (!$log['user_id']): ?>
                                <span class="badge bg-secondary">Гость</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $action_class = '';
                            if (strpos($log['action'], 'login') !== false) $action_class = 'action-login';
                            elseif (strpos($log['action'], 'logout') !== false) $action_class = 'action-logout';
                            elseif (strpos($log['action'], 'product') !== false) $action_class = 'action-product';
                            elseif (strpos($log['action'], 'user') !== false) $action_class = 'action-user';
                            ?>
                            <span class="action-badge <?= $action_class ?>"><?= $log['action'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                        <td><?= $log['ip_address'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>