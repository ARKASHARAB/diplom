<?php
// admin/products.php - управление товарами
require_once '../config.php';

// Проверка прав администратора
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Получаем список цветов из настроек
$colors_list = $db->query("SELECT * FROM admindoor1_color_settings WHERE is_active = 1 ORDER BY sort_order, color_name")->fetchAll();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Сохранение товара
        $data = [
            'article' => clean($_POST['article']),
            'model' => clean($_POST['model']),
            'code' => clean($_POST['code']),
            'url' => clean($_POST['url']),
            'categoryId' => clean($_POST['categoryId']),
            'category_name' => clean($_POST['category_name']),
            'picture' => clean($_POST['picture']),
            'name' => clean($_POST['name']),
            'price' => floatval($_POST['price']),
            'quantity' => intval($_POST['quantity']),
            'attributes_color' => isset($_POST['attributes_color']) ? implode(',', $_POST['attributes_color']) : '',
            'attributes_purpose' => $_POST['attributes_purpose'],
            'attributes_glazing' => clean($_POST['attributes_glazing']),
            'attributes_edge' => clean($_POST['attributes_edge']),
            'attributes_frame' => clean($_POST['attributes_frame']),
            'attributes_filling' => clean($_POST['attributes_filling']),
            'attributes_coating' => clean($_POST['attributes_coating']),
            'attributes_manufacturer' => clean($_POST['attributes_manufacturer']),
            'attributes_size' => $_POST['attributes_size'],
            'attributes_thickness' => clean($_POST['attributes_thickness']),
            'attributes_style' => clean($_POST['attributes_style']),
            'attributes_door_type' => clean($_POST['attributes_door_type']),
            'description' => $_POST['description']
        ];

        // Преобразуем массивы в JSON
        if (is_array($data['attributes_purpose'])) {
            $data['attributes_purpose'] = json_encode($data['attributes_purpose'], JSON_UNESCAPED_UNICODE);
        }
        if (is_array($data['attributes_size'])) {
            $data['attributes_size'] = json_encode($data['attributes_size'], JSON_UNESCAPED_UNICODE);
        }

        try {
            if ($id > 0) {
                // Обновление
                $sql = "UPDATE " . TABLE_PRODUCTS . " SET 
                        article=:article, model=:model, code=:code, url=:url,
                        categoryId=:categoryId, category_name=:category_name,
                        picture=:picture, name=:name, price=:price, quantity=:quantity,
                        attributes_color=:attributes_color, attributes_purpose=:attributes_purpose,
                        attributes_glazing=:attributes_glazing, attributes_edge=:attributes_edge,
                        attributes_frame=:attributes_frame, attributes_filling=:attributes_filling,
                        attributes_coating=:attributes_coating, attributes_manufacturer=:attributes_manufacturer,
                        attributes_size=:attributes_size, attributes_thickness=:attributes_thickness,
                        attributes_style=:attributes_style, attributes_door_type=:attributes_door_type,
                        description=:description, updated_at=NOW()
                        WHERE id=:id";
                $data['id'] = $id;
                $message = 'Товар успешно обновлен';
            } else {
                // Добавление
                $sql = "INSERT INTO " . TABLE_PRODUCTS . " 
                        (article, model, code, url, categoryId, category_name, picture, name, 
                         price, quantity, attributes_color, attributes_purpose, attributes_glazing, 
                         attributes_edge, attributes_frame, attributes_filling, attributes_coating, 
                         attributes_manufacturer, attributes_size, attributes_thickness, attributes_style, 
                         attributes_door_type, description, created_at, updated_at) 
                        VALUES 
                        (:article, :model, :code, :url, :categoryId, :category_name, :picture, :name,
                         :price, :quantity, :attributes_color, :attributes_purpose, :attributes_glazing,
                         :attributes_edge, :attributes_frame, :attributes_filling, :attributes_coating,
                         :attributes_manufacturer, :attributes_size, :attributes_thickness, :attributes_style,
                         :attributes_door_type, :description, NOW(), NOW())";
                $message = 'Товар успешно добавлен';
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($data);

            redirect('products.php?action=list&message=' . urlencode($message));
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        // Удаление товара
        $id = intval($_POST['id']);
        try {
            // Получаем информацию о товаре для лога
            $stmt = $db->prepare("SELECT name FROM " . TABLE_PRODUCTS . " WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            // Удаляем
            $stmt = $db->prepare("DELETE FROM " . TABLE_PRODUCTS . " WHERE id = ?");
            $stmt->execute([$id]);

            redirect('products.php?action=list&message=' . urlencode('Товар успешно удален'));
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении: ' . $e->getMessage();
        }
    }
}

// Получение сообщения из URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Получение списка товаров
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 100;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$manufacturer_filter = isset($_GET['manufacturer']) ? clean($_GET['manufacturer']) : '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE :search OR article LIKE :search OR model LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($manufacturer_filter) {
    $where[] = "attributes_manufacturer = :manufacturer";
    $params[':manufacturer'] = $manufacturer_filter;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем общее количество
$count_sql = "SELECT COUNT(*) FROM " . TABLE_PRODUCTS . " $where_sql";
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Получаем товары
$sql = "SELECT * FROM " . TABLE_PRODUCTS . " $where_sql ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Получаем список производителей для фильтра
$manufacturers = $db->query("SELECT DISTINCT attributes_manufacturer FROM " . TABLE_PRODUCTS . " WHERE attributes_manufacturer IS NOT NULL AND attributes_manufacturer != '' ORDER BY attributes_manufacturer")->fetchAll(PDO::FETCH_COLUMN);

// Если нужно редактирование - получаем данные товара
$product = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM " . TABLE_PRODUCTS . " WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect('products.php?action=list&error=' . urlencode('Товар не найден'));
    }
    
    // Декодируем JSON поля
    if ($product['attributes_purpose']) {
        $product['attributes_purpose'] = json_decode($product['attributes_purpose'], true);
    }
    if ($product['attributes_size']) {
        $product['attributes_size'] = json_decode($product['attributes_size'], true);
    }
    
    // Разбираем выбранные цвета
    $selected_colors = $product['attributes_color'] ? explode(',', $product['attributes_color']) : [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .main-content {
            margin-left: 25px;
            padding: 20px;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-admin {
            background-color: #3D3D3D;
            border-color: #3D3D3D;
            color: white;
        }
        .btn-admin:hover {
            background-color: #2d2d2d;
            color: white;
        }
        .product-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .nav-tabs .nav-link {
            color: #3D3D3D;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #3D3D3D;
        }
        .array-field {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .array-item {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 2px;
            font-size: 0.9rem;
        }
        .array-item i {
            cursor: pointer;
            margin-left: 5px;
            color: #dc3545;
        }
        .array-item i:hover {
            color: #bd2130;
        }
        .price-field {
            font-weight: bold;
            color: #28a745;
        }
        .badge-manufacturer {
            background-color: #6f42c1;
            color: white;
        }
        
        /* Стили для выбора цветов */
        .color-checkbox-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .color-checkbox-item {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 25px;
            background: white;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
            gap: 8px;
        }
        .color-checkbox-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: #3D3D3D;
        }
        .color-checkbox-item.selected {
            background: #e7f1ff;
            border-color: #3D3D3D;
            box-shadow: 0 0 0 2px rgba(61,61,61,0.2);
        }
        .color-checkbox-item input {
            margin: 0;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .color-circle-small {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }
        .color-label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        .color-hint {
            font-size: 12px;
            color: #6c757d;
            margin-left: 5px;
        }
        .selected-colors-preview {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .selected-color-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #e9ecef;
            border-radius: 20px;
            margin: 2px;
            font-size: 12px;
        }
        .selected-color-tag .color-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
        }
        .select-all-btn {
            margin-bottom: 10px;
            padding: 5px 12px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<?php include 'header.php';?>


    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0">
                <i class="bi bi-box"></i> 
                <?php if ($action === 'add'): ?>
                    Добавление товара
                <?php elseif ($action === 'edit'): ?>
                    Редактирование товара: <?= htmlspecialchars($product['name'] ?? '') ?>
                <?php else: ?>
                    Управление товарами
                <?php endif; ?>
            </h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span><i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?? $_SESSION['username'] ?></span>
            </div>
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

        <?php if ($action === 'list'): ?>
            <!-- Фильтры -->
            <div class="filter-section">
                <form method="GET" action="products.php" class="row g-3">
                    <input type="hidden" name="action" value="list">
                    <div class="col-md-5">
                        <label class="form-label">Поиск</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Название, артикул, модель..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Производитель</label>
                        <select name="manufacturer" class="form-select">
                            <option value="">Все производители</option>
                            <?php foreach ($manufacturers as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $manufacturer_filter == $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-admin w-100">
                            <i class="bi bi-search"></i> Применить фильтры
                        </button>
                    </div>
                </form>
            </div>

            <!-- Кнопка добавления -->
            <div class="mb-3">
                <a href="products.php?action=add" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Добавить товар
                </a>
                <span class="float-end">Всего товаров: <?= $total_items ?></span>
            </div>

            <!-- Таблица товаров -->
            <div class="product-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Название</th>
                            <th>Артикул</th>
                            <th>Производитель</th>
                            <th>Цена</th>
                            <th>Наличие</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <img src="<?= htmlspecialchars($p['picture'] ?? 'https://via.placeholder.com/50') ?>" 
                                     class="product-image" alt="" onerror="this.src='https://via.placeholder.com/50'">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($p['category_name'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['article']) ?></td>
                            <td>
                                <span class="badge badge-manufacturer"><?= htmlspecialchars($p['attributes_manufacturer'] ?? '') ?></span>
                            </td>
                            <td class="price-field"><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
                            <td>
                                <?php if ($p['quantity'] > 0): ?>
                                    <span class="badge bg-success"><?= $p['quantity'] ?> шт.</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Нет</span>
                                <?php endif; ?>
                             </td>
                             <td>
                                <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Пагинация -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php 
                            $query_params = [
                                'action' => 'list',
                                'p' => ''
                            ];
                            
                            if (!empty($search)) {
                                $query_params['search'] = $search;
                            }
                            
                            if (!empty($manufacturer_filter)) {
                                $query_params['manufacturer'] = $manufacturer_filter;
                            }
                            
                            $base_query = http_build_query(array_filter($query_params));
                            
                            for ($i = 1; $i <= $total_pages; $i++): 
                                $page_query = $base_query ? $base_query . '&p=' . $i : 'p=' . $i;
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $page_query ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Форма добавления/редактирования -->
            <div class="form-section">
                <form method="POST" action="products.php<?= $id ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
                    <ul class="nav nav-tabs mb-3" id="productTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#main" type="button">
                                Основное
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes" type="button">
                                Характеристики
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="sizes-tab" data-bs-toggle="tab" data-bs-target="#sizes" type="button">
                                Размеры
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="purpose-tab" data-bs-toggle="tab" data-bs-target="#purpose" type="button">
                                Назначение
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">
                                Описание
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Основное -->
                        <div class="tab-pane fade show active" id="main">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Название *</label>
                                        <input type="text" name="name" class="form-control" required 
                                               value="<?= htmlspecialchars($product['name'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Артикул *</label>
                                        <input type="text" name="article" class="form-control" required 
                                               value="<?= htmlspecialchars($product['article'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Модель</label>
                                        <input type="text" name="model" class="form-control" 
                                               value="<?= htmlspecialchars($product['model'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Код</label>
                                        <input type="text" name="code" class="form-control" 
                                               value="<?= htmlspecialchars($product['code'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">URL</label>
                                        <input type="url" name="url" class="form-control" 
                                               value="<?= htmlspecialchars($product['url'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Категория ID</label>
                                        <input type="text" name="categoryId" class="form-control" 
                                               value="<?= htmlspecialchars($product['categoryId'] ?? '30') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Категория название</label>
                                        <input type="text" name="category_name" class="form-control" 
                                               value="<?= htmlspecialchars($product['category_name'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Цена *</label>
                                        <input type="number" name="price" class="form-control" required step="0.01" 
                                               value="<?= htmlspecialchars($product['price'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Количество</label>
                                        <input type="number" name="quantity" class="form-control" 
                                               value="<?= htmlspecialchars($product['quantity'] ?? '1000') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Изображение URL</label>
                                        <input type="url" name="picture" class="form-control" 
                                               value="<?= htmlspecialchars($product['picture'] ?? '') ?>">
                                        <?php if (!empty($product['picture'])): ?>
                                            <img src="<?= htmlspecialchars($product['picture']) ?>" 
                                                 class="img-thumbnail mt-2" style="max-height: 100px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Характеристики -->
                        <div class="tab-pane fade" id="attributes">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Цвета</label>
                                        <div class="color-checkbox-list" id="colorList">
                                            <?php foreach ($colors_list as $color): 
                                                $is_selected = isset($selected_colors) && in_array($color['color_name'], $selected_colors);
                                            ?>
                                                <label class="color-checkbox-item <?= $is_selected ? 'selected' : '' ?>">
                                                    <input type="checkbox" name="attributes_color[]" 
                                                           value="<?= htmlspecialchars($color['color_name']) ?>"
                                                           <?= $is_selected ? 'checked' : '' ?>
                                                           onchange="updateSelectedColors(this)">
                                                    <div class="color-circle-small" style="background-color: <?= $color['color_code'] ?>; border: 2px solid <?= $color['color_code'] == '#FFFFFF' ? '#ddd' : $color['color_code'] ?>;"></div>
                                                    <span class="color-label"><?= htmlspecialchars($color['display_name'] ?: $color['color_name']) ?></span>
                                                    <span class="color-hint">(<?= htmlspecialchars($color['color_name']) ?>)</span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="selected-colors-preview" id="selectedColorsPreview">
                                            <strong>Выбранные цвета:</strong>
                                            <div id="selectedColorsTags">
                                                <?php if (isset($selected_colors) && !empty($selected_colors)): ?>
                                                    <?php foreach ($selected_colors as $color_name): 
                                                        $color_info = null;
                                                        foreach ($colors_list as $c) {
                                                            if ($c['color_name'] == $color_name) {
                                                                $color_info = $c;
                                                                break;
                                                            }
                                                        }
                                                    ?>
                                                        <span class="selected-color-tag">
                                                            <div class="color-dot" style="background-color: <?= $color_info['color_code'] ?? '#CCCCCC' ?>;"></div>
                                                            <?= htmlspecialchars($color_info['display_name'] ?? $color_name) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Ничего не выбрано</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">Выберите один или несколько цветов для товара</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Производитель</label>
                                        <input type="text" name="attributes_manufacturer" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_manufacturer'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Стиль</label>
                                        <input type="text" name="attributes_style" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_style'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Остекление</label>
                                        <input type="text" name="attributes_glazing" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_glazing'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Тип двери</label>
                                        <input type="text" name="attributes_door_type" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_door_type'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Толщина</label>
                                        <input type="text" name="attributes_thickness" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_thickness'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Покрытие</label>
                                        <input type="text" name="attributes_coating" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_coating'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Каркас</label>
                                        <input type="text" name="attributes_frame" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_frame'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Наполнение</label>
                                        <input type="text" name="attributes_filling" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_filling'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Кромка</label>
                                        <input type="text" name="attributes_edge" class="form-control" 
                                               value="<?= htmlspecialchars($product['attributes_edge'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Размеры -->
                        <div class="tab-pane fade" id="sizes">
                            <div class="mb-3">
                                <label class="form-label">Размеры (JSON массив)</label>
                                <textarea name="attributes_size" class="form-control" rows="5"><?= 
                                    htmlspecialchars(
                                        is_array($product['attributes_size'] ?? null) 
                                        ? json_encode($product['attributes_size'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                        : ($product['attributes_size'] ?? '["400*2000","600*2000","700*2000","800*2000"]')
                                    ) 
                                ?></textarea>
                                <small class="text-muted">Введите JSON массив размеров или каждый размер с ценой: [{"size":"800*2000","price":13546}]</small>
                            </div>
                            <div class="alert alert-info">
                                <strong>Пример простого массива:</strong><br>
                                ["400*2000","600*2000","700*2000","800*2000"]<br>
                                <strong>Пример с ценами:</strong><br>
                                [{"size":"800*2000","price":13546},{"size":"900*2000","price":14500}]
                            </div>
                        </div>

                        <!-- Назначение -->
                        <div class="tab-pane fade" id="purpose">
                            <div class="mb-3">
                                <label class="form-label">Назначение (JSON массив)</label>
                                <textarea name="attributes_purpose" class="form-control" rows="5"><?= 
                                    htmlspecialchars(
                                        is_array($product['attributes_purpose'] ?? null)
                                        ? json_encode($product['attributes_purpose'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                        : ($product['attributes_purpose'] ?? '["В комнату","В детскую","В квартиру"]')
                                    ) 
                                ?></textarea>
                            </div>
                        </div>

                        <!-- Описание -->
                        <div class="tab-pane fade" id="description">
                            <div class="mb-3">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="10"><?= 
                                    htmlspecialchars($product['description'] ?? '') 
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" name="save" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Сохранить
                        </button>
                        <a href="products.php?action=list" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Отмена
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить товар <strong id="deleteProductName"></strong>?</p>
                    <p class="text-danger">Это действие нельзя отменить!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="products.php" id="deleteForm">
                        <input type="hidden" name="id" id="deleteProductId">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Отмена
                        </button>
                        <button type="submit" name="delete" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Удалить
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function confirmDelete(id, name) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        function updateSelectedColors(checkbox) {
            const label = checkbox.closest('.color-checkbox-item');
            const colorName = checkbox.value;
            const displayName = label.querySelector('.color-label').textContent;
            const colorCode = label.querySelector('.color-circle-small').style.backgroundColor;
            
            if (checkbox.checked) {
                label.classList.add('selected');
                // Добавляем тег выбранного цвета
                const tagsContainer = document.getElementById('selectedColorsTags');
                const existingTag = tagsContainer.querySelector(`.selected-color-tag[data-color="${colorName}"]`);
                if (!existingTag) {
                    const tag = document.createElement('span');
                    tag.className = 'selected-color-tag';
                    tag.setAttribute('data-color', colorName);
                    tag.innerHTML = `
                        <div class="color-dot" style="background-color: ${colorCode};"></div>
                        ${displayName}
                    `;
                    tagsContainer.appendChild(tag);
                    
                    // Удаляем сообщение "Ничего не выбрано"
                    const emptyMsg = tagsContainer.querySelector('.text-muted');
                    if (emptyMsg) emptyMsg.remove();
                }
            } else {
                label.classList.remove('selected');
                // Удаляем тег выбранного цвета
                const tagsContainer = document.getElementById('selectedColorsTags');
                const tag = tagsContainer.querySelector(`.selected-color-tag[data-color="${colorName}"]`);
                if (tag) tag.remove();
                
                // Если не осталось выбранных цветов, показываем сообщение
                if (tagsContainer.children.length === 0) {
                    tagsContainer.innerHTML = '<span class="text-muted">Ничего не выбрано</span>';
                }
            }
        }
        
        // Функция для выбора всех цветов
        function selectAllColors() {
            const checkboxes = document.querySelectorAll('.color-checkbox-item input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    checkbox.checked = true;
                    updateSelectedColors(checkbox);
                }
            });
        }
        
        // Функция для снятия выбора всех цветов
        function deselectAllColors() {
            const checkboxes = document.querySelectorAll('.color-checkbox-item input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    checkbox.checked = false;
                    updateSelectedColors(checkbox);
                }
            });
        }

        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
        });
    </script>
</body>
</html>