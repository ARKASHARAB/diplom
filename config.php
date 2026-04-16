<?php
// config.php - конфигурация подключения к БД

// Параметры подключения к базе данных (для beget)
define('DB_HOST', 'localhost');
define('DB_NAME', 'n9998335_magaz');  // ваша база данных
define('DB_USER', 'n9998335_magaz');  // ваш пользователь БД
define('DB_PASS', 'Qwerty123');  // ваш пароль от БД

// Название таблицы с суффиксом _test1
define('TABLE_PRODUCTS', 'products_test1');

// Настройки сайта
define('SITE_NAME', 'ЛИГА ДВЕРЕЙ');
define('ITEMS_PER_PAGE', 12);
define('CURRENCY', ' руб.');
define('SITE_URL', 'https://vfd.ru');

// Путь к JSON файлу
define('JSON_FILE_PATH', __DIR__ . '/2.json');

// Запуск сессии, если она еще не запущена
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Подключение к БД
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    
    return $db;
}

// Функция для проверки авторизации - ЭТО НОВАЯ ФУНКЦИЯ
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для проверки прав администратора
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Перенаправление
function redirect($url) {
    header("Location: $url");
    exit;
}

// Очистка данных
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Генерация CSRF токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Функция для генерации URL
function generateProductUrl($name, $article) {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    return SITE_URL . '/catalog/detail/' . $slug . '-' . $article . '/';
}

// Функция для генерации кода
function generateCode($article) {
    return md5($article . time() . rand(1000, 9999));
}

// Функция для чтения JSON файла
function readJsonFile($file_path) {
    if (!file_exists($file_path)) {
        return ['error' => 'Файл не найден: ' . $file_path];
    }
    
    $json_content = file_get_contents($file_path);
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Ошибка парсинга JSON: ' . json_last_error_msg()];
    }
    
    return $data;
}

// Функция для обработки атрибутов (массив в строку)
function processAttributes($attributes) {
    $result = [];
    
    foreach ($attributes as $key => $value) {
        if (is_array($value)) {
            // Для массивов сохраняем как строку через запятую
            $result[$key] = implode(', ', $value);
        } else {
            $result[$key] = $value;
        }
    }
    
    return $result;
}

// Функция для извлечения категории из артикула
function extractCategoryId($article) {
    if (preg_match('/[A-Z](\d{4,})/', $article, $matches)) {
        return substr($matches[1], 0, 2) ?: '30';
    }
    return '30';
}

// Функция для генерации уникального артикула
function generateUniqueArticle($index) {
    return 'VFD-' . str_pad($index, 6, '0', STR_PAD_LEFT);
}
?>