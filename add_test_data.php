<?php
// add_test_data.php - добавление большего количества тестовых данных
require_once 'config.php';

$db = getDB();

// Пример дополнительных товаров
$test_products = [
    [
        'id' => 409,
        'code' => 'a1b2c3d4e5f6g7h8i9j0',
        'url' => 'https://luxor-dveri.ru/catalog/detail/olivia-dub-steklo/',
        'categoryId' => '29',
        'picture' => 'https://luxor-dveri.ru/upload/iblock/sample1.jpg',
        'name' => 'Оливия (дуб, стекло)',
        'price' => 14500,
        'class1' => 'Luxor (Шпон), Шпон натуральный, Филенчатые',
        'class2' => 'С прозрачным стеклом, Со стеклом',
        'class3' => 'Классические, Роскошные',
        'class4' => '7500',
        'class5' => '600х2000, 700х2000, 800х2000',
        'class6' => '15 001',
        'class7' => 'Дуб натуральный',
        'desctiption' => 'Страна: Россия<br>Материал: МДФ<br>Производитель: Luxor<br>Стиль: Классика'
    ],
    [
        'id' => 410,
        'code' => 'b2c3d4e5f6g7h8i9j0k1',
        'url' => 'https://luxor-dveri.ru/catalog/detail/olivia-dub-glukhaya/',
        'categoryId' => '29',
        'picture' => 'https://luxor-dveri.ru/upload/iblock/sample2.jpg',
        'name' => 'Оливия (дуб, глухая)',
        'price' => 13200,
        'class1' => 'Luxor (Шпон), Шпон натуральный, Филенчатые',
        'class2' => 'Глухие',
        'class3' => 'Классические, Роскошные',
        'class4' => '6800',
        'class5' => '600х2000, 700х2000, 800х2000',
        'class6' => '15 002',
        'class7' => 'Дуб натуральный',
        'desctiption' => 'Страна: Россия<br>Материал: МДФ<br>Производитель: Luxor<br>Стиль: Классика'
    ],
    [
        'id' => 411,
        'code' => 'c3d4e5f6g7h8i9j0k1l2',
        'url' => 'https://luxor-dveri.ru/catalog/detail/milano-svetlyy-dub/',
        'categoryId' => '30',
        'picture' => 'https://luxor-dveri.ru/upload/iblock/sample3.jpg',
        'name' => 'Милано (светлый дуб)',
        'price' => 15800,
        'class1' => 'Экошпон, Филенчатые',
        'class2' => 'Глухие',
        'class3' => 'Современные, Минимализм',
        'class4' => '8200',
        'class5' => '700х2000, 800х2000',
        'class6' => '16 001',
        'class7' => 'Светлый дуб',
        'desctiption' => 'Страна: Россия<br>Материал: МДФ<br>Производитель: Luxor<br>Стиль: Модерн'
    ]
];

$added_count = 0;

foreach ($test_products as $product) {
    // Проверяем, нет ли уже такого товара
    $check_sql = "SELECT COUNT(*) as count FROM " . TABLE_PRODUCTS . " WHERE id = :id";
    $stmt = $db->prepare($check_sql);
    $stmt->execute([':id' => $product['id']]);
    $exists = $stmt->fetch()['count'];
    
    if ($exists == 0) {
        $sql = "INSERT INTO " . TABLE_PRODUCTS . " (id, code, url, categoryId, picture, name, price, class1, class2, class3, class4, class5, class6, class7, desctiption) 
                VALUES (:id, :code, :url, :categoryId, :picture, :name, :price, :class1, :class2, :class3, :class4, :class5, :class6, :class7, :desctiption)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $product['id'],
            ':code' => $product['code'],
            ':url' => $product['url'],
            ':categoryId' => $product['categoryId'],
            ':picture' => $product['picture'],
            ':name' => $product['name'],
            ':price' => $product['price'],
            ':class1' => $product['class1'],
            ':class2' => $product['class2'],
            ':class3' => $product['class3'],
            ':class4' => $product['class4'],
            ':class5' => $product['class5'],
            ':class6' => $product['class6'],
            ':class7' => $product['class7'],
            ':desctiption' => $product['desctiption']
        ]);
        $added_count++;
    }
}

echo "<div class='alert alert-success'>Добавлено $added_count тестовых товаров!</div>";
echo "<p>Всего товаров в таблице " . TABLE_PRODUCTS . ": ";

$count_sql = "SELECT COUNT(*) as total FROM " . TABLE_PRODUCTS;
$stmt = $db->query($count_sql);
$total = $stmt->fetch()['total'];
echo "<strong>$total</strong></p>";

echo "<a href='index.php' class='btn btn-primary'>Вернуться в каталог</a> | ";
echo "<a href='install.php' class='btn btn-secondary'>Вернуться к установке</a>";
?>