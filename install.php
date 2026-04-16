<?php
// install.php - установка таблицы с улучшенной обработкой JSON
require_once 'config.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Установка каталога из JSON</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css'>
        <style>
            body { padding: 20px; background: #f8f9fa; }
            .container { max-width: 1200px; }
            .step { margin-bottom: 30px; padding: 25px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            .step-header { border-bottom: 2px solid #e9ecef; padding-bottom: 10px; margin-bottom: 20px; }
            .json-error { background: #fff5f5; color: #c92a2a; padding: 10px; border-radius: 5px; border-left: 4px solid #ff6b6b; font-family: monospace; }
            .json-line { margin: 2px 0; }
            .json-line.error { background: #ffc9c9; }
            .json-line-number { color: #868e96; padding-right: 10px; }
        </style>
    </head>
    <body>
    <div class='container'>
    <h1 class='text-center mb-4'><i class='bi bi-database'></i> Установка каталога из JSON</h1>";
    
    // Шаг 1: Проверка и загрузка JSON файла
    echo "<div class='step'>
            <div class='step-header'>
                <h3><i class='bi bi-file-earmark-json'></i> Шаг 1: Проверка JSON файла</h3>
            </div>";
    
    // Функция для улучшенной загрузки JSON
    function loadJsonWithValidation($file_path) {
        if (!file_exists($file_path)) {
            // Создаем пример JSON файла если его нет
            $example_json = createExampleJson();
            file_put_contents($file_path, $example_json);
            return ['info' => 'Файл создан автоматически с примером данных', 'data' => json_decode($example_json, true)];
        }
        
        $json_content = file_get_contents($file_path);
        
        // Проверяем, не пустой ли файл
        if (empty(trim($json_content))) {
            return ['error' => 'Файл пустой'];
        }
        
        // Пробуем декодировать JSON
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Пытаемся исправить JSON
            $fixed_json = fixJson($json_content);
            $data = json_decode($fixed_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Показываем подробную ошибку
                return [
                    'error' => 'Ошибка парсинга JSON: ' . json_last_error_msg(),
                    'details' => getJsonErrorDetails($json_content),
                    'raw_content' => $json_content
                ];
            }
            
            return [
                'warning' => 'JSON исправлен автоматически',
                'data' => $data,
                'original_error' => json_last_error_msg()
            ];
        }
        
        return ['data' => $data];
    }
    
    // Функция для исправления распространенных ошибок JSON
    function fixJson($json) {
        // 1. Удаляем BOM если есть
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
        
        // 2. Удаляем лишние запятые в конце массивов/объектов
        $json = preg_replace('/,\s*([\]}])/m', '$1', $json);
        
        // 3. Исправляем одинарные кавычки на двойные
        $json = preg_replace("/(?<!\\\\)'/", '"', $json);
        
        // 4. Добавляем закрывающие скобки если они отсутствуют
        $open_braces = substr_count($json, '{');
        $close_braces = substr_count($json, '}');
        $open_brackets = substr_count($json, '[');
        $close_brackets = substr_count($json, ']');
        
        if ($open_braces > $close_braces) {
            $json .= str_repeat('}', $open_braces - $close_braces);
        }
        
        if ($open_brackets > $close_brackets) {
            $json .= str_repeat(']', $open_brackets - $close_brackets);
        }
        
        // 5. Исправляем незакрытые строки
        $json = preg_replace('/:\s*([^"\s{}\[\],]+)(?=\s*[},])/', ':"$1"', $json);
        
        return $json;
    }
    
    // Функция для получения подробной информации об ошибке
    function getJsonErrorDetails($json_content) {
        $lines = explode("\n", $json_content);
        $details = [];
        
        // Находим примерную позицию ошибка
        $error_pos = json_last_error();
        
        for ($i = 0; $i < count($lines); $i++) {
            $line_number = $i + 1;
            $details[] = [
                'line' => $line_number,
                'content' => htmlspecialchars($lines[$i]),
                'has_error' => false
            ];
        }
        
        return $details;
    }
    
    // Функция для создания примера JSON
    function createExampleJson() {
        return '{
  "Пример категории": [
    {
      "category": "Пример категории",
      "Артикул": "TEST-001",
      "name": "Тестовый товар",
      "image": "https://via.placeholder.com/400x600",
      "description": "Пример описания товара",
      "price": 1000,
      "quantity": 10,
      "attributes": {
        "color": "Белый",
        "purpose": ["В комнату", "В кухню"],
        "glazing": "Полотно глухое",
        "manufacturer": "Тестовый производитель",
        "size": ["600*2000", "700*2000"],
        "thickness": "40 мм",
        "style": "Современный стиль"
      }
    }
  ]
}';
    }
    
    // Загружаем JSON
    $result = loadJsonWithValidation(JSON_FILE_PATH);
    
    if (isset($result['error'])) {
        echo "<div class='alert alert-danger'>
                <h5><i class='bi bi-exclamation-triangle'></i> Ошибка загрузки JSON</h5>
                <p>{$result['error']}</p>";
        
        if (isset($result['details'])) {
            echo "<h6>Содержимое файла (первые 50 строк):</h6>
                  <div class='json-error' style='max-height: 300px; overflow-y: auto;'>";
            
            $lines = explode("\n", $result['raw_content']);
            $total_lines = count($lines);
            
            echo "<div class='mb-2'><strong>Всего строк: $total_lines</strong></div>";
            
            // Показываем первые 50 строк
            $show_lines = min(50, $total_lines);
            for ($i = 0; $i < $show_lines; $i++) {
                $line_number = $i + 1;
                $content = htmlspecialchars($lines[$i]);
                echo "<div class='json-line'><span class='json-line-number'>$line_number:</span> $content</div>";
            }
            
            if ($total_lines > 50) {
                echo "<div class='text-muted'>... и еще " . ($total_lines - 50) . " строк</div>";
            }
            
            echo "</div>";
            
            // Кнопка для скачивания файла для отладки
            echo "<div class='mt-3'>
                    <a href='?action=download_json' class='btn btn-outline-danger btn-sm'>
                        <i class='bi bi-download'></i> Скачать JSON для отладки
                    </a>
                    <a href='?action=create_example' class='btn btn-outline-primary btn-sm ms-2'>
                        <i class='bi bi-plus-circle'></i> Создать пример JSON
                    </a>
                  </div>";
        }
        
        echo "</div></body></html>";
        exit();
    }
    
    if (isset($result['warning'])) {
        echo "<div class='alert alert-warning'>
                <h5><i class='bi bi-exclamation-triangle'></i> {$result['warning']}</h5>
                <p>Оригинальная ошибка: {$result['original_error']}</p>
                <p>JSON был автоматически исправлен.</p>
              </div>";
    }
    
    if (isset($result['info'])) {
        echo "<div class='alert alert-info'>
                <h5><i class='bi bi-info-circle'></i> {$result['info']}</h5>
              </div>";
    }
    
    $json_data = $result['data'];
    
    // Определяем структуру данных
    $is_associative = false;
    $total_products = 0;
    $categories = [];
    $products_data = [];

    // Проверяем структуру JSON
    if (isset($json_data[0]) && is_array($json_data[0])) {
        // Формат: [ {product1}, {product2}, ... ] - ПРОСТОЙ МАССИВ
        $products_data = $json_data;
        $is_associative = false;
        $total_products = count($products_data);
        
        // Извлекаем категории из продуктов
        foreach ($products_data as $product) {
            if (isset($product['category']) && !in_array($product['category'], $categories)) {
                $categories[] = $product['category'];
            }
        }
    } else {
        // Формат: { "Категория1": [{product1}, ...], "Категория2": [...] } - АССОЦИАТИВНЫЙ МАССИВ
        $products_data = [];
        foreach ($json_data as $category_name => $category_products) {
            if (is_array($category_products)) {
                foreach ($category_products as $product) {
                    $products_data[] = $product;
                }
                $categories[] = $category_name;
            }
        }
        $is_associative = true;
        $total_products = count($products_data);
    }
    
    echo "<div class='alert alert-success'>
            <h5><i class='bi bi-check-circle'></i> JSON файл успешно загружен!</h5>
            <p>Формат: " . ($is_associative ? 'Ассоциативный массив (по категориям)' : 'Простой массив') . "</p>
            <p>Найдено товаров: <strong>$total_products</strong></p>";
    
    if (!empty($categories)) {
        echo "<p>Категории: <strong>" . implode(', ', $categories) . "</strong></p>";
    }
    
    echo "</div>";
    
    // Показываем превью первого товара
    if ($total_products > 0) {
        echo "<h5>Превью первого товара:</h5>
              <div style='max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;'>
              <pre style='margin: 0; font-size: 0.85rem;'>" . 
              htmlspecialchars(json_encode($products_data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . 
              "</pre></div>";
    }
    
    echo "</div>";
    
    // Шаг 2: Создание таблицы
    echo "<div class='step'>
            <div class='step-header'>
                <h3><i class='bi bi-table'></i> Шаг 2: Создание таблицы</h3>
            </div>
            <p>Создаем таблицу: <code>" . TABLE_PRODUCTS . "</code></p>";
    
    // Создание таблицы с улучшенной структурой
    $sql = "CREATE TABLE IF NOT EXISTS `" . TABLE_PRODUCTS . "` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `article` VARCHAR(100) NOT NULL,
        `model` VARCHAR(100),
        `code` VARCHAR(255) NOT NULL,
        `url` TEXT NOT NULL,
        `categoryId` VARCHAR(50) NOT NULL,
        `category_name` VARCHAR(255),
        `picture` TEXT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT DEFAULT 0,
        
        -- Основные атрибуты
        `attributes_color` VARCHAR(150),
        `attributes_purpose` TEXT,
        `attributes_glazing` VARCHAR(150),
        `attributes_edge` TEXT,
        `attributes_frame` VARCHAR(150),
        `attributes_filling` VARCHAR(150),
        `attributes_coating` VARCHAR(150),
        `attributes_manufacturer` VARCHAR(150),
        `attributes_size` TEXT,
        `attributes_thickness` VARCHAR(100),
        `attributes_style` VARCHAR(150),
        
        -- Дополнительные атрибуты
        `attributes_colors_group` VARCHAR(150),
        `attributes_description` TEXT,
        `attributes_features` TEXT,
        `attributes_finish` TEXT,
        `attributes_material` TEXT,
        `attributes_edge_tech` TEXT,
        `attributes_glass` TEXT,
        `attributes_thickness_num` VARCHAR(50),
        `attributes_door_type` VARCHAR(150),
        `attributes_materials` VARCHAR(255),
        `attributes_width` VARCHAR(100),
        `attributes_height` VARCHAR(100),
        `attributes_wall_thickness` VARCHAR(100),
        
        -- Общие поля для фильтрации
        `class1` TEXT,
        `class2` TEXT,
        `class3` TEXT,
        `class4` TEXT,
        `class5` TEXT,
        `class6` TEXT,
        `class7` TEXT,
        `description` TEXT,
        
        -- Временные метки
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Индексы для ускорения поиска
        INDEX idx_article (article),
        INDEX idx_category (categoryId),
        INDEX idx_price (price),
        INDEX idx_name (name),
        INDEX idx_color (attributes_color),
        INDEX idx_manufacturer (attributes_manufacturer),
        INDEX idx_style (attributes_style),
        INDEX idx_door_type (attributes_door_type),
        INDEX idx_quantity (quantity),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> Таблица '" . TABLE_PRODUCTS . "' успешно создана/обновлена!</div>";
    
    // Проверяем, нужны ли дополнительные столбцы
    echo "<div class='mt-3'>
            <h6>Структура таблицы:</h6>
            <div class='table-responsive'>
              <table class='table table-sm table-bordered'>
                <thead>
                  <tr>
                    <th>Поле</th>
                    <th>Тип</th>
                    <th>Описание</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>article</td><td>VARCHAR(100)</td><td>Артикул товара</td></tr>
                  <tr><td>name</td><td>VARCHAR(255)</td><td>Название товара</td></tr>
                  <tr><td>price</td><td>DECIMAL(10,2)</td><td>Цена</td></tr>
                  <tr><td>quantity</td><td>INT</td><td>Количество на складе</td></tr>
                  <tr><td>attributes_color</td><td>VARCHAR(150)</td><td>Цвет</td></tr>
                  <tr><td>attributes_manufacturer</td><td>VARCHAR(150)</td><td>Производитель</td></tr>
                  <tr><td>attributes_size</td><td>TEXT</td><td>Размеры</td></tr>
                  <tr><td>created_at</td><td>TIMESTAMP</td><td>Дата создания</td></tr>
                </tbody>
              </table>
            </div>
          </div>";
    
    echo "</div>";
    
    // Шаг 3: Обработка данных
    echo "<div class='step'>
            <div class='step-header'>
                <h3><i class='bi bi-gear'></i> Шаг 3: Настройка загрузки</h3>
            </div>";
    
    // Проверяем существующие данные
    $check_sql = "SELECT COUNT(*) as count FROM " . TABLE_PRODUCTS;
    $stmt = $db->query($check_sql);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        echo "<div class='alert alert-warning'>
                <h5><i class='bi bi-exclamation-triangle'></i> В таблице уже есть $count записей</h5>
                <p class='mb-3'>Выберите действие:</p>
                <form method='POST' class='mb-3'>
                  <div class='row'>
                    <div class='col-md-4 mb-2'>
                      <button type='submit' name='action' value='clear_all' class='btn btn-danger w-100'>
                        <i class='bi bi-trash'></i> Очистить всё
                      </button>
                      <small class='text-muted d-block mt-1'>Удалить все данные и загрузить заново</small>
                    </div>
                    <div class='col-md-4 mb-2'>
                      <button type='submit' name='action' value='add_new' class='btn btn-warning w-100'>
                        <i class='bi bi-plus-circle'></i> Добавить все как новые
                      </button>
                      <small class='text-muted d-block mt-1'>Добавить все товары как новые записи (даже дубликаты)</small>
                    </div>
                    <div class='col-md-4 mb-2'>
                      <button type='submit' name='action' value='skip' class='btn btn-secondary w-100'>
                        <i class='bi bi-skip-forward'></i> Пропустить
                      </button>
                      <small class='text-muted d-block mt-1'>Не загружать данные</small>
                    </div>
                  </div>
                </form>
              </div>";
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'clear_all':
                    $db->exec("TRUNCATE TABLE " . TABLE_PRODUCTS);
                    echo "<div class='alert alert-info'><i class='bi bi-info-circle'></i> Таблица очищена. Продолжаем загрузку...</div>";
                    $count = 0;
                    break;
                case 'add_new':
                    echo "<div class='alert alert-info'><i class='bi bi-info-circle'></i> Будет выполнена загрузка ВСЕХ товаров как новых записей...</div>";
                    break;
                case 'skip':
                    echo "<div class='alert alert-info'><i class='bi bi-info-circle'></i> Загрузка пропущена.</div>";
                    $count = -1; // Флаг для пропуска
                    break;
            }
        } else {
            echo "</div></div></body></html>";
            exit();
        }
    }
    
    echo "</div>";
    
    // Шаг 4: Загрузка данных
    if ($count == 0 || (isset($_POST['action']) && $_POST['action'] == 'add_new')) {
        // Определяем вспомогательные функции только внутри этого блока
        if (!function_exists('generateProductUrl')) {
            function generateProductUrl($name, $article) {
                $translit = [
                    'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
                    'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
                    'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                    'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
                    'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
                    'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
                    'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
                ];
                
                $url = mb_strtolower($name, 'UTF-8');
                $url = strtr($url, $translit);
                $url = preg_replace('/[^a-z0-9\-]/', '-', $url);
                $url = preg_replace('/-+/', '-', $url);
                $url = trim($url, '-');
                
                return '/product/' . $url . '-' . $article;
            }
        }
        
        if (!function_exists('extractCategoryId')) {
            function extractCategoryId($article) {
                if (preg_match('/[A-Z]+-?\d+/', $article, $matches)) {
                    return $matches[0];
                }
                return substr(md5($article), 0, 8);
            }
        }
        
        if (!function_exists('processAttribute')) {
            function processAttribute($attributes, $key, $is_array = false) {
                if (!isset($attributes[$key])) {
                    return null;
                }
                
                $value = $attributes[$key];
                
                if ($is_array && is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                
                return $value;
            }
        }
        
        echo "<div class='step'>
                <div class='step-header'>
                    <h3><i class='bi bi-upload'></i> Шаг 4: Загрузка данных</h3>
                </div>";
        
        $added_count = 0;
        $duplicate_count = 0;
        $errors = [];
        $batch_size = 50;
        $stats = [
            'categories' => [],
            'manufacturers' => [],
            'colors' => []
        ];
        
        echo "<div class='progress mb-3' style='height: 25px;'>
                <div class='progress-bar progress-bar-striped progress-bar-animated' 
                     id='progress-bar' role='progressbar' style='width: 0%'></div>
              </div>
              <div id='progress-text' class='text-center mb-3'>Подготовка к загрузке...</div>";
        
        $total_products = count($products_data);
        $total_batches = ceil($total_products / $batch_size);
        
        for ($batch = 0; $batch < $total_batches; $batch++) {
            $start = $batch * $batch_size;
            $batch_products = array_slice($products_data, $start, $batch_size);
            $current_batch = $batch + 1;
            
            echo "<script>
                    document.getElementById('progress-bar').style.width = '" . (($current_batch / $total_batches) * 100) . "%';
                    document.getElementById('progress-text').innerHTML = 'Загрузка: $current_batch из $total_batches пачек';
                  </script>";
            flush();
            
            foreach ($batch_products as $index => $product) {
                $global_index = $start + $index;
                
                try {
                    // Извлекаем данные с проверками
                    $article = isset($product['Артикул']) ? trim($product['Артикул']) : '';
                    $name = isset($product['name']) ? trim($product['name']) : 'Товар без названия';
                    $category_name = isset($product['category']) ? trim($product['category']) : 'Без категории';
                    $price = isset($product['price']) ? floatval($product['price']) : 0;
                    
                    // Генерируем уникальный артикул, если он "Не указан" или пустой
                    if ($article === 'Не указан' || empty($article)) {
                        $article = 'VFD-' . str_pad($global_index + 1, 6, '0', STR_PAD_LEFT);
                    }
                    
                    // Делаем артикул уникальным для каждого товара
                    $unique_article = $article . '-' . ($global_index + 1);
                    
                    // Остальная обработка данных...
                    $image = isset($product['image']) ? trim($product['image']) : '';
                    $description = isset($product['description']) ? trim($product['description']) : '';
                    $quantity = isset($product['quantity']) ? intval($product['quantity']) : 0;
                    
                    // Генерируем вспомогательные данные
                    $code = md5($unique_article . $name . $global_index . time());
                    $url = generateProductUrl($name, $unique_article);
                    $category_id = extractCategoryId($unique_article);
                    
                    // Обрабатываем атрибуты
                    $attributes = isset($product['attributes']) ? $product['attributes'] : [];
                    
                    // Собираем данные для вставки
                    $insert_data = [
                        'article' => $unique_article,
                        'model' => $article,
                        'code' => $code,
                        'url' => $url,
                        'categoryId' => $category_id,
                        'category_name' => $category_name,
                        'picture' => $image,
                        'name' => $name,
                        'price' => $price,
                        'quantity' => $quantity,
                        'description' => $description,
                        
                        // Обрабатываем атрибуты
                        'attributes_color' => processAttribute($attributes, 'color'),
                        'attributes_purpose' => processAttribute($attributes, 'purpose', true),
                        'attributes_glazing' => processAttribute($attributes, 'glazing'),
                        'attributes_edge' => processAttribute($attributes, 'edge'),
                        'attributes_frame' => processAttribute($attributes, 'frame'),
                        'attributes_filling' => processAttribute($attributes, 'filling'),
                        'attributes_coating' => processAttribute($attributes, 'coating'),
                        'attributes_manufacturer' => processAttribute($attributes, 'manufacturer'),
                        'attributes_size' => processAttribute($attributes, 'size', true),
                        'attributes_thickness' => processAttribute($attributes, 'thickness'),
                        'attributes_style' => processAttribute($attributes, 'style'),
                        'attributes_colors_group' => processAttribute($attributes, 'colors_group'),
                        'attributes_description' => processAttribute($attributes, 'description'),
                        'attributes_features' => processAttribute($attributes, 'features'),
                        'attributes_finish' => processAttribute($attributes, 'finish'),
                        'attributes_material' => processAttribute($attributes, 'material'),
                        'attributes_edge_tech' => processAttribute($attributes, 'edge_tech'),
                        'attributes_glass' => processAttribute($attributes, 'glass'),
                        'attributes_thickness_num' => processAttribute($attributes, 'thickness_num'),
                        'attributes_door_type' => processAttribute($attributes, 'door_type'),
                        'attributes_materials' => processAttribute($attributes, 'materials'),
                        'attributes_width' => processAttribute($attributes, 'width'),
                        'attributes_height' => processAttribute($attributes, 'height'),
                        'attributes_wall_thickness' => processAttribute($attributes, 'wall_thickness')
                    ];
                    
                    // Обновляем статистику
                    if ($insert_data['attributes_color']) {
                        $color = $insert_data['attributes_color'];
                        $stats['colors'][$color] = isset($stats['colors'][$color]) ? $stats['colors'][$color] + 1 : 1;
                    }
                    
                    if ($insert_data['attributes_manufacturer']) {
                        $manufacturer = $insert_data['attributes_manufacturer'];
                        $stats['manufacturers'][$manufacturer] = isset($stats['manufacturers'][$manufacturer]) ? $stats['manufacturers'][$manufacturer] + 1 : 1;
                    }
                    
                    if ($category_name) {
                        $stats['categories'][$category_name] = isset($stats['categories'][$category_name]) ? $stats['categories'][$category_name] + 1 : 1;
                    }
                    
                    // Проверяем, не является ли это дубликатом (только для информации)
                    $check_sql = "SELECT COUNT(*) as count FROM " . TABLE_PRODUCTS . " 
                                  WHERE name = :name 
                                  AND category_name = :category_name 
                                  AND ABS(price - :price) < 0.01
                                  AND article LIKE :article_pattern";
                    
                    $stmt = $db->prepare($check_sql);
                    $stmt->execute([
                        ':name' => $name,
                        ':category_name' => $category_name,
                        ':price' => $price,
                        ':article_pattern' => str_replace('-1', '', $article) . '%'
                    ]);
                    
                    $existing_count = $stmt->fetch()['count'];
                    
                    if ($existing_count > 0) {
                        $duplicate_count++;
                        // Все равно вставляем как новый товар с уникальным артикулом
                        $insert_data['article'] = $unique_article . '-dup' . ($duplicate_count + 1);
                        $insert_data['code'] = md5($insert_data['article'] . $name . $global_index . time());
                    }
                    
                    // Вставляем запись
                    $fields = array_keys($insert_data);
                    $placeholders = array_map(function($field) { return ":$field"; }, $fields);
                    
                    $sql = "INSERT INTO " . TABLE_PRODUCTS . " (" . implode(', ', $fields) . ") 
                            VALUES (" . implode(', ', $placeholders) . ")";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($insert_data);
                    
                    $added_count++;
                    
                } catch (Exception $e) {
                    $errors[] = "Товар #$global_index ($name): " . $e->getMessage();
                }
            }
        }
        
        // Показываем итоги
        echo "<script>
                document.getElementById('progress-bar').classList.remove('progress-bar-animated');
                document.getElementById('progress-bar').classList.add('bg-success');
                document.getElementById('progress-text').innerHTML = '<strong>Загрузка завершена!</strong>';
              </script>";
        
        echo "<div class='alert alert-success mt-3'>
                <h5><i class='bi bi-check-circle'></i> Загрузка данных завершена!</h5>
                <p>Добавлено: <strong>$added_count</strong> товаров</p>
                <p>Найдено дубликатов: <strong>$duplicate_count</strong> (загружены как отдельные товары)</p>";
        
        if (!empty($errors)) {
            $error_count = count($errors);
            echo "<p>Ошибок при загрузке: <strong>$error_count</strong></p>";
            if ($error_count <= 5) {
                echo "<div class='alert alert-danger mt-2'><h6>Ошибки:</h6><ul>";
                foreach ($errors as $error) {
                    echo "<li>$error</li>";
                }
                echo "</ul></div>";
            }
        }
        
        echo "</div>";
        
        // Показываем статистику
        echo "<div class='row mt-3'>
                <div class='col-md-4'>
                  <div class='card'>
                    <div class='card-body'>
                      <h6 class='card-title'><i class='bi bi-tags'></i> Категории</h6>
                      <p class='card-text'>" . count($stats['categories']) . " категорий</p>
                      <ul class='list-unstyled'>";
        
        arsort($stats['categories']);
        $i = 0;
        foreach ($stats['categories'] as $cat => $count) {
            if ($i < 3) {
                echo "<li><small>$cat: $count</small></li>";
                $i++;
            }
        }
        
        echo "      </ul>
                    </div>
                  </div>
                </div>
                
                <div class='col-md-4'>
                  <div class='card'>
                    <div class='card-body'>
                      <h6 class='card-title'><i class='bi bi-building'></i> Производители</h6>
                      <p class='card-text'>" . count($stats['manufacturers']) . " производителей</p>
                      <ul class='list-unstyled'>";
        
        arsort($stats['manufacturers']);
        $i = 0;
        foreach ($stats['manufacturers'] as $man => $count) {
            if ($i < 3) {
                echo "<li><small>$man: $count</small></li>";
                $i++;
            }
        }
        
        echo "      </ul>
                    </div>
                  </div>
                </div>
                
                <div class='col-md-4'>
                  <div class='card'>
                    <div class='card-body'>
                      <h6 class='card-title'><i class='bi bi-palette'></i> Цвета</h6>
                      <p class='card-text'>" . count($stats['colors']) . " цветов</p>
                      <ul class='list-unstyled'>";
        
        arsort($stats['colors']);
        $i = 0;
        foreach ($stats['colors'] as $color => $count) {
            if ($i < 3) {
                echo "<li><small>$color: $count</small></li>";
                $i++;
            }
        }
        
        echo "      </ul>
                    </div>
                  </div>
                </div>
              </div>";
        
        echo "</div>";
    }
    
    // Шаг 5: Завершение
    echo "<div class='step'>
            <div class='step-header'>
                <h3><i class='bi bi-flag'></i> Шаг 5: Завершение установки</h3>
            </div>
            
            <div class='alert alert-success'>
                <h4><i class='bi bi-check-circle'></i> Установка завершена!</h4>
                <p>База данных готова к использованию.</p>
            </div>
            
            <div class='row'>
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-body'>
                            <h5 class='card-title'><i class='bi bi-door-open'></i> Перейти к каталогу</h5>
                            <p class='card-text'>Откройте каталог товаров для просмотра</p>
                            <a href='index.php' class='btn btn-success w-100'>
                                <i class='bi bi-door-open'></i> Открыть каталог
                            </a>
                        </div>
                    </div>
                </div>
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-body'>
                            <h5 class='card-title'><i class='bi bi-shield-exclamation'></i> Безопасность</h5>
                            <p class='card-text'>Рекомендуемые действия после установки</p>
                            <div class='alert alert-warning'>
                                <small>
                                    <i class='bi bi-exclamation-triangle'></i> 
                                    Удалите файл <code>install.php</code> для безопасности
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class='text-center mt-4'>
                <a href='index.php' class='btn btn-primary btn-lg'>
                    <i class='bi bi-rocket-takeoff'></i> Начать использование каталога
                </a>
            </div>
          </div>";
    
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'><h4>Ошибка установки:</h4><p>" . $e->getMessage() . "</p></div>");
}
?>