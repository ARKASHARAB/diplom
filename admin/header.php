<?php
// admin/header.php - хедер для админ-панели
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка авторизации
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Подключение к БД
require_once __DIR__ . '/../config.php';
$db = getDB();

// Получение уведомлений
$new_orders = $db->query("SELECT COUNT(*) FROM admindoor1_orders WHERE status = 'new'")->fetchColumn();
$low_stock = $db->query("SELECT COUNT(*) FROM " . TABLE_PRODUCTS . " WHERE quantity > 0 AND quantity < 10")->fetchColumn();
$total_products = $db->query("SELECT COUNT(*) FROM " . TABLE_PRODUCTS)->fetchColumn();

// Проверка состояния сайдбара (из куки)
$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            display: flex;
            min-height: 100vh;
        }
        
        /* Сайдбар */
        .admin-sidebar {
            width: 260px;
            background: #3D3D3D;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
        }
        
        .admin-sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        
        .sidebar-toggle {
            position: absolute;
            right: -12px;
            top: 20px;
            width: 24px;
            height: 24px;
            background: #e74c3c;
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 12px;
            transition: all 0.3s;
            z-index: 1001;
        }
        
        .sidebar-toggle:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        .admin-sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity 0.3s;
        }
        
        .admin-sidebar.collapsed .sidebar-header h4 span,
        .admin-sidebar.collapsed .sidebar-header small,
        .admin-sidebar.collapsed .admin-details {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .sidebar-header h4 i {
            color: #e74c3c;
        }
        
        .sidebar-header small {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            display: block;
            margin-top: 5px;
            transition: opacity 0.3s;
        }
        
        .admin-info {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .admin-sidebar.collapsed .admin-info {
            padding: 15px;
            justify-content: center;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            background: #e74c3c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }
        
        .admin-details h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .admin-details small {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .sidebar-menu::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar-menu::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .admin-sidebar.collapsed .sidebar-menu a {
            padding: 12px;
            justify-content: center;
        }
        
        .admin-sidebar.collapsed .sidebar-menu a span:not(.badge) {
            display: none;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a.active {
            background: #e74c3c;
            color: white;
        }
        
        .sidebar-menu a i {
            width: 20px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .admin-sidebar.collapsed .sidebar-menu a i {
            width: auto;
            font-size: 20px;
        }
        
        .sidebar-menu .badge {
            margin-left: auto;
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .admin-sidebar.collapsed .sidebar-menu .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            margin-left: 0;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-menu hr {
            margin: 15px 20px;
            border-color: rgba(255,255,255,0.1);
        }
        
        .admin-sidebar.collapsed .sidebar-menu hr {
            margin: 15px 10px;
        }
        
        /* Основной контент */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        
        .admin-main.expanded {
            margin-left: 70px;
        }
        
        .admin-navbar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        
        .nav-title {
            font-size: 18px;
            font-weight: 600;
            color: #3D3D3D;
        }
        
        .nav-title i {
            margin-right: 10px;
            color: #e74c3c;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .notifications {
            position: relative;
            cursor: pointer;
            padding: 5px;
        }
        
        .notifications i {
            font-size: 20px;
            color: #3D3D3D;
        }
        
        .notifications .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .datetime {
            color: #6c757d;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .datetime i {
            color: #3D3D3D;
        }
        
        .admin-stats {
            background: white;
            border-radius: 8px;
            padding: 5px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
        }
        
        .admin-stats span {
            color: #3D3D3D;
        }
        
        .admin-stats strong {
            color: #e74c3c;
            margin-left: 5px;
        }
        
        .admin-content {
            padding: 25px;
            flex: 1;
        }
        
        /* Уведомления */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        /* Карточки */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #e9ecef;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #3D3D3D;
        }
        
        /* Таблицы */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .btn-admin {
            background: #3D3D3D;
            border-color: #3D3D3D;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-admin:hover {
            background: #2d2d2d;
            border-color: #2d2d2d;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            border-color: #c0392b;
        }
        
        .page-link {
            color: #3D3D3D;
        }
        
        .page-item.active .page-link {
            background: #3D3D3D;
            border-color: #3D3D3D;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            
            .admin-sidebar .sidebar-header h4 span,
            .admin-sidebar .sidebar-header small,
            .admin-sidebar .admin-details,
            .admin-sidebar .sidebar-menu a span:not(.badge) {
                display: none;
            }
            
            .admin-sidebar .admin-info {
                justify-content: center;
                padding: 15px;
            }
            
            .admin-sidebar .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .admin-sidebar .sidebar-menu a i {
                margin: 0;
                font-size: 20px;
            }
            
            .admin-main {
                margin-left: 70px;
            }
            
            .sidebar-toggle {
                display: none;
            }
            
            .nav-right {
                gap: 10px;
            }
            
            .datetime span {
                display: none;
            }
        }
        
        /* Статусы заказов */
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
        
        .payment-pending { background: #fff3cd; color: #664d03; }
        .payment-paid { background: #d1e7dd; color: #0f5132; }
        .payment-failed { background: #f8d7da; color: #842029; }
        
        /* Прогресс бары */
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background: #3D3D3D;
        }
    </style>
</head>
<body>
    <div class="admin-sidebar <?= $sidebar_collapsed ? 'collapsed' : '' ?>">
        <div class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="bi bi-chevron-left"></i>
        </div>
        
        <div class="sidebar-header">
            <h4>
                <span style="font-size: 28px;"><svg width="35" height="30" viewBox="0 0 75 72" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M34.1263 33.5V42.7253M34.1368 12.0879H12C6.47715 12.0879 2 16.5651 2 22.0879V60C2 65.5228 6.47715 70 12 70H30.8632C36.386 70 40.8632 65.5228 40.8632 60V59.1648M34.1368 12.0879C37.8517 12.0879 40.8632 15.0994 40.8632 18.8142V59.1648M34.1368 12.0879C34.1368 6.56507 38.614 2 44.1368 2H63C68.5228 2 73 6.47715 73 12V49.1648C73 54.6877 68.5228 59.1648 63 59.1648H40.8632M59.5 31.5165H68.8895" stroke="white" stroke-width="4"/>
</svg></span>
                <span>Admin</span>
            </h4>
            <small>Панель управления</small>
        </div>
        
        <div class="admin-info">
            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['username'], 0, 1)) ?>
            </div>
            <div class="admin-details">
                <h6><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']) ?></h6>
                <small>
                    <i class="bi bi-shield-check"></i>
                    <?= $_SESSION['user_role'] === 'admin' ? 'Администратор' : 'Менеджер' ?>
                </small>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Дашборд</span>
            </a>
            <a href="products.php" class="<?= strpos($_SERVER['PHP_SELF'], 'products.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                <span>Товары</span>
                <?php if ($total_products > 0): ?>
                    <span class="badge"><?= $total_products ?></span>
                <?php endif; ?>
            </a>
            <a href="orders.php" class="<?= strpos($_SERVER['PHP_SELF'], 'orders.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-cart"></i>
                <span>Заказы</span>
                <?php if ($new_orders > 0): ?>
                    <span class="badge"><?= $new_orders ?></span>
                <?php endif; ?>
            </a>
            <a href="users.php" class="<?= strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Пользователи</span>
            </a>
            <a href="categories.php" class="<?= strpos($_SERVER['PHP_SELF'], 'categories.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>
                <span>Категории</span>
            </a>
            
            <hr>
            
            <a href="logs.php" class="<?= strpos($_SERVER['PHP_SELF'], 'logs.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span>Логи действий</span>
            </a>
            <a href="backup.php" class="<?= strpos($_SERVER['PHP_SELF'], 'backup.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-database"></i>
                <span>Резервное копирование</span>
            </a>
            <a href="settings.php" class="<?= strpos($_SERVER['PHP_SELF'], 'settings.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Настройки</span>
            </a>
            
            <hr>
            
            <a href="../index.php">
                <i class="bi bi-house"></i>
                <span>На сайт</span>
            </a>
            <a href="../?logout=1" style="color: #e74c3c;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Выход</span>
            </a>
        </div>
    </div>
    
    <div class="admin-main <?= $sidebar_collapsed ? 'expanded' : '' ?>">
        <div class="admin-navbar">
            <div class="nav-title">
                <i class="bi bi-<?= $page_icon ?? 'speedometer2' ?>"></i>
                <?= $page_title ?? 'Панель управления' ?>
            </div>
            
            <div class="nav-right">
                <div class="admin-stats d-none d-md-flex">
                    <span><i class="bi bi-box"></i> Товаров: <strong><?= $total_products ?></strong></span>
                    <span class="text-muted">|</span>
                    <span><i class="bi bi-cart"></i> Новых: <strong><?= $new_orders ?></strong></span>
                </div>
                
                <div class="notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($new_orders > 0 || $low_stock > 0): ?>
                        <span class="badge"><?= $new_orders + $low_stock ?></span>
                    <?php endif; ?>
                    
                    <!-- Выпадающее меню уведомлений (можно добавить позже) -->
                </div>
                
                <div class="datetime">
                    <i class="bi bi-clock"></i>
                    <span><?= date('d.m.Y H:i') ?></span>
                </div>
            </div>
        </div>
        
        <div class="admin-content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    const isCollapsed = sidebar.classList.contains('collapsed');
    
    if (isCollapsed) {
        sidebar.classList.remove('collapsed');
        main.classList.remove('expanded');
        document.cookie = 'sidebar_collapsed=false; path=/; max-age=' + 60*60*24*30; // 30 дней
    } else {
        sidebar.classList.add('collapsed');
        main.classList.add('expanded');
        document.cookie = 'sidebar_collapsed=true; path=/; max-age=' + 60*60*24*30; // 30 дней
    }
}

// Добавляем обработчик для скрытия сайдбара на мобильных устройствах при клике на ссылку
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // На мобильных устройствах автоматически сворачиваем сайдбар после клика
            if (window.innerWidth <= 768) {
                setTimeout(function() {
                    sidebar.classList.add('collapsed');
                    main.classList.add('expanded');
                    document.cookie = 'sidebar_collapsed=true; path=/; max-age=' + 60*60*24*30;
                }, 100);
            }
        });
    });
});
</script>