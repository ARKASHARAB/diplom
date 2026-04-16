<?php
// index.php - главная страница каталога с фильтрами по JSON-структуре
require_once 'config.php';

// Получаем параметры из URL
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$category = isset($_GET['category']) ? clean($_GET['category']) : '';
$color = isset($_GET['color']) ? clean($_GET['color']) : '';
$manufacturer = isset($_GET['manufacturer']) ? clean($_GET['manufacturer']) : '';
$style = isset($_GET['style']) ? clean($_GET['style']) : '';
$glazing = isset($_GET['glazing']) ? clean($_GET['glazing']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'price_asc';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

// Подключаемся к БД
$db = getDB();

// Формируем SQL запрос с фильтрами
$where = [];
$params = [];

// Базовые фильтры
if ($category) {
    $where[] = "categoryId = :category";
    $params[':category'] = $category;
}

if ($color) {
    $where[] = "attributes_color = :color";
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

if ($search) {
    $where[] = "(name LIKE :search OR description LIKE :search OR model LIKE :search OR class1 LIKE :search)";
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

// Определяем минимальную и максимальную цену для фильтра
$price_stats = $db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM " . TABLE_PRODUCTS)->fetch();
$global_min_price = floor($price_stats['min_price'] / 1000) * 1000;
$global_max_price = ceil($price_stats['max_price'] / 1000) * 1000;

if ($min_price == 0) $min_price = $global_min_price;
if ($max_price == 0) $max_price = $global_max_price;

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Сортировка
$sort_sql = match($sort) {
    'price_desc' => 'ORDER BY price DESC',
    'name_asc' => 'ORDER BY name ASC',
    'name_desc' => 'ORDER BY name DESC',
    'newest' => 'ORDER BY id DESC',
    'quantity_desc' => 'ORDER BY quantity DESC',
    default => 'ORDER BY price ASC'
};

// Пагинация
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Получаем товары
$sql = "SELECT *, 
               attributes_color as color,
               attributes_manufacturer as manufacturer,
               attributes_style as style,
               attributes_glazing as glazing,
               attributes_thickness as thickness,
               attributes_coating as coating,
               attributes_frame as frame
        FROM " . TABLE_PRODUCTS . " 
        $where_sql 
        $sort_sql 
        LIMIT :limit OFFSET :offset";
        
$stmt = $db->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Получаем общее количество для пагинации
$count_sql = "SELECT COUNT(*) as total FROM " . TABLE_PRODUCTS . " $where_sql";
$stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    if (!in_array($key, [':limit', ':offset'])) {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$total_items = $stmt->fetch()['total'];
$total_pages = ceil($total_items / ITEMS_PER_PAGE);

// Получаем фильтры для сайдбара
$colors = $db->query("SELECT DISTINCT attributes_color FROM " . TABLE_PRODUCTS . " WHERE attributes_color IS NOT NULL AND attributes_color != '' ORDER BY attributes_color")->fetchAll(PDO::FETCH_COLUMN);
$manufacturers = $db->query("SELECT DISTINCT attributes_manufacturer FROM " . TABLE_PRODUCTS . " WHERE attributes_manufacturer IS NOT NULL AND attributes_manufacturer != '' ORDER BY attributes_manufacturer")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT DISTINCT categoryId FROM " . TABLE_PRODUCTS . " WHERE categoryId IS NOT NULL AND categoryId != '' ORDER BY categoryId")->fetchAll(PDO::FETCH_COLUMN);
$styles = $db->query("SELECT DISTINCT attributes_style FROM " . TABLE_PRODUCTS . " WHERE attributes_style IS NOT NULL AND attributes_style != '' ORDER BY attributes_style")->fetchAll(PDO::FETCH_COLUMN);
$glazing_types = $db->query("SELECT DISTINCT attributes_glazing FROM " . TABLE_PRODUCTS . " WHERE attributes_glazing IS NOT NULL AND attributes_glazing != '' ORDER BY attributes_glazing")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.css">
    <style>
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 1px solid #e9ecef;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 220px;
            object-fit: contain;
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
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
            top: 20px;
        }
        .product-tags {
            margin-top: 10px;
        }
        .tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 10px;
            margin: 2px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .badge-custom {
            font-size: 0.7rem;
            padding: 3px 8px;
            margin-right: 3px;
        }
        .model-code {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .quantity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .noUi-connect {
            background: #0d6efd;
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
            height: 3em;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .card-text {
            font-size: 0.85rem;
            color: #6c757d;
            height: 4.2em;
            overflow: hidden;
        }
        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .active-filters {
            background: #e7f1ff;
            border-left: 4px solid #0d6efd;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .filter-tag {
            background: #0d6efd;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-door-closed me-2"></i> <?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex ms-auto" method="GET" action="index.php">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" 
                               placeholder="Поиск по названию, модели..." value="<?= $search ?>">
                        <button class="btn btn-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Активные фильтры -->
        <?php if ($category || $color || $manufacturer || $style || $glazing || $search || $min_price != $global_min_price || $max_price != $global_max_price): ?>
            <div class="active-filters">
                <h6><i class="bi bi-funnel-fill"></i> Активные фильтры:</h6>
                <div class="d-flex flex-wrap align-items-center">
                    <?php if ($search): ?>
                        <span class="filter-tag">
                            Поиск: "<?= $search ?>"
                            <a href="<?= removeUrlParam('search') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($category): ?>
                        <span class="filter-tag">
                            Категория: <?= $category ?>
                            <a href="<?= removeUrlParam('category') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($color): ?>
                        <span class="filter-tag">
                            Цвет: <?= $color ?>
                            <a href="<?= removeUrlParam('color') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($manufacturer): ?>
                        <span class="filter-tag">
                            Производитель: <?= $manufacturer ?>
                            <a href="<?= removeUrlParam('manufacturer') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($style): ?>
                        <span class="filter-tag">
                            Стиль: <?= $style ?>
                            <a href="<?= removeUrlParam('style') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($glazing): ?>
                        <span class="filter-tag">
                            Остекление: <?= $glazing ?>
                            <a href="<?= removeUrlParam('glazing') ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($min_price != $global_min_price || $max_price != $global_max_price): ?>
                        <span class="filter-tag">
                            Цена: <?= number_format($min_price, 0, ',', ' ') ?> - <?= number_format($max_price, 0, ',', ' ') ?> руб.
                            <a href="<?= removeUrlParam(['min_price', 'max_price']) ?>"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline-danger btn-sm ms-auto">
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
                        <span class="badge bg-primary"><?= $total_items ?></span>
                    </h5>
                    
                    <form method="GET" action="index.php" id="filterForm">
                        
                        <!-- Поиск -->
                        <div class="mb-3">
                            <label class="form-label">Поиск</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Название, модель..." value="<?= $search ?>">
                        </div>
                        
                        <!-- Фильтр по цене -->
                        <div class="mb-3">
                            <label class="form-label">Цена, руб.</label>
                            <div id="price-slider" class="price-slider"></div>
                            <div class="price-values">
                                <input type="number" id="min-price" name="min_price" 
                                       class="form-control form-control-sm" style="width: 100px;"
                                       value="<?= $min_price ?>">
                                <span>—</span>
                                <input type="number" id="max-price" name="max_price" 
                                       class="form-control form-control-sm" style="width: 100px;"
                                       value="<?= $max_price ?>">
                            </div>
                        </div>
                        
                        <!-- Фильтр по категории -->
                        <div class="mb-3">
                            <label class="form-label">Категория</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>" <?= ($category == $cat) ? 'selected' : '' ?>>
                                        Категория <?= $cat ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Фильтр по цвету -->
                        <div class="mb-3">
                            <label class="form-label">Цвет</label>
                            <select name="color" class="form-select" onchange="this.form.submit()">
                                <option value="">Все цвета</option>
                                <?php foreach ($colors as $color_option): ?>
                                    <option value="<?= $color_option ?>" <?= ($color == $color_option) ? 'selected' : '' ?>>
                                        <?= $color_option ?>
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
                                    <option value="<?= $manufacturer_option ?>" <?= ($manufacturer == $manufacturer_option) ? 'selected' : '' ?>>
                                        <?= $manufacturer_option ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Фильтр по стилю -->
                        <div class="mb-3">
                            <label class="form-label">Стиль</label>
                            <select name="style" class="form-select" onchange="this.form.submit()">
                                <option value="">Все стили</option>
                                <?php foreach ($styles as $style_option): ?>
                                    <option value="<?= $style_option ?>" <?= ($style == $style_option) ? 'selected' : '' ?>>
                                        <?= $style_option ?>
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
                                    <option value="<?= $glazing_option ?>" <?= ($glazing == $glazing_option) ? 'selected' : '' ?>>
                                        <?= $glazing_option ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Сортировка -->
                        <div class="mb-3">
                            <label class="form-label">Сортировка</label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : '' ?>>По цене (дешевле)</option>
                                <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : '' ?>>По цене (дороже)</option>
                                <option value="name_asc" <?= ($sort == 'name_asc') ? 'selected' : '' ?>>По названию (А-Я)</option>
                                <option value="name_desc" <?= ($sort == 'name_desc') ? 'selected' : '' ?>>По названию (Я-А)</option>
                                <option value="newest" <?= ($sort == 'newest') ? 'selected' : '' ?>>Сначала новые</option>
                                <option value="quantity_desc" <?= ($sort == 'quantity_desc') ? 'selected' : '' ?>>По наличию</option>
                            </select>
                        </div>
                        
                        <!-- Скрытые поля -->
                        <?php if ($search): ?><input type="hidden" name="search" value="<?= $search ?>"><?php endif; ?>
                        <?php if ($category): ?><input type="hidden" name="category" value="<?= $category ?>"><?php endif; ?>
                        <?php if ($color): ?><input type="hidden" name="color" value="<?= $color ?>"><?php endif; ?>
                        <?php if ($manufacturer): ?><input type="hidden" name="manufacturer" value="<?= $manufacturer ?>"><?php endif; ?>
                        <?php if ($style): ?><input type="hidden" name="style" value="<?= $style ?>"><?php endif; ?>
                        <?php if ($glazing): ?><input type="hidden" name="glazing" value="<?= $glazing ?>"><?php endif; ?>
                        
                    </form>
                    
                    <!-- Статистика -->
                    <div class="alert alert-light border mt-3">
                        <h6><i class="bi bi-info-circle"></i> Статистика</h6>
                        <p class="mb-1 small">Товаров: <strong><?= $total_items ?></strong></p>
                        <p class="mb-1 small">Цветов: <strong><?= count($colors) ?></strong></p>
                        <p class="mb-0 small">Производителей: <strong><?= count($manufacturers) ?></strong></p>
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div class="col-lg-9">
                <!-- Заголовок с сортировкой -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-0">Каталог дверей</h3>
                        <small class="text-muted">Найдено товаров: <?= $total_items ?></small>
                    </div>
                    
                    <!-- Быстрая сортировка для мобильных -->
                    <div class="d-lg-none">
                        <select class="form-select form-select-sm" onchange="window.location.href = this.value">
                            <option value="?sort=price_asc<?= getActiveParams(['sort']) ?>">По цене (↑)</option>
                            <option value="?sort=price_desc<?= getActiveParams(['sort']) ?>">По цене (↓)</option>
                            <option value="?sort=name_asc<?= getActiveParams(['sort']) ?>">По названию (А-Я)</option>
                            <option value="?sort=name_desc<?= getActiveParams(['sort']) ?>">По названию (Я-А)</option>
                        </select>
                    </div>
                    
                    <!-- Кнопки просмотра -->
                    <div class="btn-group d-none d-lg-flex" role="group">
                        <a href="?<?= getActiveParams() ?>&sort=price_asc" class="btn btn-outline-primary <?= ($sort == 'price_asc') ? 'active' : '' ?>">
                            <i class="bi bi-sort-numeric-down"></i> Дешевле
                        </a>
                        <a href="?<?= getActiveParams() ?>&sort=price_desc" class="btn btn-outline-primary <?= ($sort == 'price_desc') ? 'active' : '' ?>">
                            <i class="bi bi-sort-numeric-up-alt"></i> Дороже
                        </a>
                        <a href="?<?= getActiveParams() ?>&sort=quantity_desc" class="btn btn-outline-primary <?= ($sort == 'quantity_desc') ? 'active' : '' ?>">
                            <i class="bi bi-box"></i> В наличии
                        </a>
                    </div>
                </div>

                <!-- Карточки товаров -->
                <?php if ($products): ?>
                    <div class="row g-3">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card product-card h-100">
                                    <!-- Бейдж наличия -->
                                    <?php if ($product['quantity'] > 0): ?>
                                        <span class="badge bg-success quantity-badge">
                                            <i class="bi bi-check-circle"></i> В наличии
                                            <?php if ($product['quantity'] < 10): ?>
                                                <small>(<?= $product['quantity'] ?> шт.)</small>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger quantity-badge">
                                            <i class="bi bi-x-circle"></i> Нет в наличии
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Изображение -->
                                    <div class="position-relative">
                                        <img src="<?= $product['picture'] ?>" 
                                             class="card-img-top product-image" 
                                             alt="<?= $product['name'] ?>"
                                             onerror="this.src='https://via.placeholder.com/400x300?text=Нет+изображения'">
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <!-- Название -->
                                        <h5 class="card-title"><?= $product['name'] ?></h5>
                                        
                                        <!-- Модель и код -->
                                        <div class="mb-2">
                                            <small class="model-code"><?= $product['model'] ?></small>
                                        </div>
                                        
                                        <!-- Цвет и производитель -->
                                        <div class="mb-2">
                                            <span class="badge bg-info badge-custom">
                                                <i class="bi bi-palette"></i> <?= $product['color'] ?>
                                            </span>
                                            <span class="badge bg-secondary badge-custom">
                                                <i class="bi bi-building"></i> <?= $product['manufacturer'] ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Характеристики -->
                                        <div class="mb-2">
                                            <small class="text-muted d-block">
                                                <i class="bi bi-rulers"></i> Толщина: <?= $product['thickness'] ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-border-style"></i> Остекление: <?= $product['glazing'] ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-brush"></i> Покрытие: <?= $product['coating'] ?>
                                            </small>
                                        </div>
                                        
                                        <!-- Цена -->
                                        <div class="price mt-auto mb-2">
                                            <?= number_format($product['price'], 0, ',', ' ') ?><?= CURRENCY ?>
                                        </div>
                                        
                                        <!-- Кнопки -->
                                        <div class="d-flex gap-2 mt-2">
                                            <button class="btn btn-primary btn-sm flex-fill"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#productModal"
                                                    onclick="showProductDetails(
                                                        '<?= addslashes($product['name']) ?>',
                                                        '<?= addslashes($product['model']) ?>',
                                                        '<?= $product['picture'] ?>',
                                                        '<?= number_format($product['price'], 0, ',', ' ') ?>',
                                                        '<?= addslashes($product['description']) ?>',
                                                        '<?= addslashes($product['attributes_size']) ?>',
                                                        '<?= addslashes($product['color']) ?>',
                                                        '<?= addslashes($product['manufacturer']) ?>',
                                                        '<?= addslashes($product['style']) ?>',
                                                        '<?= addslashes($product['glazing']) ?>',
                                                        '<?= addslashes($product['thickness']) ?>',
                                                        '<?= addslashes($product['coating']) ?>',
                                                        '<?= addslashes($product['frame']) ?>',
                                                        '<?= addslashes($product['attributes_filling']) ?>',
                                                        '<?= addslashes($product['attributes_edge']) ?>',
                                                        '<?= addslashes($product['attributes_purpose']) ?>',
                                                        '<?= $product['quantity'] ?>',
                                                        '<?= $product['url'] ?>'
                                                    )">
                                                <i class="bi bi-info-circle"></i> Подробнее
                                            </button>
                                            <?php if ($product['quantity'] > 0): ?>
                                                <button class="btn btn-success btn-sm">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="bi bi-cart-x"></i>
                                                </button>
                                            <?php endif; ?>
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
                                <!-- Кнопка "Назад" -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $page - 1 ?><?= getActiveParams(['page']) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Номера страниц -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" 
                                               href="?page=<?= $i ?><?= getActiveParams(['page']) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <!-- Кнопка "Вперед" -->
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $page + 1 ?><?= getActiveParams(['page']) ?>">
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
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise"></i> Показать все товары
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно деталей -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProductTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="modalProductImage" src="" class="img-fluid rounded" alt="">
                            <div class="mt-3">
                                <h6>Код модели: <span id="modalProductModel" class="badge bg-secondary"></span></h6>
                                <h4 id="modalProductPrice" class="text-danger"></h4>
                                <div id="modalProductAvailability" class="mb-3"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Основные характеристики:</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Цвет:</strong></td><td id="modalProductColor"></td></tr>
                                <tr><td><strong>Производитель:</strong></td><td id="modalProductManufacturer"></td></tr>
                                <tr><td><strong>Стиль:</strong></td><td id="modalProductStyle"></td></tr>
                                <tr><td><strong>Остекление:</strong></td><td id="modalProductGlazing"></td></tr>
                                <tr><td><strong>Толщина:</strong></td><td id="modalProductThickness"></td></tr>
                                <tr><td><strong>Покрытие:</strong></td><td id="modalProductCoating"></td></tr>
                                <tr><td><strong>Каркас:</strong></td><td id="modalProductFrame"></td></tr>
                                <tr><td><strong>Наполнение:</strong></td><td id="modalProductFilling"></td></tr>
                                <tr><td><strong>Кромка:</strong></td><td id="modalProductEdge"></td></tr>
                            </table>
                            
                            <h6 class="mt-4">Доступные размеры:</h6>
                            <div id="modalProductSizes" class="mb-3"></div>
                            
                            <h6>Назначение:</h6>
                            <div id="modalProductPurpose" class="mb-3"></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Описание:</h6>
                            <div id="modalProductDescription" class="border rounded p-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <a id="modalProductLink" href="#" class="btn btn-primary" target="_blank">
                        <i class="bi bi-cart-plus"></i> Перейти к покупке
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-door-closed"></i> <?= SITE_NAME ?></h5>
                    <p class="text-muted">Каталог межкомнатных дверей с расширенными фильтрами</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Используется таблица: <?= TABLE_PRODUCTS ?></small><br>
                    <small>Товаров в базе: <?= $total_items ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.js"></script>
    <script>
    // Инициализация слайдера цен
    document.addEventListener('DOMContentLoaded', function() {
        var priceSlider = document.getElementById('price-slider');
        var minPriceInput = document.getElementById('min-price');
        var maxPriceInput = document.getElementById('max-price');
        
        if (priceSlider) {
            noUiSlider.create(priceSlider, {
                start: [<?= $min_price ?>, <?= $max_price ?>],
                connect: true,
                range: {
                    'min': <?= $global_min_price ?>,
                    'max': <?= $global_max_price ?>
                },
                step: 100,
                format: {
                    to: function(value) {
                        return Math.round(value);
                    },
                    from: function(value) {
                        return Number(value);
                    }
                }
            });
            
            priceSlider.noUiSlider.on('update', function(values) {
                minPriceInput.value = Math.round(values[0]);
                maxPriceInput.value = Math.round(values[1]);
            });
            
            minPriceInput.addEventListener('change', function() {
                priceSlider.noUiSlider.set([this.value, null]);
            });
            
            maxPriceInput.addEventListener('change', function() {
                priceSlider.noUiSlider.set([null, this.value]);
            });
        }
        
        // Автосабмит при изменении полей цены
        document.getElementById('min-price').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.getElementById('max-price').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    function showProductDetails(name, model, image, price, description, sizes, color, manufacturer, style, glazing, thickness, coating, frame, filling, edge, purpose, quantity, url) {
        // Устанавливаем значения
        document.getElementById('modalProductTitle').textContent = name;
        document.getElementById('modalProductModel').textContent = model;
        document.getElementById('modalProductImage').src = image;
        document.getElementById('modalProductPrice').textContent = price + '<?= CURRENCY ?>';
        document.getElementById('modalProductColor').textContent = color;
        document.getElementById('modalProductManufacturer').textContent = manufacturer;
        document.getElementById('modalProductStyle').textContent = style;
        document.getElementById('modalProductGlazing').textContent = glazing;
        document.getElementById('modalProductThickness').textContent = thickness;
        document.getElementById('modalProductCoating').textContent = coating;
        document.getElementById('modalProductFrame').textContent = frame;
        document.getElementById('modalProductFilling').textContent = filling;
        document.getElementById('modalProductEdge').textContent = edge;
        document.getElementById('modalProductPurpose').textContent = purpose;
        document.getElementById('modalProductSizes').textContent = sizes;
        document.getElementById('modalProductDescription').innerHTML = description;
        document.getElementById('modalProductLink').href = url;
        
        // Наличие
        var availability = document.getElementById('modalProductAvailability');
        if (quantity > 0) {
            availability.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> В наличии</span>';
        } else {
            availability.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Нет в наличии</span>';
        }
        
        // Обработка ошибок изображений
        document.getElementById('modalProductImage').onerror = function() {
            this.src = 'https://via.placeholder.com/600x400?text=Нет+изображения';
        };
    }
    
    // Обработка ошибок изображений на странице
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('img').forEach(img => {
            img.onerror = function() {
                this.src = 'https://via.placeholder.com/400x300?text=Нет+изображения';
            }
        });
    });
    </script>
</body>
</html>

<?php
// Функция для генерации параметров URL
function getActiveParams($exclude = []) {
    $params = [];
    
    if (isset($_GET['category']) && !in_array('category', $exclude)) {
        $params[] = 'category=' . urlencode($_GET['category']);
    }
    if (isset($_GET['color']) && !in_array('color', $exclude)) {
        $params[] = 'color=' . urlencode($_GET['color']);
    }
    if (isset($_GET['manufacturer']) && !in_array('manufacturer', $exclude)) {
        $params[] = 'manufacturer=' . urlencode($_GET['manufacturer']);
    }
    if (isset($_GET['style']) && !in_array('style', $exclude)) {
        $params[] = 'style=' . urlencode($_GET['style']);
    }
    if (isset($_GET['glazing']) && !in_array('glazing', $exclude)) {
        $params[] = 'glazing=' . urlencode($_GET['glazing']);
    }
    if (isset($_GET['search']) && !in_array('search', $exclude)) {
        $params[] = 'search=' . urlencode($_GET['search']);
    }
    if (isset($_GET['min_price']) && $_GET['min_price'] > 0 && !in_array('min_price', $exclude)) {
        $params[] = 'min_price=' . floatval($_GET['min_price']);
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] > 0 && !in_array('max_price', $exclude)) {
        $params[] = 'max_price=' . floatval($_GET['max_price']);
    }
    if (isset($_GET['sort']) && !in_array('sort', $exclude) && $_GET['sort'] != 'price_asc') {
        $params[] = 'sort=' . urlencode($_GET['sort']);
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
    
    return $url . http_build_query($params);
}
?>