<?php
// check_table.php - проверка структуры таблицы
require_once 'config.php';

$db = getDB();

echo "<h2>Проверка таблицы: " . TABLE_PRODUCTS . "</h2>";

// Показываем структуру таблицы
$sql = "DESCRIBE " . TABLE_PRODUCTS;
$stmt = $db->query($sql);
$structure = $stmt->fetchAll();

echo "<h3>Структура таблицы:</h3>";
echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>По умолчанию</th></tr></thead>";
foreach ($structure as $field) {
    echo "<tr>";
    echo "<td>{$field['Field']}</td>";
    echo "<td>{$field['Type']}</td>";
    echo "<td>{$field['Null']}</td>";
    echo "<td>{$field['Key']}</td>";
    echo "<td>{$field['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Показываем количество записей
$count_sql = "SELECT COUNT(*) as total FROM " . TABLE_PRODUCTS;
$stmt = $db->query($count_sql);
$total = $stmt->fetch()['total'];

echo "<h3>Статистика:</h3>";
echo "<p>Всего записей: <strong>$total</strong></p>";

// Показываем несколько записей для примера
$sample_sql = "SELECT id, name, price, categoryId FROM " . TABLE_PRODUCTS . " LIMIT 5";
$stmt = $db->query($sample_sql);
$samples = $stmt->fetchAll();

echo "<h3>Примеры товаров:</h3>";
echo "<table class='table'>";
echo "<thead><tr><th>ID</th><th>Название</th><th>Цена</th><th>Категория</th></tr></thead>";
foreach ($samples as $item) {
    echo "<tr>";
    echo "<td>{$item['id']}</td>";
    echo "<td>{$item['name']}</td>";
    echo "<td>{$item['price']}</td>";
    echo "<td>{$item['categoryId']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='index.php' class='btn btn-primary'>Вернуться в каталог</a>";
?>