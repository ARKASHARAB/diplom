<?php
// index.php - главная страница каталога с разделением на двери и фурнитуру
require_once 'config.php';
require_once 'auth.php';

// Функция для экранирования данных для JavaScript
function escapeJs($data) {
    if (is_null($data)) return '';
    $data = str_replace(array("\r", "\n"), ' ', $data);
    $data = str_replace("'", "\\'", $data);
    $data = str_replace('"', '\\"', $data);
    $data = str_replace("\\", "\\\\", $data);
    return $data;
}

// Функция для очистки размера от скобок и кавычек
function cleanSize($size) {
    if (!$size) return '';
    // Удаляем кавычки, скобки и лишние пробелы
    $size = trim($size);
    $size = str_replace(['"', "'", '(', ')', '[', ']', '{', '}'], '', $size);
    return $size;
}

// Определяем, какой раздел активен (двери или фурнитура)
$section = isset($_GET['section']) ? $_GET['section'] : 'doors'; // doors - двери, hardware - фурнитура

// Получаем параметры из URL
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$category_id = isset($_GET['categoryId']) ? clean($_GET['categoryId']) : '';
$category_name = isset($_GET['category_name']) ? clean($_GET['category_name']) : '';
$color = isset($_GET['color']) ? clean($_GET['color']) : '';
$manufacturer = isset($_GET['manufacturer']) ? clean($_GET['manufacturer']) : '';
$style = isset($_GET['style']) ? clean($_GET['style']) : '';
$glazing = isset($_GET['glazing']) ? clean($_GET['glazing']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'price_asc';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$door_type = isset($_GET['door_type']) ? clean($_GET['door_type']) : '';
$selected_size = isset($_GET['size']) ? clean($_GET['size']) : '';

// Подключаемся к БД
$db = getDB();

// Формируем SQL запрос с фильтрами
$where = [];
$params = [];

// УСЛОВИЕ РАЗДЕЛЕНИЯ: двери - category_name начинается с "серия"
if ($section === 'doors') {
    $where[] = "category_name LIKE 'серия%'";
} else {
    $where[] = "(category_name NOT LIKE 'серия%' OR category_name IS NULL)";
}

// Базовые фильтры
if ($category_id) {
    $where[] = "categoryId = :categoryId";
    $params[':categoryId'] = $category_id;
}

if ($category_name) {
    $where[] = "category_name = :category_name";
    $params[':category_name'] = $category_name;
}

if ($color) {
    $where[] = "FIND_IN_SET(:color, attributes_color)";
    $params[':color'] = $color;
}

if ($manufacturer) {
    $where[] = "attributes_manufacturer = :manufacturer";
    $params[':manufacturer'] = $manufacturer;
}

if ($style) {
    $where[] = "attributes_style = :style";
    $params[':style'] = $style;
}

if ($glazing) {
    $where[] = "attributes_glazing = :glazing";
    $params[':glazing'] = $glazing;
}

if ($door_type) {
    $where[] = "attributes_door_type = :door_type";
    $params[':door_type'] = $door_type;
}

if ($selected_size) {
    $where[] = "attributes_size LIKE :size";
    $params[':size'] = "%$selected_size%";
}

if ($search) {
    $where[] = "(name LIKE :search OR description LIKE :search OR article LIKE :search OR category_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($min_price > 0) {
    $where[] = "price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price > 0) {
    $where[] = "price <= :max_price";
    $params[':max_price'] = $max_price;
}

// Определяем минимальную и максимальную цену для фильтра (с учетом раздела)
$price_sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM " . TABLE_PRODUCTS;
$price_where = "";
if ($section === 'doors') {
    $price_where = " WHERE category_name LIKE 'серия%'";
} else {
    $price_where = " WHERE (category_name NOT LIKE 'серия%' OR category_name IS NULL)";
}
$price_stats = $db->query($price_sql . $price_where)->fetch();
$global_min_price = floor(($price_stats['min_price'] ?? 0) / 1000) * 1000;
$global_max_price = ceil(($price_stats['max_price'] ?? 100000) / 1000) * 1000;

if ($min_price == 0) $min_price = $global_min_price;
if ($max_price == 0) $max_price = $global_max_price;

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ГРУППИРУЕМ ПО ИМЕНИ (и другим ключевым полям)
$group_sql = "SELECT 
                name,
                category_name,
                categoryId,
                MIN(price) as min_price,
                MAX(price) as max_price,
                SUM(quantity) as total_quantity,
                GROUP_CONCAT(DISTINCT attributes_color ORDER BY attributes_color SEPARATOR '||') as colors,
                GROUP_CONCAT(DISTINCT attributes_size ORDER BY attributes_size SEPARATOR '||') as all_sizes,
                GROUP_CONCAT(DISTINCT id ORDER BY id SEPARATOR ',') as variant_ids,
                GROUP_CONCAT(DISTINCT article ORDER BY article SEPARATOR ',') as articles
              FROM " . TABLE_PRODUCTS . " 
              $where_sql 
              GROUP BY name, category_name, categoryId";

// Сортировка для групп
$sort_sql = match($sort) {
    'price_desc' => 'ORDER BY min_price DESC',
    'name_asc' => 'ORDER BY name ASC',
    'name_desc' => 'ORDER BY name DESC',
    'price_asc' => 'ORDER BY min_price ASC',
    default => 'ORDER BY min_price ASC'
};

// Пагинация
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Получаем сгруппированные товары
$group_stmt = $db->prepare($group_sql . " " . $sort_sql . " LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $group_stmt->bindValue($key, $value);
}
$group_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$group_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$group_stmt->execute();
$grouped_products = $group_stmt->fetchAll();

// Получаем общее количество уникальных групп для пагинации
$count_sql = "SELECT COUNT(DISTINCT CONCAT(name, '||', category_name, '||', categoryId)) as total FROM " . TABLE_PRODUCTS . " $where_sql";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / ITEMS_PER_PAGE);

