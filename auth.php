<?php
// auth.php - обработка регистрации и авторизации


$db = getDB();
$error = '';
$success = '';

// Регистрация
if (isset($_POST['register'])) {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = clean($_POST['full_name']);
    
    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        // Проверка существования пользователя
        $stmt = $db->prepare("SELECT id FROM admindoor1_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            // Хеширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Вставка нового пользователя
            $stmt = $db->prepare("INSERT INTO admindoor1_users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'user')");
            
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка при регистрации';
            }
        }
    }
}

// Авторизация
if (isset($_POST['login'])) {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $db->prepare("SELECT * FROM admindoor1_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Обновление времени последнего входа
            $db->prepare("UPDATE admindoor1_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            // Логирование
            $db->prepare("INSERT INTO admindoor1_logs (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)")
                ->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
            redirect('index.php');
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}

// Выход
if (isset($_GET['logout'])) {
    if (isLoggedIn()) {
        // Логирование выхода
        $db->prepare("INSERT INTO admindoor1_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)")
            ->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    }
    
    session_destroy();
    redirect('index.php');
}
?>