<?php
// header.php - общий шапка сайта
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Функция проверки авторизации (если не определена)
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Получаем количество товаров в корзине
$cart_count = 0;
if (isset($_SESSION['cart_session'])) {
    require_once 'config.php';
    $db = getDB();
    $session_id = $_SESSION['cart_session'];
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    if ($user_id) {
        $stmt = $db->prepare("SELECT SUM(quantity) FROM admindoor1_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) FROM admindoor1_cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    $cart_count = $stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= SITE_NAME ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background: #3D3D3D;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo:hover {
            color: rgba(255,255,255,0.9);
        }
        
        .nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav a.active {
            background: rgba(255,255,255,0.2);
            font-weight: 500;
        }
        
        .cart-link {
            position: relative;
        }
        
        .cart-count {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: <?= $cart_count > 0 ? 'inline-flex' : 'none' ?>;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: -8px;
            right: -8px;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .user-info:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 220px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
            padding: 10px 0;
            display: none;
            z-index: 1001;
        }
        
        .user-menu:hover .user-dropdown {
            display: block;
        }
        
        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .user-dropdown a:hover {
            background: #f5f5f5;
        }
        
        .user-dropdown a i {
            width: 20px;
            color: #3D3D3D;
        }
        
        .user-dropdown hr {
            margin: 8px 0;
            border-color: #e9ecef;
        }
        
        .admin-badge {
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }
        
        .search-form {
            display: flex;
            gap: 5px;
        }
        
        .search-form input {
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            min-width: 250px;
        }
        
        .search-form button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .search-form input {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                                <span style="font-size: 28px;"><svg width="35" height="30" viewBox="0 0 75 72" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M34.1263 33.5V42.7253M34.1368 12.0879H12C6.47715 12.0879 2 16.5651 2 22.0879V60C2 65.5228 6.47715 70 12 70H30.8632C36.386 70 40.8632 65.5228 40.8632 60V59.1648M34.1368 12.0879C37.8517 12.0879 40.8632 15.0994 40.8632 18.8142V59.1648M34.1368 12.0879C34.1368 6.56507 38.614 2 44.1368 2H63C68.5228 2 73 6.47715 73 12V49.1648C73 54.6877 68.5228 59.1648 63 59.1648H40.8632M59.5 31.5165H68.8895" stroke="white" stroke-width="4"/>
</svg></span> <?= SITE_NAME ?>
            </a>
            
            <nav class="nav">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-house"></i> Каталог
                </a>

                
                <form class="search-form d-none d-md-flex" action="index.php" method="GET">
                    <input type="text" name="search" placeholder="Поиск товаров..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit"><i class="bi bi-search"></i></button>
                </form>
                
                <a href="cart.php" class="cart-link">
                    <i class="bi bi-cart"></i> Корзина
                    <span class="cart-count"><?= $cart_count ?></span>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu">
                        <div class="user-info">
                            <span class="user-avatar">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['username'], 0, 1)) ?>
                            </span>
                            <span class="d-none d-md-inline">
                                <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']) ?>
                            </span>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="user-dropdown">
                            <a href="profile.php">
                                <i class="bi bi-person-circle"></i> Личный кабинет
                            </a>
                            <a href="orders.php">
                                <i class="bi bi-box"></i> Мои заказы
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <hr>
                                <a href="admin/index.php">
                                    <i class="bi bi-speedometer2"></i> Админ-панель
                                </a>
                            <?php endif; ?>
                            <hr>
                            <a href="?logout=1" class="text-danger">
                                <i class="bi bi-box-arrow-right"></i> Выход
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Вход
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="main-container">