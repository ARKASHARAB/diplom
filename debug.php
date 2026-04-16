<?php
// debug.php - отладка установки
require_once 'config.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Отладка установки</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
    <div class='container mt-4'>
    <h1>Отладка установки</h1>";
    
    // 1. Проверка подключения к БД
    echo "<div class='card mb-3'>
            <div class='card-header'>1. Проверка подключения к БД</div>
            <div class='card-body'>";
    
    try {
        $test = $db->query("SELECT 1");
        echo "<div class='alert alert-success'>✅ Подключение к БД успешно</div>";
        echo "<p>Хост: " . DB_HOST . "</p>";
        echo "<p>База данных: " . DB_NAME . "</p>";
        echo "<p>Таблица: " . TABLE_PRODUCTS . "</p>";
    } catch (Exception $e) {
        die("<div class='alert alert-danger'>❌ Ошибка подключения: " . $e->getMessage() . "</div>");
    }
    
    echo "</div></div>";
    
    // 2. Проверка существования таблицы
    echo "<div class='card mb-3'>
            <div class='card-header'>2. Проверка таблицы " . TABLE_PRODUCTS . "</div>
            <div class='card-body'>";
    
    $table_check = $db->query("SHOW TABLES LIKE '" . TABLE_PRODUCTS . "'")->fetch();
    
    if ($table_check) {
        echo "<div class='alert alert-success'>✅ Таблица существует</div>";
        
        // Показываем структуру
        $structure = $db->query("DESCRIBE " . TABLE_PRODUCTS)->fetchAll();
        echo "<h6>Структура таблицы:</h6>
              <div class='table-responsive'>
                <table class='table table-sm'>
                  <tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th></tr>";
        
        foreach ($structure as $field) {
            echo "<tr>
                    <td>{$field['Field']}</td>
                    <td>{$field['Type']}</td>
                    <td>{$field['Null']}</td>
                    <td>{$field['Key']}</td>
                  </tr>";
        }
        
        echo "</table></div>";
        
        // Проверяем количество записей
        $count = $db->query("SELECT COUNT(*) as cnt FROM " . TABLE_PRODUCTS)->fetch()['cnt'];
        echo "<p>Записей в таблице: <strong>$count</strong></p>";
        
        if ($count > 0) {
            // Показываем несколько записей
            $sample = $db->query("SELECT id, name, article, price FROM " . TABLE_PRODUCTS . " LIMIT 5")->fetchAll();
            echo "<h6>Примеры записей:</h6>
                  <table class='table table-sm'>
                    <tr><th>ID</th><th>Название</th><th>Артикул</th><th>Цена</th></tr>";
            
            foreach ($sample as $row) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['article']}</td>
                        <td>{$row['price']}</td>
                      </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ Таблица пустая</div>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>❌ Таблица не существует</div>";
        echo "<p><a href='install.php' class='btn btn-primary'>Запустить установку</a></p>";
    }
    
    echo "</div></div>";
    
    // 3. Проверка JSON файла
    echo "<div class='card mb-3'>
            <div class='card-header'>3. Проверка файла products.json</div>
            <div class='card-body'>";
    
    if (file_exists(JSON_FILE_PATH)) {
        $size = filesize(JSON_FILE_PATH);
        $content = file_get_contents(JSON_FILE_PATH);
        $data = json_decode($content, true);
        
        echo "<div class='alert alert-success'>✅ Файл найден</div>";
        echo "<p>Размер: " . number_format($size) . " байт</p>";
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<div class='alert alert-success'>✅ JSON валидный</div>";
            
            // Определяем структуру
            if (isset($data[0])) {
                echo "<p>Формат: Массив товаров</p>";
                echo "<p>Товаров: " . count($data) . "</p>";
            } else {
                echo "<p>Формат: Ассоциативный массив (по категориям)</p>";
                $total = 0;
                foreach ($data as $category => $items) {
                    if (is_array($items)) {
                        $total += count($items);
                    }
                }
                echo "<p>Товаров: $total</p>";
                echo "<p>Категорий: " . count(array_keys($data)) . "</p>";
            }
            
            // Показываем первый товар
            echo "<h6>Первый товар в JSON:</h6>
                  <pre style='max-height: 300px; overflow: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;'>" 
                  . htmlspecialchars(json_encode(is_array($data) ? reset($data) : $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) 
                  . "</pre>";
        } else {
            echo "<div class='alert alert-danger'>❌ Ошибка JSON: " . json_last_error_msg() . "</div>";
            echo "<p><a href='install.php' class='btn btn-warning'>Попробовать исправить</a></p>";
        }
    } else {
        echo "<div class='alert alert-danger'>❌ Файл не найден: " . JSON_FILE_PATH . "</div>";
        echo "<p>Создайте файл products.json в папке с install.php</p>";
    }
    
    echo "</div></div>";
    
    // 4. Проверка функций config.php
    echo "<div class='card mb-3'>
            <div class='card-header'>4. Проверка функций из config.php</div>
            <div class='card-body'>";
    
    // Проверяем функции
    echo "<h6>Тест функций:</h6>";
    
    $test_article = "TEST-001";
    $test_name = "Test Product";
    
    echo "<p>generateCode('$test_article'): " . generateCode($test_article) . "</p>";
    echo "<p>generateProductUrl('$test_name', '$test_article'): " . generateProductUrl($test_name, $test_article) . "</p>";
    echo "<p>extractCategoryId('$test_article'): " . extractCategoryId($test_article) . "</p>";
    
    // Тест обработки атрибутов
    $test_attrs = [
        'color' => 'Белый',
        'purpose' => ['В комнату', 'В кухню'],
        'manufacturer' => 'VFD'
    ];
    
    echo "<p>processAttributes: ";
    print_r(processAttributes($test_attrs));
    echo "</p>";
    
    echo "</div></div>";
    
    // 5. Быстрая загрузка тестовых данных
    echo "<div class='card mb-3'>
            <div class='card-header'>5. Быстрая загрузка тестовых данных</div>
            <div class='card-body'>";
    
    $count = $db->query("SELECT COUNT(*) as cnt FROM " . TABLE_PRODUCTS)->fetch()['cnt'];
    
    if ($count == 0) {
        echo "<p>Таблица пустая. Хотите добавить тестовые данные?</p>";
        echo "<form method='POST'>
                <button type='submit' name='add_test' class='btn btn-primary'>Добавить 5 тестовых товаров</button>
              </form>";
        
        if (isset($_POST['add_test'])) {
            $test_products = [
                [
                    'article' => 'TEST-001',
                    'name' => 'Тестовая дверь 1',
                    'category' => 'Тест',
                    'image' => 'https://via.placeholder.com/400x600',
                    'description' => 'Тестовое описание',
                    'price' => 1000,
                    'quantity' => 10,
                    'attributes' => [
                        'color' => 'Белый',
                        'manufacturer' => 'VFD',
                        'size' => ['600*2000']
                    ]
                ],
                [
                    'article' => 'TEST-002',
                    'name' => 'Тестовая дверь 2',
                    'category' => 'Тест',
                    'image' => 'https://via.placeholder.com/400x600',
                    'description' => 'Тестовое описание 2',
                    'price' => 1500,
                    'quantity' => 5,
                    'attributes' => [
                        'color' => 'Черный',
                        'manufacturer' => 'ZADOOR',
                        'size' => ['700*2000']
                    ]
                ]
            ];
            
            $added = 0;
            foreach ($test_products as $product) {
                try {
                    $code = generateCode($product['article']);
                    $url = generateProductUrl($product['name'], $product['article']);
                    $category_id = extractCategoryId($product['article']);
                    
                    $sql = "INSERT INTO " . TABLE_PRODUCTS . " 
                            (article, code, url, categoryId, category_name, picture, name, price, quantity, description,
                             attributes_color, attributes_manufacturer, attributes_size)
                            VALUES 
                            (:article, :code, :url, :categoryId, :category_name, :picture, :name, :price, :quantity, :description,
                             :color, :manufacturer, :size)";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':article' => $product['article'],
                        ':code' => $code,
                        ':url' => $url,
                        ':categoryId' => $category_id,
                        ':category_name' => $product['category'],
                        ':picture' => $product['image'],
                        ':name' => $product['name'],
                        ':price' => $product['price'],
                        ':quantity' => $product['quantity'],
                        ':description' => $product['description'],
                        ':color' => $product['attributes']['color'],
                        ':manufacturer' => $product['attributes']['manufacturer'],
                        ':size' => is_array($product['attributes']['size']) ? 
                                   implode(', ', $product['attributes']['size']) : 
                                   $product['attributes']['size']
                    ]);
                    
                    $added++;
                    echo "<div class='alert alert-success'>✅ Добавлен: {$product['name']}</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Ошибка: " . $e->getMessage() . "</div>";
                }
            }
            
            echo "<div class='alert alert-info'>Добавлено $added тестовых товаров</div>";
            echo "<meta http-equiv='refresh' content='2'>";
        }
    } else {
        echo "<div class='alert alert-info'>В таблице уже есть $count записей</div>";
    }
    
    echo "</div></div>";
    
    // 6. Ссылки
    echo "<div class='card'>
            <div class='card-header'>6. Быстрые ссылки</div>
            <div class='card-body'>
                <a href='install.php' class='btn btn-primary'>install.php</a>
                <a href='index.php' class='btn btn-success'>index.php (каталог)</a>
                <a href='json_validator.php' class='btn btn-warning'>json_validator.php</a>
            </div>
          </div>";
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Ошибка: " . $e->getMessage() . "</div>");
}
?>