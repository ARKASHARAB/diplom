<?php
// admin/color_settings.php - настройка цветов товаров
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$message = '';
$error = '';

// Создаем таблицу для хранения цветов, если её нет
$db->exec("
    CREATE TABLE IF NOT EXISTS admindoor1_color_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        color_name VARCHAR(100) NOT NULL,
        color_code VARCHAR(7) NOT NULL,
        display_name VARCHAR(100),
        sort_order INT DEFAULT 0,
        is_active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_color_name (color_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Получаем все цвета из товаров (реально существующие в базе)
$existing_colors_in_products = $db->query("
    SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(attributes_color, ',', n), ',', -1)) as color_name
    FROM " . TABLE_PRODUCTS . "
    CROSS JOIN (
        SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
        UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
    ) numbers
    WHERE attributes_color IS NOT NULL AND attributes_color != ''
    AND CHAR_LENGTH(attributes_color) - CHAR_LENGTH(REPLACE(attributes_color, ',', '')) >= n-1
")->fetchAll(PDO::FETCH_COLUMN);

// Очищаем имена цветов
$existing_colors = [];
foreach ($existing_colors_in_products as $color) {
    $color = trim($color);
    if ($color && !in_array($color, $existing_colors)) {
        $existing_colors[] = $color;
    }
}

// Стандартные цвета
$default_colors = [
    ['белый', '#FFFFFF', 'Белый', 10],
    ['черный', '#000000', 'Черный', 20],
    ['серый', '#808080', 'Серый', 30],
    ['коричневый', '#8B4513', 'Коричневый', 40],
    ['бежевый', '#F5F5DC', 'Бежевый', 50],
    ['венге', '#4A2C2C', 'Венге', 60],
    ['дуб', '#8B5A2B', 'Дуб', 70],
    ['орех', '#5D3A1A', 'Орех', 80],
    ['красный', '#FF4444', 'Красный', 90],
    ['синий', '#4444FF', 'Синий', 100],
    ['зеленый', '#44FF44', 'Зеленый', 110],
    ['желтый', '#FFFF44', 'Желтый', 120],
    ['Truffo', '#8B5A2B', 'Трюфель', 130],
    ['Cinnamon', '#D2691E', 'Корица', 140],
    ['Mouse', '#A9A9A9', 'Мышиный', 150],
    ['Mocco', '#6F4E37', 'Мокко', 160],
    ['Latte L', '#C4A484', 'Латте', 170],
    ['Cotton', '#F5F5F5', 'Хлопок', 180],
    ['Ivory', '#FFFFF0', 'Слоновая кость', 190],
    ['Polar', '#E0E0E0', 'Полярный', 200],
    ['Wenge', '#4A2C2C', 'Венге', 210],
    ['Grey', '#808080', 'Серый', 220],
    ['Grey Wood', '#A9A9A9', 'Серое дерево', 230],
    ['Jet Loft', '#2F4F4F', 'Джет Лофт', 240],
    ['Brun Oak', '#8B4513', 'Брун Оак', 250],
    ['Sand Vellum', '#F4A460', 'Песочный велюм', 260],
    ['Antic Loft', '#BC8F8F', 'Антик Лофт', 270],
    ['Cappuccino', '#D2B48C', 'Капучино', 280],
    ['Griz Soft', '#778899', 'Гриз Софт', 290],
    ['Bianco', '#FFFFFF', 'Бианко', 300],
    ['Lin Vellum', '#DEB887', 'Лин Велюм', 310],
    ['Stone Oak', '#BC9A6C', 'Каменный дуб', 320],
    ['Scansom Oak', '#CD853F', 'Скансом Оак', 330],
    ['Fleet Soft', '#B0C4DE', 'Флит Софт', 340],
    ['Polar Soft', '#E0FFFF', 'Поляр Софт', 350],
    ['Nord Vellum', '#D3D3D3', 'Норд Велюм', 360],
    ['Snow', '#FFFFFF', 'Снежный', 370],
    ['Emalex Ice', '#F0F8FF', 'Эмалекс Айс', 380]
];

// Загружаем стандартные цвета, если таблица пуста
$check = $db->query("SELECT COUNT(*) FROM admindoor1_color_settings")->fetchColumn();
if ($check == 0) {
    $stmt = $db->prepare("INSERT INTO admindoor1_color_settings (color_name, color_code, display_name, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($default_colors as $color) {
        $stmt->execute([$color[0], $color[1], $color[2], $color[3]]);
    }
}

// Обработка добавления/редактирования цвета
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $color_name = clean($_POST['color_name']);
                $color_code = clean($_POST['color_code']);
                $display_name = clean($_POST['display_name']);
                $sort_order = intval($_POST['sort_order']);
                
                // Проверяем, существует ли уже такой цвет
                $check = $db->prepare("SELECT id FROM admindoor1_color_settings WHERE color_name = ?");
                $check->execute([$color_name]);
                if ($check->fetch()) {
                    $error = "Цвет с таким названием уже существует!";
                } else {
                    $stmt = $db->prepare("INSERT INTO admindoor1_color_settings (color_name, color_code, display_name, sort_order) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$color_name, $color_code, $display_name, $sort_order])) {
                        $message = "Цвет успешно добавлен!";
                    } else {
                        $error = "Ошибка при добавлении цвета!";
                    }
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $color_name = clean($_POST['color_name']);
                $color_code = clean($_POST['color_code']);
                $display_name = clean($_POST['display_name']);
                $sort_order = intval($_POST['sort_order']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $db->prepare("UPDATE admindoor1_color_settings SET color_name = ?, color_code = ?, display_name = ?, sort_order = ?, is_active = ? WHERE id = ?");
                if ($stmt->execute([$color_name, $color_code, $display_name, $sort_order, $is_active, $id])) {
                    $message = "Цвет успешно обновлен!";
                } else {
                    $error = "Ошибка при обновлении цвета!";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $db->prepare("SELECT color_name FROM admindoor1_color_settings WHERE id = ?");
                $stmt->execute([$id]);
                $color = $stmt->fetch();
                
                if ($color) {
                    // Проверяем, используется ли цвет в товарах
                    $check_usage = $db->prepare("SELECT COUNT(*) FROM " . TABLE_PRODUCTS . " WHERE attributes_color LIKE ?");
                    $check_usage->execute(["%" . $color['color_name'] . "%"]);
                    $usage_count = $check_usage->fetchColumn();
                    
                    if ($usage_count > 0) {
                        $error = "Нельзя удалить цвет «{$color['color_name']}», так как он используется в {$usage_count} товарах!";
                    } else {
                        $stmt = $db->prepare("DELETE FROM admindoor1_color_settings WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            $message = "Цвет успешно удален!";
                        } else {
                            $error = "Ошибка при удалении цвета!";
                        }
                    }
                }
                break;
                
            case 'add_missing_colors':
                // Добавляем только недостающие цвета из товаров
                $added_count = 0;
                $stmt = $db->prepare("INSERT IGNORE INTO admindoor1_color_settings (color_name, color_code, display_name, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                
                foreach ($existing_colors as $color_name) {
                    // Проверяем, есть ли уже такой цвет
                    $check = $db->prepare("SELECT id FROM admindoor1_color_settings WHERE color_name = ?");
                    $check->execute([$color_name]);
                    if (!$check->fetch()) {
                        // Генерируем случайный HEX код на основе имени цвета
                        $hash = md5($color_name);
                        $color_code = '#' . substr($hash, 0, 6);
                        $display_name = $color_name;
                        $sort_order = 500 + $added_count;
                        
                        if ($stmt->execute([$color_name, $color_code, $display_name, $sort_order])) {
                            $added_count++;
                        }
                    }
                }
                
                if ($added_count > 0) {
                    $message = "Добавлено {$added_count} новых цветов из товаров!";
                } else {
                    $message = "Все цвета из товаров уже присутствуют в настройках.";
                }
                break;
                
            case 'reset_default':
                // Сначала добавляем все существующие цвета из товаров
                $stmt = $db->prepare("INSERT IGNORE INTO admindoor1_color_settings (color_name, color_code, display_name, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                foreach ($existing_colors as $color_name) {
                    $hash = md5($color_name);
                    $color_code = '#' . substr($hash, 0, 6);
                    $display_name = $color_name;
                    $stmt->execute([$color_name, $color_code, $display_name, 500]);
                }
                
                // Затем добавляем стандартные цвета, которых еще нет
                $stmt = $db->prepare("INSERT IGNORE INTO admindoor1_color_settings (color_name, color_code, display_name, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                foreach ($default_colors as $color) {
                    $stmt->execute([$color[0], $color[1], $color[2], $color[3]]);
                }
                
                $message = "Настройки цветов обновлены! Добавлены все цвета из товаров и стандартные цвета.";
                break;
        }
    }
}

// Получаем параметры фильтрации и сортировки
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Сохраняем параметры в сессию для удобства
if ($search !== '') $_SESSION['color_search'] = $search;
if ($sort !== 'default') $_SESSION['color_sort'] = $sort;

// Если параметры не переданы, но есть в сессии — используем их
if ($search === '' && isset($_SESSION['color_search'])) $search = $_SESSION['color_search'];
if ($sort === 'default' && isset($_SESSION['color_sort'])) $sort = $_SESSION['color_sort'];

// Формируем SQL запрос с учетом поиска
$whereClause = '';
$params = [];
if (!empty($search)) {
    $whereClause = " WHERE (color_name LIKE ? OR display_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Определяем сортировку
$orderBy = " ORDER BY sort_order, color_name";
switch ($sort) {
    case 'name':
        $orderBy = " ORDER BY color_name";
        break;
    case 'usage':
        // Сортировка по количеству использований будет выполнена в PHP после подсчета
        $orderBy = " ORDER BY sort_order, color_name";
        break;
    case 'order':
        $orderBy = " ORDER BY sort_order";
        break;
}

// Получаем все цвета
$stmt = $db->prepare("SELECT * FROM admindoor1_color_settings" . $whereClause . $orderBy);
$stmt->execute($params);
$colors = $stmt->fetchAll();

// Получаем статистику использования цветов в товарах
$color_usage = $db->query("
    SELECT 
        attributes_color,
        COUNT(*) as count
    FROM " . TABLE_PRODUCTS . "
    WHERE attributes_color IS NOT NULL AND attributes_color != ''
    GROUP BY attributes_color
    ORDER BY count DESC
")->fetchAll();

// Создаем массив для быстрого доступа к использованию
$usage_map = [];
foreach ($color_usage as $usage) {
    $colors_in_product = explode(',', $usage['attributes_color']);
    foreach ($colors_in_product as $c) {
        $c = trim($c);
        if (!isset($usage_map[$c])) {
            $usage_map[$c] = 0;
        }
        $usage_map[$c] += $usage['count'];
    }
}

// Если выбрана сортировка по использованию, сортируем массив $colors
if ($sort === 'usage') {
    usort($colors, function($a, $b) use ($usage_map) {
        $usageA = isset($usage_map[$a['color_name']]) ? $usage_map[$a['color_name']] : 0;
        $usageB = isset($usage_map[$b['color_name']]) ? $usage_map[$b['color_name']] : 0;
        if ($usageA == $usageB) {
            return strcmp($a['color_name'], $b['color_name']);
        }
        return $usageB - $usageA;
    });
}

// Подсчитываем количество цветов, которые используются в товарах, но отсутствуют в настройках
$missing_colors = [];
foreach ($existing_colors as $color_name) {
    $found = false;
    foreach ($colors as $c) {
        if (strtolower($c['color_name']) == strtolower($color_name)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $missing_colors[] = $color_name;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка цветов - Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: #3D3D3D; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: block; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-menu a.active { background: #2d2d2d; border-left: 4px solid #e74c3c; }
        .main-content { margin-left: 25px; padding: 20px; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 15px; border-radius: 10px; }
        .color-preview { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            display: inline-block;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.2s;
        }
        .color-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .color-table td {
            vertical-align: middle;
        }
        .btn-sm-icon {
            padding: 4px 8px;
        }
        .usage-badge {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        .modal-color-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }
        .modal-color-preview:hover {
            transform: scale(1.05);
        }
        .color-preview-small {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            vertical-align: middle;
        }
        .alert-missing {
            background-color: #fff3cd;
            border-color: #ffecb5;
            color: #856404;
        }
        
        /* Color Picker стили */
        .color-picker-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }
        .color-picker-canvas {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        .color-spectrum {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            cursor: crosshair;
            position: relative;
            margin-bottom: 15px;
            background: #f00; /* будет переопределено в JS */
        }
        .color-spectrum-saturation {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(0,0,0,1));
        }
        .color-picker-handle {
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 1px rgba(0,0,0,0.3);
            pointer-events: none;
        }
        .color-hue {
            width: 100%;
            height: 20px;
            border-radius: 10px;
            margin: 15px 0;
            cursor: pointer;
            position: relative;
            background: linear-gradient(to right, 
                #ff0000 0%, #ffff00 17%, #00ff00 33%, 
                #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
        }
        .color-hue-handle {
            width: 12px;
            height: 24px;
            background: white;
            border: 2px solid #333;
            border-radius: 4px;
            position: absolute;
            transform: translateX(-50%);
            top: -2px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .color-opacity {
            width: 100%;
            height: 20px;
            border-radius: 10px;
            margin: 15px 0;
            cursor: pointer;
            position: relative;
            background: repeating-linear-gradient(45deg, #ccc 0px, #ccc 5px, #f0f0f0 5px, #f0f0f0 10px);
        }
        .color-opacity-gradient {
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }
        .color-opacity-handle {
            width: 12px;
            height: 24px;
            background: white;
            border: 2px solid #333;
            border-radius: 4px;
            position: absolute;
            transform: translateX(-50%);
            top: -2px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .color-values {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .color-values input {
            flex: 1;
            font-family: monospace;
            font-size: 14px;
        }
        .preset-colors {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .preset-color {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        .preset-color:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .rgb-inputs {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .rgb-inputs input {
            flex: 1;
            text-align: center;
        }
        .filter-bar {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0"><i class="bi bi-palette"></i> Настройка цветов товаров</h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span><i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($missing_colors)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Внимание!</strong> Обнаружены цвета в товарах, которые отсутствуют в настройках:
                <strong><?= implode(', ', array_slice($missing_colors, 0, 10)) ?></strong>
                <?php if (count($missing_colors) > 10): ?>
                    и еще <?= count($missing_colors) - 10 ?> цветов...
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-2">
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="action" value="add_missing_colors">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-plus-circle"></i> Добавить недостающие цвета
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Добавить новый цвет</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addColorForm">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label class="form-label">Название цвета (системное)</label>
                                <input type="text" class="form-control" name="color_name" required 
                                       placeholder="например: белый, red, blue">
                                <small class="text-muted">Используется для поиска и фильтрации</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Отображаемое название</label>
                                <input type="text" class="form-control" name="display_name" required 
                                       placeholder="например: Белый, Красный, Синий">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">HEX код цвета</label>
                                <div class="d-flex align-items-center">
                                    <input type="text" class="form-control" name="color_code" 
                                           id="add_color_code" value="#FFFFFF" required placeholder="#FFFFFF" style="flex: 1;">
                                    <div class="color-preview-small" id="addColorPreview" style="background-color: #FFFFFF; cursor: pointer;" onclick="openColorPickerForAdd()"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Порядок сортировки</label>
                                <input type="number" class="form-control" name="sort_order" value="500">
                                <small class="text-muted">Чем меньше число, тем выше в списке</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Добавить цвет
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            <i class="bi bi-lightbulb"></i> Здесь вы можете настроить список доступных цветов для товаров.<br><br>
                            <strong>Цвета используются:</strong><br>
                            • В фильтрации товаров<br>
                            • При отображении кружков цветов на карточках товаров<br>
                            • В модальных окнах деталей товара<br><br>
                            <strong>Советы:</strong><br>
                            • Нажмите на кружок цвета чтобы открыть цветовую палитру<br>
                            • Системное название цвета должно совпадать с названием в базе данных<br>
                            • HEX код должен начинаться с #<br>
                            • Неактивные цвета не будут отображаться в фильтрах
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0"><i class="bi bi-palette"></i> Список цветов</h5>
                        <div class="btn-group">
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="action" value="add_missing_colors">
                                <button type="submit" class="btn btn-outline-success btn-sm me-2" <?= empty($missing_colors) ? 'disabled' : '' ?>>
                                    <i class="bi bi-plus-circle"></i> Добавить недостающие
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Вы уверены, что хотите обновить список цветов? Будут добавлены все цвета из товаров и стандартные цвета. Существующие настройки не будут удалены.');">
                                <input type="hidden" name="action" value="reset_default">
                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-arrow-repeat"></i> Обновить список
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Панель фильтрации и сортировки -->
                        <div class="filter-bar">
                            <form method="GET" class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-search"></i> Поиск по названию</label>
                                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Введите название цвета...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="bi bi-sort-down"></i> Сортировка</label>
                                    <select class="form-select" name="sort">
                                        <option value="default" <?= $sort == 'default' ? 'selected' : '' ?>>По умолчанию</option>
                                        <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>По названию (А-Я)</option>
                                        <option value="usage" <?= $sort == 'usage' ? 'selected' : '' ?>>По количеству товаров</option>
                                        <option value="order" <?= $sort == 'order' ? 'selected' : '' ?>>По порядку сортировки</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table color-table">
                                <thead>
                                    <tr>
                                        <th>Цвет</th>
                                        <th>Системное имя</th>
                                        <th>Отображаемое имя</th>
                                        <th>HEX код</th>
                                        <th>Использование</th>
                                        <th>Сортировка</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($colors as $color): 
                                        $usage = $usage_map[$color['color_name']] ?? 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="color-preview" style="background-color: <?= $color['color_code'] ?>; border: 2px solid <?= $color['color_code'] == '#FFFFFF' ? '#ddd' : $color['color_code'] ?>;"
                                                 onclick="editColor(<?= $color['id'] ?>, '<?= htmlspecialchars($color['color_name']) ?>', '<?= $color['color_code'] ?>', '<?= htmlspecialchars($color['display_name']) ?>', <?= $color['sort_order'] ?>, <?= $color['is_active'] ?>)">
                                            </div>
                                        </td>
                                        <td><code><?= htmlspecialchars($color['color_name']) ?></code></td>
                                        <td><?= htmlspecialchars($color['display_name'] ?: $color['color_name']) ?></td>
                                        <td><code><?= $color['color_code'] ?></code></td>
                                        <td>
                                            <?php if ($usage > 0): ?>
                                                <span class="usage-badge">
                                                    <i class="bi bi-box"></i> <?= $usage ?> товаров
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $color['sort_order'] ?></td>
                                        <td>
                                            <?php if ($color['is_active']): ?>
                                                <span class="badge bg-success">Активен</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Неактивен</span>
                                            <?php endif; ?>
                                         </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-sm-icon" 
                                                    onclick="editColor(<?= $color['id'] ?>, '<?= htmlspecialchars($color['color_name']) ?>', '<?= $color['color_code'] ?>', '<?= htmlspecialchars($color['display_name']) ?>', <?= $color['sort_order'] ?>, <?= $color['is_active'] ?>)"
                                                    title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($usage == 0): ?>
                                                <form method="POST" style="display: inline-block;" 
                                                      onsubmit="return confirm('Вы уверены, что хотите удалить цвет «<?= htmlspecialchars($color['display_name'] ?: $color['color_name']) ?>»?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $color['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-sm-icon" title="Удалить">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary btn-sm-icon" disabled 
                                                        title="Нельзя удалить цвет, который используется в товарах">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                         </td>
                                     </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($colors)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-palette" style="font-size: 48px; color: #dee2e6;"></i>
                                            <p class="mt-3 text-muted">Нет цветов, соответствующих запросу</p>
                                            <a href="?search=&sort=default" class="btn btn-outline-secondary btn-sm">Сбросить фильтр</a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Статистика использования цветов -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Статистика использования цветов</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $top_colors = array_slice($color_usage, 0, 10);
                            foreach ($top_colors as $usage):
                                $color_names = explode(',', $usage['attributes_color']);
                                foreach ($color_names as $cn):
                                    $cn = trim($cn);
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                        $color_info = null;
                                        foreach ($colors as $c) {
                                            if (strtolower($c['color_name']) == strtolower($cn)) {
                                                $color_info = $c;
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="color-preview me-2" style="background-color: <?= $color_info['color_code'] ?? '#CCCCCC' ?>;"></div>
                                        <span class="flex-grow-1"><?= htmlspecialchars($cn) ?></span>
                                        <span class="badge bg-secondary"><?= $usage['count'] ?> товаров</span>
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования цвета с color picker -->
    <div class="modal fade" id="editColorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editColorForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Редактирование цвета</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="color-picker-container">
                                    <div class="text-center mb-3">
                                        <div id="edit_color_preview" class="modal-color-preview" style="background-color: #FFFFFF;" onclick="openColorPickerForEdit()"></div>
                                        <small class="text-muted">Нажмите на круг для выбора цвета</small>
                                    </div>
                                    
                                    <div class="color-picker-canvas" id="colorPickerCanvas" style="display: none;">
                                        <div class="color-spectrum" id="colorSpectrum">
                                            <div class="color-spectrum-saturation" id="colorSaturation"></div>
                                            <div class="color-picker-handle" id="colorHandle"></div>
                                        </div>
                                        <div class="color-hue" id="colorHue">
                                            <div class="color-hue-handle" id="hueHandle"></div>
                                        </div>
                                        <div class="color-opacity" id="colorOpacity" style="display: none;">
                                            <div class="color-opacity-gradient" id="opacityGradient"></div>
                                            <div class="color-opacity-handle" id="opacityHandle"></div>
                                        </div>
                                        
                                        <div class="color-values">
                                            <input type="text" class="form-control" id="hexValue" placeholder="HEX" maxlength="7">
                                            <input type="text" class="form-control" id="rgbValue" placeholder="RGB">
                                        </div>
                                        
                                        <div class="rgb-inputs">
                                            <input type="number" class="form-control" id="rValue" placeholder="R" min="0" max="255">
                                            <input type="number" class="form-control" id="gValue" placeholder="G" min="0" max="255">
                                            <input type="number" class="form-control" id="bValue" placeholder="B" min="0" max="255">
                                        </div>
                                        
                                        <div class="preset-colors" id="presetColors">
                                            <div class="preset-color" style="background-color: #FF0000;" onclick="setColor('#FF0000')"></div>
                                            <div class="preset-color" style="background-color: #00FF00;" onclick="setColor('#00FF00')"></div>
                                            <div class="preset-color" style="background-color: #0000FF;" onclick="setColor('#0000FF')"></div>
                                            <div class="preset-color" style="background-color: #FFFF00;" onclick="setColor('#FFFF00')"></div>
                                            <div class="preset-color" style="background-color: #FF00FF;" onclick="setColor('#FF00FF')"></div>
                                            <div class="preset-color" style="background-color: #00FFFF;" onclick="setColor('#00FFFF')"></div>
                                            <div class="preset-color" style="background-color: #000000;" onclick="setColor('#000000')"></div>
                                            <div class="preset-color" style="background-color: #FFFFFF;" onclick="setColor('#FFFFFF')"></div>
                                            <div class="preset-color" style="background-color: #808080;" onclick="setColor('#808080')"></div>
                                            <div class="preset-color" style="background-color: #8B4513;" onclick="setColor('#8B4513')"></div>
                                            <div class="preset-color" style="background-color: #4A2C2C;" onclick="setColor('#4A2C2C')"></div>
                                            <div class="preset-color" style="background-color: #8B5A2B;" onclick="setColor('#8B5A2B')"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Системное название цвета</label>
                                    <input type="text" class="form-control" name="color_name" id="edit_color_name" required>
                                    <small class="text-muted">Используется для поиска и фильтрации</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Отображаемое название</label>
                                    <input type="text" class="form-control" name="display_name" id="edit_display_name">
                                    <small class="text-muted">Оставьте пустым для использования системного названия</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">HEX код цвета</label>
                                    <div class="d-flex align-items-center">
                                        <input type="text" class="form-control" name="color_code" id="edit_color_code" required style="flex: 1;">
                                        <div class="color-preview-small" id="editColorPreviewSmall" style="background-color: #FFFFFF; cursor: pointer;" onclick="openColorPickerForEdit()"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Порядок сортировки</label>
                                    <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="edit_is_active" value="1">
                                    <label class="form-check-label">Цвет активен</label>
                                    <small class="d-block text-muted">Неактивные цвета не будут отображаться в фильтрах</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Color Picker Variables
        let currentColor = { r: 255, g: 0, b: 0, s: 1, v: 1 };
        let currentHue = 0;
        let isDraggingSpectrum = false;
        let isDraggingHue = false;
        let currentColorCallback = null;
        
        // DOM Elements
        let colorSpectrum, colorSaturation, colorHandle, colorHue, hueHandle;
        let hexValue, rgbValue, rValue, gValue, bValue;
        let colorPickerCanvas;
        
        // Функция для конвертации HSV в RGB
        function hsvToRgb(h, s, v) {
            let r, g, b;
            let i = Math.floor(h * 6);
            let f = h * 6 - i;
            let p = v * (1 - s);
            let q = v * (1 - f * s);
            let t = v * (1 - (1 - f) * s);
            
            switch (i % 6) {
                case 0: r = v; g = t; b = p; break;
                case 1: r = q; g = v; b = p; break;
                case 2: r = p; g = v; b = t; break;
                case 3: r = p; g = q; b = v; break;
                case 4: r = t; g = p; b = v; break;
                default: r = v; g = p; b = q; break;
            }
            
            return { r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255) };
        }
        
        // Конвертация RGB в HSV
        function rgbToHsv(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            let max = Math.max(r, g, b), min = Math.min(r, g, b);
            let h, s, v = max;
            let d = max - min;
            s = max === 0 ? 0 : d / max;
            
            if (max === min) {
                h = 0;
            } else {
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }
            
            return { h: h, s: s, v: v };
        }
        
        // Конвертация HEX в RGB
        function hexToRgb(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex.split('').map(c => c + c).join('');
            }
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);
            return { r: r, g: g, b: b };
        }
        
        // Конвертация RGB в HEX
        function rgbToHex(r, g, b) {
            return '#' + [r, g, b].map(x => {
                const hex = x.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('');
        }
        
        // Обновление цвета на основе текущих HSV значений
        function updateColorFromHsv() {
            let rgb = hsvToRgb(currentHue, currentColor.s, currentColor.v);
            currentColor.r = rgb.r;
            currentColor.g = rgb.g;
            currentColor.b = rgb.b;
            
            updateColorDisplay();
        }
        
        function updateColorFromRgb() {
            let hsv = rgbToHsv(currentColor.r, currentColor.g, currentColor.b);
            currentHue = hsv.h;
            currentColor.s = hsv.s;
            currentColor.v = hsv.v;
            updateColorDisplay();
        }
        
        function updateColorDisplay() {
            let hex = rgbToHex(currentColor.r, currentColor.g, currentColor.b);
            let rgb = `${currentColor.r}, ${currentColor.g}, ${currentColor.b}`;
            
            if (hexValue) hexValue.value = hex;
            if (rgbValue) rgbValue.value = rgb;
            if (rValue) rValue.value = currentColor.r;
            if (gValue) gValue.value = currentColor.g;
            if (bValue) bValue.value = currentColor.b;
            
            // Обновляем превью
            if (document.getElementById('edit_color_preview')) {
                document.getElementById('edit_color_preview').style.backgroundColor = hex;
            }
            if (document.getElementById('editColorPreviewSmall')) {
                document.getElementById('editColorPreviewSmall').style.backgroundColor = hex;
            }
            if (document.getElementById('edit_color_code')) {
                document.getElementById('edit_color_code').value = hex;
            }
            
            // Обновляем фон спектра в зависимости от текущего оттенка
            if (colorSpectrum) {
                let hueColor = hsvToRgb(currentHue, 1, 1);
                colorSpectrum.style.backgroundColor = `rgb(${hueColor.r}, ${hueColor.g}, ${hueColor.b})`;
            }
            
            // Обновляем позицию маркера насыщенности/яркости
            if (colorHandle) {
                let x = currentColor.s * 100;
                let y = (1 - currentColor.v) * 100;
                colorHandle.style.left = `${x}%`;
                colorHandle.style.top = `${y}%`;
            }
            
            if (hueHandle) {
                hueHandle.style.left = `${currentHue * 100}%`;
            }
            
            if (currentColorCallback) {
                currentColorCallback(hex);
            }
        }
        
        function setColor(hex) {
            let rgb = hexToRgb(hex);
            currentColor.r = rgb.r;
            currentColor.g = rgb.g;
            currentColor.b = rgb.b;
            let hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            currentHue = hsv.h;
            currentColor.s = hsv.s;
            currentColor.v = hsv.v;
            updateColorDisplay();
        }
        
        function openColorPicker(callback) {
            currentColorCallback = callback;
            if (colorPickerCanvas) {
                colorPickerCanvas.style.display = 'block';
                updateColorDisplay();
            }
        }
        
        function closeColorPicker() {
            if (colorPickerCanvas) {
                colorPickerCanvas.style.display = 'none';
            }
            currentColorCallback = null;
        }
        
        function openColorPickerForEdit() {
            if (colorPickerCanvas) {
                if (colorPickerCanvas.style.display === 'none') {
                    colorPickerCanvas.style.display = 'block';
                } else {
                    colorPickerCanvas.style.display = 'none';
                }
            }
        }
        
        function openColorPickerForAdd() {
            let hex = document.getElementById('add_color_code').value;
            setColor(hex);
            openColorPicker((newHex) => {
                document.getElementById('add_color_code').value = newHex;
                document.getElementById('addColorPreview').style.backgroundColor = newHex;
            });
        }
        
        // Инициализация color picker
        function initColorPicker() {
            colorSpectrum = document.getElementById('colorSpectrum');
            colorSaturation = document.getElementById('colorSaturation');
            colorHandle = document.getElementById('colorHandle');
            colorHue = document.getElementById('colorHue');
            hueHandle = document.getElementById('hueHandle');
            hexValue = document.getElementById('hexValue');
            rgbValue = document.getElementById('rgbValue');
            rValue = document.getElementById('rValue');
            gValue = document.getElementById('gValue');
            bValue = document.getElementById('bValue');
            colorPickerCanvas = document.getElementById('colorPickerCanvas');
            
            if (!colorSpectrum) return;
            
            // События для спектра (насыщенность/яркость)
            function updateSpectrumPosition(e) {
                let rect = colorSpectrum.getBoundingClientRect();
                let x = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
                let y = Math.min(1, Math.max(0, (e.clientY - rect.top) / rect.height));
                currentColor.s = x;
                currentColor.v = 1 - y;
                updateColorFromHsv();
            }
            
            colorSpectrum.addEventListener('mousedown', (e) => {
                isDraggingSpectrum = true;
                updateSpectrumPosition(e);
                document.addEventListener('mousemove', onSpectrumMove);
                document.addEventListener('mouseup', onSpectrumUp);
            });
            
            function onSpectrumMove(e) {
                if (isDraggingSpectrum) {
                    updateSpectrumPosition(e);
                }
            }
            
            function onSpectrumUp() {
                isDraggingSpectrum = false;
                document.removeEventListener('mousemove', onSpectrumMove);
                document.removeEventListener('mouseup', onSpectrumUp);
            }
            
            // События для Hue
            function updateHuePosition(e) {
                let rect = colorHue.getBoundingClientRect();
                let x = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
                currentHue = x;
                updateColorFromHsv();
            }
            
            colorHue.addEventListener('mousedown', (e) => {
                isDraggingHue = true;
                updateHuePosition(e);
                document.addEventListener('mousemove', onHueMove);
                document.addEventListener('mouseup', onHueUp);
            });
            
            function onHueMove(e) {
                if (isDraggingHue) {
                    updateHuePosition(e);
                }
            }
            
            function onHueUp() {
                isDraggingHue = false;
                document.removeEventListener('mousemove', onHueMove);
                document.removeEventListener('mouseup', onHueUp);
            }
            
            // События для HEX input
            if (hexValue) {
                hexValue.addEventListener('change', () => {
                    let hex = hexValue.value;
                    if (hex.match(/^#[0-9A-Fa-f]{6}$/)) {
                        setColor(hex);
                    }
                });
            }
            
            // События для RGB inputs
            if (rValue && gValue && bValue) {
                [rValue, gValue, bValue].forEach(input => {
                    input.addEventListener('change', () => {
                        currentColor.r = Math.min(255, Math.max(0, parseInt(rValue.value) || 0));
                        currentColor.g = Math.min(255, Math.max(0, parseInt(gValue.value) || 0));
                        currentColor.b = Math.min(255, Math.max(0, parseInt(bValue.value) || 0));
                        updateColorFromRgb();
                    });
                });
            }
            
            setColor('#FF0000');
        }
        
        // Функция редактирования цвета
        function editColor(id, colorName, colorCode, displayName, sortOrder, isActive) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_color_name').value = colorName;
            document.getElementById('edit_display_name').value = displayName || '';
            document.getElementById('edit_color_code').value = colorCode;
            document.getElementById('edit_sort_order').value = sortOrder;
            document.getElementById('edit_is_active').checked = isActive == 1;
            
            // Устанавливаем текущий цвет в пикер
            setColor(colorCode);
            
            // Закрываем color picker при открытии модального окна
            if (colorPickerCanvas) {
                colorPickerCanvas.style.display = 'none';
            }
            
            // Устанавливаем callback для сохранения цвета
            currentColorCallback = (newHex) => {
                document.getElementById('edit_color_code').value = newHex;
                document.getElementById('edit_color_preview').style.backgroundColor = newHex;
                document.getElementById('editColorPreviewSmall').style.backgroundColor = newHex;
            };
            
            // Открываем модальное окно
            const modal = new bootstrap.Modal(document.getElementById('editColorModal'));
            modal.show();
            
            // При закрытии модального окна сбрасываем callback
            document.getElementById('editColorModal').addEventListener('hidden.bs.modal', function() {
                currentColorCallback = null;
                if (colorPickerCanvas) {
                    colorPickerCanvas.style.display = 'none';
                }
            }, { once: true });
        }
        
        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            initColorPicker();
            
            // Предпросмотр цвета для добавления
            const addColorCode = document.getElementById('add_color_code');
            const addColorPreview = document.getElementById('addColorPreview');
            
            if (addColorCode) {
                addColorCode.addEventListener('input', function(e) {
                    let color = this.value;
                    if (color.match(/^#[0-9A-Fa-f]{6}$/)) {
                        addColorPreview.style.backgroundColor = color;
                    }
                });
            }
        });
    </script>
</body>
</html>