// Для каждой группы собираем все варианты
$products_with_variants = [];
foreach ($grouped_products as $group) {
    // Получаем все варианты этой группы
    $variant_ids = explode(',', $group['variant_ids']);
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
    $variants_sql = "SELECT * FROM " . TABLE_PRODUCTS . " WHERE id IN ($placeholders)";
    $variants_stmt = $db->prepare($variants_sql);
    $variants_stmt->execute($variant_ids);
    $variants = $variants_stmt->fetchAll();
    
    // Собираем уникальные цвета и размеры
    $colors = [];
    $sizes = [];
    $images_by_color = [];
    
    foreach ($variants as $variant) {
        // Обрабатываем цвета (могут быть через запятую)
        if ($variant['attributes_color']) {
            $color_array = explode(',', $variant['attributes_color']);
            foreach ($color_array as $c) {
                $c = trim($c);
                if ($c && !in_array($c, $colors)) {
                    $colors[] = $c;
                }
            }
        }
        
        // Обрабатываем размеры - очищаем от скобок и кавычек
        if ($variant['attributes_size']) {
            $size_array = explode(',', $variant['attributes_size']);
            foreach ($size_array as $s) {
                $s = cleanSize($s);
                if ($s && !in_array($s, $sizes)) {
                    $sizes[] = $s;
                }
            }
        }
        
        // Группируем изображения по цвету
        if ($variant['attributes_color'] && $variant['picture']) {
            $color_array = explode(',', $variant['attributes_color']);
            foreach ($color_array as $c) {
                $c = trim($c);
                if ($c) {
                    if (!isset($images_by_color[$c])) {
                        $images_by_color[$c] = [];
                    }
                    if (!in_array($variant['picture'], $images_by_color[$c])) {
                        $images_by_color[$c][] = $variant['picture'];
                    }
                }
            }
        }
    }
    
    // Сортируем размеры естественным образом
    usort($sizes, function($a, $b) {
        // Извлекаем числа из строк
        preg_match_all('/\d+/', $a, $a_nums);
        preg_match_all('/\d+/', $b, $b_nums);
        $a_val = !empty($a_nums[0]) ? intval($a_nums[0][0]) : 0;
        $b_val = !empty($b_nums[0]) ? intval($b_nums[0][0]) : 0;
        return $a_val - $b_val;
    });
    
    // Выбираем основное изображение
    $main_image = '';
    if (!empty($images_by_color)) {
        $first_color = array_keys($images_by_color)[0];
        $main_image = $images_by_color[$first_color][0] ?? '';
    }
    if (!$main_image && !empty($variants)) {
        $main_image = $variants[0]['picture'] ?? '';
    }
    
    $products_with_variants[] = [
        'name' => $group['name'],
        'category_name' => $group['category_name'],
        'categoryId' => $group['categoryId'],
        'min_price' => floatval($group['min_price']),
        'max_price' => floatval($group['max_price']),
        'total_quantity' => intval($group['total_quantity']),
        'colors' => $colors,
        'sizes' => $sizes,
        'main_image' => $main_image,
        'images_by_color' => $images_by_color,
        'variants' => $variants,
        'articles' => explode(',', $group['articles'])
    ];
}

// Получаем фильтры для сайдбара (с учетом раздела)
$filter_where = "";
if ($section === 'doors') {
    $filter_where = " WHERE category_name LIKE 'серия%'";
} else {
    $filter_where = " WHERE (category_name NOT LIKE 'серия%' OR category_name IS NULL)";
}

