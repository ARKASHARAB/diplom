<?php
// add_admin.php - скрипт для добавления администратора
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Функция для безопасного вывода
function showMessage($text, $type = 'info') {
    $colors = [
        'success' => "\033[32m", // зеленый
        'error' => "\033[31m",   // красный
        'info' => "\033[36m",    // голубой
        'warning' => "\033[33m"  // желтый
    ];
    $reset = "\033[0m";
    
    if (php_sapi_name() === 'cli') {
        // Консольный режим
        echo $colors[$type] . $text . $reset . "\n";
    } else {
        // Веб-режим
        $alert_types = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'info' => 'alert-info',
            'warning' => 'alert-warning'
        ];
        echo "<div class='alert {$alert_types[$type]}'>$text</div>";
    }
}

$db = getDB();
$message = '';
$messageType = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = clean($_POST['email'] ?? '');
    $full_name = clean($_POST['full_name'] ?? '');
    
    // Валидация
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Укажите имя пользователя';
    }
    
    if (empty($password)) {
        $errors[] = 'Укажите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if (empty($email)) {
        $errors[] = 'Укажите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Укажите корректный email';
    }
    
    if (empty($errors)) {
        try {
            // Проверка существования пользователя
            $stmt = $db->prepare("SELECT id FROM admindoor1_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $message = 'Пользователь с таким именем или email уже существует';
                $messageType = 'error';
            } else {
                // Хеширование пароля
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставка администратора
                $stmt = $db->prepare("
                    INSERT INTO admindoor1_users 
                    (username, password, email, full_name, role, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 'admin', NOW(), NOW())
                ");
                
                if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                    $message = "✅ Администратор успешно добавлен!\n\n";
                    $message .= "Логин: $username\n";
                    $message .= "Пароль: $password\n";
                    $message .= "Email: $email\n";
                    $message .= "Роль: admin";
                    $messageType = 'success';
                    
                    // Логирование
                    $log_stmt = $db->prepare("
                        INSERT INTO admindoor1_logs (user_id, action, details, ip_address, created_at) 
                        VALUES (NULL, 'admin_created', ?, ?, NOW())
                    ");
                    $log_stmt->execute([
                        "Создан администратор: $username",
                        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                    ]);
                } else {
                    $message = 'Ошибка при добавлении администратора';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Ошибка базы данных: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = 'error';
    }
}

// Получаем список существующих администраторов
$admins = [];
try {
    $stmt = $db->query("SELECT id, username, email, full_name, created_at FROM admindoor1_users WHERE role = 'admin' ORDER BY id DESC");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    // Таблица может не существовать
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление администратора</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border: none;
        }
        .card-header i {
            margin-right: 10px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
        }
        .admin-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .admin-item {
            background: white;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .admin-role {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
            white-space: pre-line;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #dc3545;
            margin-bottom: 20px;
        }
        .info-text {
            background: #e7f3ff;
            color: #004085;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #004085;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-lock"></i> Добавление администратора
            </div>
            <div class="card-body p-4">
                
                <?php if ($message): ?>
                    <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
                        <?= nl2br($message) ?>
                    </div>
                <?php endif; ?>

                <div class="info-text">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Важно:</strong> После добавления администратора удалите этот файл с сервера в целях безопасности!
                </div>

                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person"></i> Имя пользователя *</label>
                        <input type="text" name="username" class="form-control" 
                               placeholder="admin" required 
                               pattern="[a-zA-Z0-9_]+" 
                               title="Только латинские буквы, цифры и подчеркивание">
                        <small class="text-muted">Только латинские буквы, цифры и подчеркивание</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope"></i> Email *</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="admin@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-badge"></i> Полное имя</label>
                        <input type="text" name="full_name" class="form-control" 
                               placeholder="Главный администратор">
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-key"></i> Пароль *</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="••••••" required minlength="6">
                        <small class="text-muted">Минимум 6 символов</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-shield-check"></i> Добавить администратора
                        </button>
                    </div>
                </form>

                <!-- Список существующих администраторов -->
                <?php if (!empty($admins)): ?>
                    <div class="admin-list mt-4">
                        <h5><i class="bi bi-people"></i> Существующие администраторы:</h5>
                        <?php foreach ($admins as $admin): ?>
                            <div class="admin-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($admin['username'] ?? '') ?></strong>
                                    <?php if ($admin['full_name']): ?>
                                        <small class="text-muted">(<?= htmlspecialchars($admin['full_name']) ?>)</small>
                                    <?php endif; ?>
                                    <span class="admin-role">Admin</span>
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($admin['email'] ?? '') ?>
                                        </small>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= date('d.m.Y', strtotime($admin['created_at'] ?? '')) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <a href="admin/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Перейти в админ-панель
                    </a>
                </div>
            </div>
        </div>

        <!-- Быстрое добавление тестового администратора -->
        <div class="card mt-3">
            <div class="card-header bg-secondary">
                <i class="bi bi-lightning"></i> Быстрое добавление
            </div>
            <div class="card-body">
                <p>Нажмите для добавления тестового администратора с паролем <strong>123456</strong></p>
                <form method="POST" action="">
                    <input type="hidden" name="username" value="admin">
                    <input type="hidden" name="email" value="admin@example.com">
                    <input type="hidden" name="full_name" value="Тестовый администратор">
                    <input type="hidden" name="password" value="123456">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-lightning"></i> Добавить тестового админа (admin/123456)
                    </button>
                </form>
            </div>
        </div>

        <!-- Консольная команда -->
        <div class="card mt-3">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-terminal"></i> Консольная команда
            </div>
            <div class="card-body">
                <p>Если у вас есть доступ к SSH, выполните:</p>
                <pre class="bg-light p-3 rounded"><code>php add_admin.php admin 123456 admin@example.com "Администратор"</code></pre>
            </div>
        </div>
    </div>

    <script>
    function validateForm() {
        const password = document.querySelector('input[name="password"]').value;
        if (password.length < 6) {
            alert('Пароль должен быть не менее 6 символов');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>