$colors_list = $db->query("SELECT DISTINCT attributes_color FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_color IS NOT NULL AND attributes_color != '' ORDER BY attributes_color")->fetchAll(PDO::FETCH_COLUMN);
$manufacturers = $db->query("SELECT DISTINCT attributes_manufacturer FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_manufacturer IS NOT NULL AND attributes_manufacturer != '' ORDER BY attributes_manufacturer")->fetchAll(PDO::FETCH_COLUMN);
$categories_name = $db->query("SELECT DISTINCT category_name FROM " . TABLE_PRODUCTS . $filter_where . " AND category_name IS NOT NULL AND category_name != '' ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);
$styles = $db->query("SELECT DISTINCT attributes_style FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_style IS NOT NULL AND attributes_style != '' ORDER BY attributes_style")->fetchAll(PDO::FETCH_COLUMN);
$glazing_types = $db->query("SELECT DISTINCT attributes_glazing FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_glazing IS NOT NULL AND attributes_glazing != '' ORDER BY attributes_glazing")->fetchAll(PDO::FETCH_COLUMN);
$door_types = $db->query("SELECT DISTINCT attributes_door_type FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_door_type IS NOT NULL AND attributes_door_type != '' ORDER BY attributes_door_type")->fetchAll(PDO::FETCH_COLUMN);
$sizes_list = $db->query("SELECT DISTINCT attributes_size FROM " . TABLE_PRODUCTS . $filter_where . " AND attributes_size IS NOT NULL AND attributes_size != '' ORDER BY attributes_size")->fetchAll(PDO::FETCH_COLUMN);

// Очищаем размеры в списке фильтров
$clean_sizes_list = [];
foreach ($sizes_list as $size_option) {
    $size_parts = explode(',', $size_option);
    foreach ($size_parts as $part) {
        $clean = cleanSize($part);
        if ($clean && !in_array($clean, $clean_sizes_list)) {
            $clean_sizes_list[] = $clean;
        }
    }
}
sort($clean_sizes_list);

// Получаем количество товаров в корзине
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Функция для генерации параметров URL
function getActiveParams($exclude = []) {
    $params = [];
    
    $get_params = [
        'section' => 'section',
        'categoryId' => 'categoryId',
        'category_name' => 'category_name',
        'color' => 'color',
        'manufacturer' => 'manufacturer',
        'style' => 'style',
        'glazing' => 'glazing',
        'door_type' => 'door_type',
        'size' => 'size',
        'search' => 'search',
        'min_price' => 'min_price',
        'max_price' => 'max_price',
        'sort' => 'sort',
        'page' => 'page'
    ];
    
    foreach ($get_params as $key => $param) {
        if (isset($_GET[$key]) && !in_array($key, $exclude)) {
            if ($key == 'min_price' && $_GET[$key] > 0) {
                $params[] = $key . '=' . floatval($_GET[$key]);
            } elseif ($key == 'max_price' && $_GET[$key] > 0) {
                $params[] = $key . '=' . floatval($_GET[$key]);
            } elseif ($key != 'min_price' && $key != 'max_price' && $_GET[$key] != '') {
                $params[] = $key . '=' . urlencode($_GET[$key]);
            }
        }
    }
    
    return $params ? '&' . implode('&', $params) : '';
}

// Функция для удаления параметра из URL
function removeUrlParam($param) {
    $url = 'index.php?';
    $params = $_GET;
    
    if (is_array($param)) {
        foreach ($param as $p) {
            unset($params[$p]);
        }
    } else {
        unset($params[$param]);
    }
    
    $params = array_filter($params, function($value) {
        return $value !== '';
    });
    
    return $url . http_build_query($params);
}

// Функция для получения кода цвета
// Функция для получения кода цвета из базы данных
function getColorCode($colorName) {
    global $db;
    static $color_cache = [];
    
    if (!$colorName) return '#CCCCCC';
    
    // Проверяем кэш
    if (isset($color_cache[$colorName])) {
        return $color_cache[$colorName];
    }
    
    // Ищем цвет в базе
    $stmt = $db->prepare("SELECT color_code FROM admindoor1_color_settings WHERE color_name = ? OR display_name = ?");
    $stmt->execute([$colorName, $colorName]);
    $color = $stmt->fetch();
    
    if ($color) {
        $color_cache[$colorName] = $color['color_code'];
        return $color['color_code'];
    }
    
    // Если не нашли, возвращаем стандартный серый
    $color_cache[$colorName] = '#CCCCCC';
    return '#CCCCCC';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= $section === 'doors' ? 'Двери' : 'Фурнитура' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 1px solid #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 220px;
            object-fit: contain;
            background: #fff;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .price {
            color: #d32f2f;
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .filter-sidebar {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 90px;
        }
        
        .badge-custom {
            font-size: 0.7rem;
            padding: 3px 8px;
            margin-right: 3px;
        }
        
        .quantity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        
        .noUi-connect {
            background: #3D3D3D;
        }
        
        .price-slider {
            margin: 20px 0;
        }
        
        .price-values {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .card-title {
            font-size: 1rem;
            height: 1em;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .active-filters {
            background: #e7f1ff;
            border-left: 4px solid #3D3D3D;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .filter-tag {
            background: #3D3D3D;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
        }
        
        .filter-tag i {
            margin-left: 5px;
            cursor: pointer;
        }
        
        .filter-tag a {
            color: white;
            text-decoration: none;
        }
        
        .category-badge {
            background: #6f42c1;
            color: white;
        }
        
        .section-tabs {
            margin-bottom: 25px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-tab {
            display: inline-block;
            padding: 12px 24px;
            font-size: 1.1rem;
            font-weight: 500;
            color: #6c757d;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .section-tab:hover {
            color: #3D3D3D;
            border-bottom-color: #3D3D3D;
        }
        
        .section-tab.active {
            color: #3D3D3D;
            border-bottom-color: #3D3D3D;
            font-weight: 600;
        }
        
        .article-code {
            font-family: monospace;
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85rem;
        }
        
        .selected-color-name {
            font-family: monospace;
            background: #e8f0fe;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85rem;
            color: #3D3D3D;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: #3D3D3D;
            border-color: #3D3D3D;
        }
        
        .btn-primary:hover {
            background-color: #2d2d2d;
            border-color: #2d2d2d;
        }
        
        .page-item.active .page-link {
            background-color: #3D3D3D;
            border-color: #3D3D3D;
        }
        
        .page-link {
            color: #3D3D3D;
        }
        
        /* Стили для цветов и размеров */
        .color-selector, .size-selector {
            margin-bottom: 12px;
        }
        
        .color-options, .size-options {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            margin-top: 5px;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 8px;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }
        
        .color-options::-webkit-scrollbar, .size-options::-webkit-scrollbar {
            height: 4px;
        }
        
        .color-options::-webkit-scrollbar-track, .size-options::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .color-options::-webkit-scrollbar-thumb, .size-options::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .color-options::-webkit-scrollbar-thumb:hover, .size-options::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .color-circle {
            margin-left: 60%;
            margin-top: 40%;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }
        
        .color-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .color-circle.selected {
            border: 3px solid #3D3D3D;
            transform: scale(1.05);
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #3D3D3D;
        }
        
        .color-name {
            font-size: 0.65rem;
            text-align: center;
            margin-top: 3px;
            color: #666;
            white-space: nowrap;
        }
        
        .color-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        /* Стили для размеров */
        .size-option {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            padding: 6px 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
            font-weight: 500;
            color: #495057;
            flex-shrink: 0;
        }
        
        .size-option:hover {
            background: #e9ecef;
            border-color: #3D3D3D;
            transform: translateY(-1px);
        }
        
        .size-option.selected {
            background: #3D3D3D;
            border-color: #3D3D3D;
            color: white;
        }
        
        .more-colors, .more-sizes {
            font-size: 0.7rem;
            color: #888;
            background: #f0f0f0;
            padding: 6px 10px;
            border-radius: 20px;
            cursor: pointer;
            text-align: center;
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .more-sizes {
            min-width: 45px;
        }
        
        .option-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Модальное окно */
        .modal-gallery {
            position: relative;
        }
        
        .modal-main-image {
            width: 100%;
            height: 350px;
            object-fit: contain;
            background: #fafafa;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        
        .modal-thumbnails {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        
        .modal-thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .modal-thumb.active {
            border-color: #3D3D3D;
        }
        
        .color-option-btn, .size-option-btn-modal {
            padding: 8px 16px;
            border: 2px solid #ddd;
            border-radius: 30px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            margin: 4px;
        }
        
        .color-option-btn:hover,
        .color-option-btn.active,
        .size-option-btn-modal:hover,
        .size-option-btn-modal.active {
            background: #3D3D3D;
            color: white;
            border-color: #3D3D3D;
        }
        
        @media (max-width: 768px) {
            .filter-sidebar {
                position: static;
                margin-bottom: 20px;
            }
            
            .modal-main-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php';?>

    <div class="main-container">
        <div class="container mt-4">
            <!-- Переключатель разделов: Двери / Фурнитура -->
            <div class="section-tabs">
                <a href="?section=doors<?= getActiveParams(['section', 'page']) ?>" class="section-tab <?= $section === 'doors' ? 'active' : '' ?>">
                    <i class="bi bi-door-open"></i> Двери
                </a>
                <a href="?section=hardware<?= getActiveParams(['section', 'page']) ?>" class="section-tab <?= $section === 'hardware' ? 'active' : '' ?>">
                    <i class="bi bi-tools"></i> Фурнитура
                </a>
            </div>
            
            <!-- Активные фильтры -->
            <?php if ($category_id || $category_name || $color || $manufacturer || $style || $glazing || $door_type || $selected_size || $search || $min_price != $global_min_price || $max_price != $global_max_price): ?>
                <div class="active-filters">
                    <h6><i class="bi bi-funnel-fill"></i> Активные фильтры:</h6>
                    <div class="d-flex flex-wrap align-items-center">
                        <?php if ($search): ?>
                            <span class="filter-tag">
                                Поиск: "<?= htmlspecialchars($search) ?>"
                                <a href="<?= removeUrlParam('search') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($category_id): ?>
                            <span class="filter-tag">
                                ID категории: <?= htmlspecialchars($category_id) ?>
                                <a href="<?= removeUrlParam('categoryId') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($category_name): ?>
                            <span class="filter-tag">
                                Категория: <?= htmlspecialchars($category_name) ?>
                                <a href="<?= removeUrlParam('category_name') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($color): ?>
                            <span class="filter-tag">
                                Цвет: <?= htmlspecialchars($color) ?>
                                <a href="<?= removeUrlParam('color') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($manufacturer): ?>
                            <span class="filter-tag">
                                Производитель: <?= htmlspecialchars($manufacturer) ?>
                                <a href="<?= removeUrlParam('manufacturer') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($style): ?>
                            <span class="filter-tag">
                                Стиль: <?= htmlspecialchars($style) ?>
                                <a href="<?= removeUrlParam('style') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($glazing): ?>
                            <span class="filter-tag">
                                Остекление: <?= htmlspecialchars($glazing) ?>
                                <a href="<?= removeUrlParam('glazing') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($door_type): ?>
                            <span class="filter-tag">
                                Тип двери: <?= htmlspecialchars($door_type) ?>
                                <a href="<?= removeUrlParam('door_type') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($selected_size): ?>
                            <span class="filter-tag">
                                Размер: <?= htmlspecialchars($selected_size) ?>
                                <a href="<?= removeUrlParam('size') ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <?php if ($min_price != $global_min_price || $max_price != $global_max_price): ?>
                            <span class="filter-tag">
                                Цена: <?= number_format($min_price, 0, ',', ' ') ?> - <?= number_format($max_price, 0, ',', ' ') ?> руб.
                                <a href="<?= removeUrlParam(['min_price', 'max_price']) ?>"><i class="bi bi-x"></i></a>
                            </span>
                        <?php endif; ?>
                        <a href="index.php?section=<?= $section ?>" class="btn btn-outline-danger btn-sm ms-auto">
                            <i class="bi bi-x-circle"></i> Сбросить все
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Сайдбар с фильтрами -->
                <div class="col-lg-3 mb-4">
                    <div class="filter-sidebar">
                        <h5 class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="bi bi-funnel"></i> Фильтры</span>
                            <span class="badge bg-secondary"><?= $total_items ?></span>
                        </h5>
                        
                        <form method="GET" action="index.php" id="filterForm">
                            <input type="hidden" name="section" value="<?= $section ?>">
                            <input type="hidden" name="sort" id="sortField" value="<?= htmlspecialchars($sort) ?>">
                            
                            <!-- Поиск -->
                            <div class="mb-3">
                                <label class="form-label">Поиск</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Название, артикул..." value="<?= htmlspecialchars($search) ?>"
                                       onchange="this.form.submit()">
                            </div>
                            
                            <!-- Фильтр по цене -->
                            <div class="mb-3">
                                <label class="form-label">Цена, руб.</label>
                                <div id="price-slider" class="price-slider"></div>
                                <div class="price-values">
                                    <input type="number" id="min-price" name="min_price" 
                                           class="form-control form-control-sm" style="width: 100px;"
                                           value="<?= $min_price ?>"
                                           onchange="this.form.submit()">
                                    <span>—</span>
                                    <input type="number" id="max-price" name="max_price" 
                                           class="form-control form-control-sm" style="width: 100px;"
                                           value="<?= $max_price ?>"
                                           onchange="this.form.submit()">
                                </div>
                            </div>
                            
                            <!-- Фильтр по категории -->
                            <div class="mb-3">
                                <label class="form-label">Категория</label>
                                <select name="category_name" class="form-select" onchange="updateCategorySort(this.value)">
                                    <option value="">Все категории</option>
                                    <?php foreach ($categories_name as $cat_name): ?>
                                        <option value="<?= htmlspecialchars($cat_name) ?>" <?= ($category_name == $cat_name) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat_name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по цвету -->
                            <div class="mb-3">
                                <label class="form-label">Цвет</label>
                                <select name="color" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все цвета</option>
                                    <?php foreach ($colors_list as $color_option): ?>
                                        <?php 
                                        $color_parts = explode(',', $color_option);
                                        foreach ($color_parts as $part): 
                                            $part = trim($part);
                                        ?>
                                            <option value="<?= htmlspecialchars($part) ?>" <?= ($color == $part) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($part) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по размеру -->
                            <div class="mb-3">
                                <label class="form-label">Размер</label>
                                <select name="size" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все размеры</option>
                                    <?php foreach ($clean_sizes_list as $size_option): ?>
                                        <option value="<?= htmlspecialchars($size_option) ?>" <?= ($selected_size == $size_option) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($size_option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по производителю -->
                            <div class="mb-3">
                                <label class="form-label">Производитель</label>
                                <select name="manufacturer" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все производители</option>
                                    <?php foreach ($manufacturers as $manufacturer_option): ?>
                                        <option value="<?= htmlspecialchars($manufacturer_option) ?>" <?= ($manufacturer == $manufacturer_option) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($manufacturer_option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по стилю (только для дверей) -->
                            <?php if ($section === 'doors'): ?>
                            <div class="mb-3">
                                <label class="form-label">Стиль</label>
                                <select name="style" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все стили</option>
                                    <?php foreach ($styles as $style_option): ?>
                                        <option value="<?= htmlspecialchars($style_option) ?>" <?= ($style == $style_option) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($style_option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по типу двери -->
                            <div class="mb-3">
                                <label class="form-label">Тип двери</label>
                                <select name="door_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все типы</option>
                                    <?php foreach ($door_types as $door_type_option): ?>
                                        <option value="<?= htmlspecialchars($door_type_option) ?>" <?= ($door_type == $door_type_option) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($door_type_option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Фильтр по остеклению -->
                            <div class="mb-3">
                                <label class="form-label">Остекление</label>
                                <select name="glazing" class="form-select" onchange="this.form.submit()">
                                    <option value="">Все типы</option>
                                    <?php foreach ($glazing_types as $glazing_option): ?>
                                        <option value="<?= htmlspecialchars($glazing_option) ?>" <?= ($glazing == $glazing_option) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($glazing_option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </form>
                        
                        <!-- Статистика -->
                        <div class="alert alert-light border mt-3">
                            <h6><i class="bi bi-info-circle"></i> Статистика</h6>
                            <p class="mb-1 small">Моделей: <strong><?= $total_items ?></strong></p>
                            <p class="mb-1 small">Категорий: <strong><?= count($categories_name) ?></strong></p>
                            <p class="mb-1 small">Цветов: <strong><?= count($colors_list) ?></strong></p>
                            <p class="mb-0 small">Производителей: <strong><?= count($manufacturers) ?></strong></p>
                        </div>
                    </div>
                </div>

                <!-- Основной контент -->
                <div class="col-lg-9">
                    <!-- Заголовок с сортировкой -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="mb-0">
                                <?php if ($category_name): ?>
                                    <?= $section === 'doors' ? 'Двери' : 'Фурнитура' ?>: "<?= htmlspecialchars($category_name) ?>"
                                <?php elseif ($category_id): ?>
                                    <?= $section === 'doors' ? 'Двери' : 'Фурнитура' ?> ID: <?= htmlspecialchars($category_id) ?>
                                <?php else: ?>
                                    <?= $section === 'doors' ? 'Двери' : 'Фурнитура' ?>
                                <?php endif; ?>
                            </h3>
                            <small class="text-muted">Найдено моделей: <?= $total_items ?></small>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="sortSelect" onchange="updateSorting(this.value)">
                                <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : '' ?>>По цене (дешевле)</option>
                                <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : '' ?>>По цене (дороже)</option>
                                <option value="name_asc" <?= ($sort == 'name_asc') ? 'selected' : '' ?>>По названию (А-Я)</option>
                                <option value="name_desc" <?= ($sort == 'name_desc') ? 'selected' : '' ?>>По названию (Я-А)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Карточки товаров (сгруппированные) -->
                    <?php if ($products_with_variants): ?>
                        <div class="row g-3" id="productsContainer">
                            <?php foreach ($products_with_variants as $product): ?>
                                <?php $productHash = md5($product['name'] . $product['category_name']); ?>
                                <div class="col-md-6 col-xl-4 product-item">
                                    <div class="card product-card h-100">
                                        <!-- Бейдж наличия -->
                                        <?php if ($product['total_quantity'] > 0): ?>
                                            <span class="badge bg-success quantity-badge">
                                                <i class="bi bi-check-circle"></i> 
                                                <?php if ($product['total_quantity'] < 10): ?>
                                                    <?= $product['total_quantity'] ?> шт.
                                                <?php else: ?>
                                                    В наличии
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger quantity-badge">
                                                <i class="bi bi-x-circle"></i> Нет в наличии
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Изображение -->
                                        <div class="position-relative">
                                            <img src="<?= htmlspecialchars($product['main_image'] ?: 'https://via.placeholder.com/400x300?text=Нет+изображения') ?>" 
                                                 class="card-img-top product-image" 
                                                 id="productImage_<?= $productHash ?>"
                                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                                 onerror="this.src='https://via.placeholder.com/400x300?text=Нет+изображения'">
                                        </div>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <!-- Категория -->
                                            <div class="mb-2">
                                                <span class="badge category-badge badge-custom">
                                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Название -->
                                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                                                                            <?php if (!empty($product['articles'])): ?>
                                                    <div>
                                                        <small class="article-code">Арт.: <?= htmlspecialchars($product['articles'][0]) ?></small>
                                                        <?php if (count($product['articles']) > 1): ?>
                                                            <small class="text-muted ms-1">+<?= count($product['articles']) - 1 ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                                                                    <div class="option-label">
                                                        <i class="bi bi-palette"></i> Цвет:
                                                    </div>
                                            <!-- Выбранный цвет и артикулы -->
                                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="selected-color-name" id="selectedColorName_<?= $productHash ?>">
                                                        <?= !empty($product['colors']) ? htmlspecialchars($product['colors'][0]) : '' ?>
                                                    </span>
                                                </div>

                                            </div>
                                            
                                            <!-- Выбор цвета - кружки с горизонтальным скроллом -->
                                            <?php if (!empty($product['colors'])): ?>
                                                <div class="color-selector">

                                                    <div class="color-options" id="colorOptions_<?= $productHash ?>">
                                                        <?php 
                                                        foreach ($product['colors'] as $color_option): 
                                                        ?>
                                                            <div class="color-item" onclick="selectProductColor(this, '<?= $productHash ?>', '<?= htmlspecialchars($color_option) ?>', <?= htmlspecialchars(json_encode($product['images_by_color'], JSON_UNESCAPED_UNICODE)) ?>)">
                                                                <div class="color-circle" style="background-color: <?= getColorCode($color_option) ?>; border: 2px solid <?= getColorCode($color_option) == '#FFFFFF' ? '#ddd' : getColorCode($color_option) ?>;"></div>
                                                                <div class="color-name" style="display: none;"><?= htmlspecialchars(mb_substr($color_option, 0, 8)) ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="hidden" class="selected-color" id="selectedColor_<?= $productHash ?>" value="<?= htmlspecialchars($product['colors'][0] ?? '') ?>">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Выбор размера - кнопки с горизонтальным скроллом -->
                                            <?php if (!empty($product['sizes'])): ?>
                                                <div class="size-selector">
                                                    <div class="option-label">
                                                        <i class="bi bi-arrows-angle-expand"></i> Размер:
                                                    </div>
                                                    <div class="size-options" id="sizeOptions_<?= $productHash ?>">
                                                        <?php foreach ($product['sizes'] as $size_option): ?>
                                                            <div class="size-option" onclick="selectProductSize(this, '<?= $productHash ?>', '<?= htmlspecialchars($size_option) ?>')">
                                                                <?= htmlspecialchars($size_option) ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="hidden" class="selected-size" id="selectedSize_<?= $productHash ?>" value="<?= htmlspecialchars($product['sizes'][0] ?? '') ?>">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Цена -->
                                            <div class="price mt-auto mb-2" id="price_<?= $productHash ?>">
                                                от <?= number_format($product['min_price'], 0, ',', ' ') ?> ₽
                                                <?php if ($product['max_price'] > $product['min_price']): ?>
                                                    <small class="text-muted">до <?= number_format($product['max_price'], 0, ',', ' ') ?> ₽</small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Кнопки действий -->
                                            <div class="d-flex gap-2 mt-2">
                                                <button class="btn btn-primary btn-sm flex-fill add-to-cart-btn"
                                                        data-product-id="<?= $product['variants'][0]['id'] ?? '' ?>"
                                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                        data-product-category="<?= htmlspecialchars($product['category_name']) ?>"
                                                        data-product-hash="<?= $productHash ?>"
                                                        data-variants='<?= json_encode($product['variants'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'>
                                                    <i class="bi bi-cart-plus"></i> В корзину
                                                </button>
                                                
                                                <button class="btn btn-outline-secondary btn-sm details-btn"
                                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                        data-product-category="<?= htmlspecialchars($product['category_name']) ?>"
                                                        data-variants='<?= json_encode($product['variants'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'
                                                        data-images='<?= json_encode($product['images_by_color'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'
                                                        data-colors='<?= json_encode($product['colors'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'
                                                        data-sizes='<?= json_encode($product['sizes'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'
                                                        onclick="openProductModal(this)">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Пагинация -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-5">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?section=<?= $section ?>&page=<?= $page - 1 ?><?= getActiveParams(['page']) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="?section=<?= $section ?>&page=<?= $i ?><?= getActiveParams(['page']) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?section=<?= $section ?>&page=<?= $page + 1 ?><?= getActiveParams(['page']) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-door-closed" style="font-size: 4rem; color: #dee2e6;"></i>
                            <h4 class="mt-3">Товары не найдены</h4>
                            <p class="text-muted">Попробуйте изменить параметры поиска или фильтры</p>
                            <a href="index.php?section=<?= $section ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise"></i> Показать все товары
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно деталей товара -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProductTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="modal-gallery">
                                <img id="modalMainImage" class="modal-main-image" src="" alt="">
                                <div id="modalThumbnails" class="modal-thumbnails"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <span class="badge bg-primary" id="modalProductCategory"></span>
                            </div>
                            
                            <h4 id="modalProductPrice" class="text-danger mb-3"></h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">🎨 Выберите цвет:</label>
                                <div id="modalColors" class="d-flex flex-wrap"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">📏 Выберите размер:</label>
                                <div id="modalSizes" class="d-flex flex-wrap"></div>
                            </div>
                            
                            <div id="modalProductAvailability" class="mb-3"></div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" id="modalAddToCartBtn">
                                    <i class="bi bi-cart-plus"></i> Добавить в корзину
                                </button>
                            </div>
                            
                            <hr>
                            
                            <h6>📋 Характеристики:</h6>
                            <table class="table table-sm">
                                <tr><td>Артикул:</td><td id="modalProductArticle"></td></tr>
                                <tr><td>Модель:</td><td id="modalProductModel"></td></tr>
                                <tr><td>Производитель:</td><td id="modalProductManufacturer"></td></tr>
                                <tr><td>Тип двери:</td><td id="modalProductDoorType"></td></tr>
                                <tr><td>Стиль:</td><td id="modalProductStyle"></td></tr>
                                <tr><td>Остекление:</td><td id="modalProductGlazing"></td></tr>
                                <tr><td>Толщина:</td><td id="modalProductThickness"></td></tr>
                                <tr><td>Покрытие:</td><td id="modalProductCoating"></td></tr>
                                <tr><td>Наполнение:</td><td id="modalProductFilling"></td></tr>
                             </table>
                            
                            <h6>Описание:</h6>
                            <div id="modalProductDescription" class="border rounded p-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "footer.php" ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.js"></script>
    <script>
    // Глобальные переменные для модального окна
    let currentModalVariants = [];
    let currentModalSelectedColor = null;
    let currentModalSelectedSize = null;
    let currentModalSelectedVariant = null;
    let modalImagesByColor = {};
    
    // Функция добавления в корзину
    function addToCart(productId, productName, productCategory, price, color, size) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('product_name', productName);
        formData.append('product_category', productCategory);
        formData.append('price', price);
        formData.append('color', color);
        formData.append('size', size);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Товар добавлен в корзину', 'success');
                updateCartCount(data.cart_count);
            } else {
                showNotification(data.error || '❌ Ошибка при добавлении', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('❌ Ошибка при добавлении в корзину', 'danger');
        });
    }
    
    // Показать уведомление
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);';
        notification.innerHTML = `<div class="d-flex align-items-center"><i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${message}</div>`;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
    
    // Обновить счетчик корзины
    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
    
    // Выбор цвета в карточке
    function selectProductColor(element, productHash, color, imagesByColor) {
        // Убираем выделение со всех кружков
        const container = document.getElementById(`colorOptions_${productHash}`);
        if (container) {
            container.querySelectorAll('.color-item').forEach(item => {
                const circle = item.querySelector('.color-circle');
                if (circle) circle.classList.remove('selected');
            });
        }
        
        // Добавляем выделение на выбранный
        const circle = element.querySelector('.color-circle');
        if (circle) circle.classList.add('selected');
        
        // Обновляем скрытое поле
        const colorInput = document.getElementById(`selectedColor_${productHash}`);
        if (colorInput) colorInput.value = color;
        
        // Обновляем отображение названия цвета
        const colorNameSpan = document.getElementById(`selectedColorName_${productHash}`);
        if (colorNameSpan) {
            colorNameSpan.textContent = color;
        }
        
        // Обновляем изображение
        const imgElement = document.getElementById(`productImage_${productHash}`);
        if (imgElement && imagesByColor[color] && imagesByColor[color].length > 0) {
            imgElement.src = imagesByColor[color][0];
        }
        
        // Обновляем цену
        updateProductPrice(productHash);
    }
    
    // Выбор размера в карточке
    function selectProductSize(element, productHash, size) {
        // Убираем выделение со всех размеров
        const container = document.getElementById(`sizeOptions_${productHash}`);
        if (container) {
            container.querySelectorAll('.size-option').forEach(item => {
                item.classList.remove('selected');
            });
        }
        
        // Добавляем выделение на выбранный
        element.classList.add('selected');
        
        // Обновляем скрытое поле
        const sizeInput = document.getElementById(`selectedSize_${productHash}`);
        if (sizeInput) sizeInput.value = size;
        
        // Обновляем цену
        updateProductPrice(productHash);
    }
    
    // Обновление цены при выборе цвета или размера
    function updateProductPrice(productHash) {
        const colorInput = document.getElementById(`selectedColor_${productHash}`);
        const sizeInput = document.getElementById(`selectedSize_${productHash}`);
        const selectedColor = colorInput ? colorInput.value : '';
        const selectedSize = sizeInput ? sizeInput.value : '';
        const addBtn = document.querySelector(`.add-to-cart-btn[data-product-hash="${productHash}"]`);
        const variants = addBtn ? JSON.parse(addBtn.dataset.variants) : [];
        
        let variant = null;
        if (selectedColor && selectedSize) {
            variant = variants.find(v => 
                v.attributes_color && v.attributes_color.includes(selectedColor) && 
                v.attributes_size && cleanSize(v.attributes_size) === selectedSize
            );
        }
        if (!variant && selectedColor) {
            variant = variants.find(v => v.attributes_color && v.attributes_color.includes(selectedColor));
        }
        if (!variant && selectedSize) {
            variant = variants.find(v => v.attributes_size && cleanSize(v.attributes_size) === selectedSize);
        }
        
        if (variant) {
            const priceElement = document.getElementById(`price_${productHash}`);
            if (priceElement) {
                priceElement.innerHTML = `${Number(variant.price).toLocaleString('ru-RU')} ₽`;
            }
            if (addBtn) {
                addBtn.dataset.productId = variant.id;
            }
        }
    }
    
    // Функция для очистки размера (должна совпадать с PHP)
    function cleanSize(size) {
        if (!size) return '';
        size = size.trim();
        size = size.replace(/["'\(\)\[\]\{\}]/g, '');
        return size;
    }
    
    // Получить код цвета
    function getColorCode(colorName) {
        const colors = {
            'белый': '#FFFFFF', 'черный': '#000000', 'серый': '#808080',
            'коричневый': '#8B4513', 'бежевый': '#F5F5DC', 'венге': '#4A2C2C',
            'дуб': '#8B5A2B', 'орех': '#5D3A1A', 'красный': '#FF4444',
            'синий': '#4444FF', 'зеленый': '#44FF44', 'желтый': '#FFFF44'
        };
        return colors[colorName?.toLowerCase()] || '#CCCCCC';
    }
    
    // Открыть модальное окно с деталями
    function openProductModal(button) {
        const variants = JSON.parse(button.dataset.variants);
        const imagesByColor = JSON.parse(button.dataset.images);
        const colors = JSON.parse(button.dataset.colors);
        const sizes = JSON.parse(button.dataset.sizes);
        const productName = button.dataset.productName;
        const productCategory = button.dataset.productCategory;
        
        currentModalVariants = variants;
        modalImagesByColor = imagesByColor;
        
        document.getElementById('modalProductTitle').textContent = productName;
        document.getElementById('modalProductCategory').textContent = productCategory;
        
        if (colors.length > 0) {
            currentModalSelectedColor = colors[0];
            const colorVariants = variants.filter(v => v.attributes_color && v.attributes_color.includes(colors[0]));
            renderModalColors(colors, variants);
            renderModalSizes(colorVariants);
            currentModalSelectedVariant = colorVariants[0] || variants[0];
            if (currentModalSelectedVariant) {
                updateModalInfo(currentModalSelectedVariant);
            }
        } else if (variants.length > 0) {
            currentModalSelectedVariant = variants[0];
            updateModalInfo(currentModalSelectedVariant);
            renderModalSizes(variants);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    }
    
    function renderModalColors(colors, variants) {
        const container = document.getElementById('modalColors');
        container.innerHTML = '';
        
        colors.forEach((color, index) => {
            const colorBtn = document.createElement('button');
            colorBtn.className = `color-option-btn ${index === 0 ? 'active' : ''}`;
            colorBtn.textContent = color;
            colorBtn.onclick = () => {
                document.querySelectorAll('#modalColors .color-option-btn').forEach(btn => btn.classList.remove('active'));
                colorBtn.classList.add('active');
                currentModalSelectedColor = color;
                const colorVariants = variants.filter(v => v.attributes_color && v.attributes_color.includes(color));
                renderModalSizes(colorVariants);
                currentModalSelectedVariant = colorVariants[0] || variants[0];
                if (currentModalSelectedVariant) updateModalInfo(currentModalSelectedVariant);
            };
            container.appendChild(colorBtn);
        });
    }
    
    function renderModalSizes(variants) {
        const container = document.getElementById('modalSizes');
        container.innerHTML = '';
        const sizes = [...new Set(variants.map(v => v.attributes_size ? cleanSize(v.attributes_size) : null).filter(s => s && s.trim()))];
        
        if (sizes.length === 0) {
            container.innerHTML = '<p class="text-muted">Стандартный размер</p>';
            return;
        }
        
        sizes.forEach((size, index) => {
            const sizeBtn = document.createElement('button');
            sizeBtn.className = `size-option-btn-modal ${index === 0 ? 'active' : ''}`;
            sizeBtn.textContent = size;
            sizeBtn.onclick = () => {
                document.querySelectorAll('#modalSizes .size-option-btn-modal').forEach(btn => btn.classList.remove('active'));
                sizeBtn.classList.add('active');
                currentModalSelectedSize = size;
                currentModalSelectedVariant = variants.find(v => cleanSize(v.attributes_size) === size) || variants[0];
                if (currentModalSelectedVariant) updateModalInfo(currentModalSelectedVariant);
            };
            container.appendChild(sizeBtn);
        });
        
        if (sizes.length > 0) {
            currentModalSelectedSize = sizes[0];
            currentModalSelectedVariant = variants.find(v => cleanSize(v.attributes_size) === sizes[0]) || variants[0];
        }
    }
    
    function updateModalInfo(variant) {
        document.getElementById('modalProductPrice').textContent = (variant.price || 0).toLocaleString('ru-RU') + ' ₽';
        const imageUrl = variant.picture || (modalImagesByColor[currentModalSelectedColor] ? modalImagesByColor[currentModalSelectedColor][0] : 'https://via.placeholder.com/500x400?text=Нет+изображения');
        document.getElementById('modalMainImage').src = imageUrl;
        
        renderModalThumbnails();
        
        const availability = document.getElementById('modalProductAvailability');
        availability.innerHTML = variant.quantity > 0 ? `<span class="badge bg-success">В наличии (${variant.quantity} шт.)</span>` : '<span class="badge bg-danger">Нет в наличии</span>';
        
        document.getElementById('modalProductArticle').textContent = variant.article || '—';
        document.getElementById('modalProductModel').textContent = variant.model || '—';
        document.getElementById('modalProductManufacturer').textContent = variant.attributes_manufacturer || '—';
        document.getElementById('modalProductDoorType').textContent = variant.attributes_door_type || '—';
        document.getElementById('modalProductStyle').textContent = variant.attributes_style || '—';
        document.getElementById('modalProductGlazing').textContent = variant.attributes_glazing || '—';
        document.getElementById('modalProductThickness').textContent = variant.attributes_thickness || '—';
        document.getElementById('modalProductCoating').textContent = variant.attributes_coating || '—';
        document.getElementById('modalProductFilling').textContent = variant.attributes_filling || '—';
        document.getElementById('modalProductDescription').innerHTML = variant.description || 'Описание отсутствует';
        
        const addBtn = document.getElementById('modalAddToCartBtn');
        addBtn.onclick = () => {
            if (currentModalSelectedVariant) {
                addToCart(
                    currentModalSelectedVariant.id,
                    document.getElementById('modalProductTitle').textContent,
                    document.getElementById('modalProductCategory').textContent,
                    currentModalSelectedVariant.price,
                    currentModalSelectedColor || currentModalSelectedVariant.attributes_color,
                    currentModalSelectedSize || cleanSize(currentModalSelectedVariant.attributes_size)
                );
            }
        };
    }
    
    function renderModalThumbnails() {
        const container = document.getElementById('modalThumbnails');
        container.innerHTML = '';
        const images = modalImagesByColor[currentModalSelectedColor] || [];
        images.forEach((img, index) => {
            const thumb = document.createElement('img');
            thumb.className = `modal-thumb ${index === 0 ? 'active' : ''}`;
            thumb.src = img;
            thumb.onclick = () => {
                document.getElementById('modalMainImage').src = img;
                document.querySelectorAll('.modal-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            };
            container.appendChild(thumb);
        });
    }
    
    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        const priceSlider = document.getElementById('price-slider');
        const minPriceInput = document.getElementById('min-price');
        const maxPriceInput = document.getElementById('max-price');
        
        if (priceSlider) {
            noUiSlider.create(priceSlider, {
                start: [<?= $min_price ?>, <?= $max_price ?>],
                connect: true,
                range: { 'min': <?= $global_min_price ?>, 'max': <?= $global_max_price ?> },
                step: 100,
                format: { to: value => Math.round(value), from: value => Number(value) }
            });
            
            priceSlider.noUiSlider.on('update', values => {
                minPriceInput.value = Math.round(values[0]);
                maxPriceInput.value = Math.round(values[1]);
            });
            
            priceSlider.noUiSlider.on('change', () => document.getElementById('filterForm').submit());
        }
        
        // Выделяем первый цвет и размер по умолчанию
        document.querySelectorAll('.color-options').forEach(container => {
            const firstColor = container.querySelector('.color-item');
            if (firstColor) {
                const circle = firstColor.querySelector('.color-circle');
                if (circle) circle.classList.add('selected');
            }
        });
        
        document.querySelectorAll('.size-options').forEach(container => {
            const firstSize = container.querySelector('.size-option');
            if (firstSize) {
                firstSize.classList.add('selected');
            }
        });
        
        // Кнопки добавления в корзину
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const productCategory = this.dataset.productCategory;
                const productHash = this.dataset.productHash;
                const variants = JSON.parse(this.dataset.variants);
                
                const colorInput = document.getElementById(`selectedColor_${productHash}`);
                const sizeInput = document.getElementById(`selectedSize_${productHash}`);
                const selectedColor = colorInput ? colorInput.value : '';
                const selectedSize = sizeInput ? sizeInput.value : '';
                
                let variant = null;
                if (selectedColor && selectedSize) {
                    variant = variants.find(v => 
                        v.attributes_color && v.attributes_color.includes(selectedColor) && 
                        v.attributes_size && cleanSize(v.attributes_size) === selectedSize
                    );
                }
                if (!variant && selectedColor) {
                    variant = variants.find(v => v.attributes_color && v.attributes_color.includes(selectedColor));
                }
                if (!variant && selectedSize) {
                    variant = variants.find(v => v.attributes_size && cleanSize(v.attributes_size) === selectedSize);
                }
                if (!variant) variant = variants[0];
                
                if (variant) {
                    addToCart(
                        variant.id,
                        productName,
                        productCategory,
                        variant.price,
                        selectedColor,
                        selectedSize
                    );
                    
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check"></i> Добавлено';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                    }, 2000);
                } else {
                    showNotification('❌ Не удалось найти подходящий вариант', 'danger');
                }
            });
        });
        
        document.querySelectorAll('img').forEach(img => {
            img.onerror = function() { this.src = 'https://via.placeholder.com/400x300?text=Нет+изображения'; };
        });
    });
    
    function updateSorting(value) {
        document.getElementById('sortField').value = value;
        document.getElementById('filterForm').submit();
    }
    
    function updateCategorySort(categoryName) {
        const sortField = document.getElementById('sortField');
        const sortSelect = document.getElementById('sortSelect');
        if (categoryName) {
            sortField.value = 'name_asc';
            if (sortSelect) sortSelect.value = 'name_asc';
        } else {
            sortField.value = 'price_asc';
            if (sortSelect) sortSelect.value = 'price_asc';
        }
        document.getElementById('filterForm').submit();
    }
    </script>
</body>
</html>