<?php
// login.php - страница входа и регистрации
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$db = getDB();
$error = '';
$success = '';

// Если уже авторизован - перенаправляем на главную
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}

// Обработка регистрации
if (isset($_POST['register'])) {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = clean($_POST['full_name'] ?? '');
    
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
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO admindoor1_users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'user')");
            
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка при регистрации';
            }
        }
    }
}

// Обработка входа
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
            // Устанавливаем сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
            
            // Обновление времени последнего входа
            $db->prepare("UPDATE admindoor1_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            // Перенаправление в зависимости от роли
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            } else {
                redirect('index.php');
            }
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход / Регистрация - <?= SITE_NAME ?></title>
    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f2f4f8;  /* светло-серый фон */
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
            margin: 0;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            margin: auto;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 35px -8px rgba(0, 0, 0, 0.12), 0 5px 10px -4px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
        }

        .auth-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: #ffffff;
            border-bottom: 1px solid #eef2f6;
            padding: 1.8rem 1.5rem 1rem;
            font-size: 1.8rem;
            font-weight: 600;
            color: #1e293b;
            text-align: center;
            letter-spacing: -0.02em;
        }

        .card-header i {
            color: #5f6b7a;  /* серый оттенок */
            margin-right: 8px;
            font-size: 2rem;
            vertical-align: middle;
        }

        .card-body {
            padding: 2rem 2rem 2.5rem;
        }

        /* Табы */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            gap: 8px;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 30px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #5f6b7a;
            background: transparent;
            transition: all 0.2s;
            margin-bottom: -2px;
            font-size: 1rem;
        }

        .nav-tabs .nav-link i {
            margin-right: 6px;
            font-size: 1.1rem;
            color: #7e8895;
        }

        .nav-tabs .nav-link:hover {
            color: #2c3e50;
            background: #f8f9fc;
            border: none;
        }

        .nav-tabs .nav-link.active {
            color: #2c3e50;
            background: #ffffff;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            border-bottom: 2px solid #6c757d;  /* серый акцент */
            border-radius: 30px 30px 0 0;
        }

        .nav-tabs .nav-link.active i {
            color: #495057;
        }

        /* Поля ввода с иконками */
        .input-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa3af;
            font-size: 1.2rem;
            transition: color 0.2s;
            pointer-events: none;
            z-index: 5;
        }

        .input-icon-wrapper .form-control {
            padding-left: 48px;
            border: 1.5px solid #dee2e6;
            border-radius: 16px;
            height: 54px;
            font-size: 1rem;
            background: #ffffff;
            transition: all 0.2s;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.03);
        }

        .input-icon-wrapper .form-control:focus {
            border-color: #868e96;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), inset 0 1px 3px rgba(0,0,0,0.02);
            background: #fff;
        }

        .input-icon-wrapper:focus-within i {
            color: #495057;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        /* Кнопки */
        .btn-primary {
            background: #4b5563;  /* темно-серый */
            border: 1px solid #374151;
            border-radius: 40px;
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.3px;
            box-shadow: 0 6px 14px -6px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
            color: #fff;
        }

        .btn-primary:hover, .btn-primary:focus {
            background: #374151;
            border-color: #1f2937;
            box-shadow: 0 10px 18px -8px rgba(0, 0, 0, 0.25);
            transform: scale(1.01);
        }

        .btn-primary i {
            margin-right: 8px;
            font-size: 1.2rem;
            vertical-align: middle;
            color: #e5e7eb;
        }

        /* Алерты */
        .alert {
            border-radius: 18px;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            box-shadow: 0 6px 12px -6px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.8rem;
            background: #f1f3f5;
            color: #343a40;
        }

        .alert-danger {
            background: #f8d7da;
            color: #842029;
            border-left: 5px solid #dc3545;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border-left: 5px solid #198754;
        }

        /* Футер */
        .auth-footer {
            text-align: center;
            margin-top: 1.2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            color: #212529;
            text-decoration: underline;
        }

        /* Адаптивность */
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
            .nav-tabs .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        ::placeholder {
            color: #a0aec0;
            opacity: 0.7;
            font-weight: 400;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="card-header">
                <i class="bi bi-shield-lock"></i> <?= SITE_NAME ?>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
                    </div>
                <?php endif; ?>

                <!-- Табы переключения -->
                <ul class="nav nav-tabs mb-4 justify-content-center" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                            <i class="bi bi-box-arrow-in-right"></i> Вход
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                            <i class="bi bi-person-plus-fill"></i> Регистрация
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Панель Входа -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="POST" action="">
                            <div class="input-icon-wrapper">
                                <i class="bi bi-person"></i>
                                <input type="text" name="username" class="form-control" placeholder="Имя пользователя или Email" required>
                            </div>
                            <div class="input-icon-wrapper">
                                <i class="bi bi-lock"></i>
                                <input type="password" name="password" class="form-control" placeholder="Пароль" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 mt-3">
                                <i class="bi bi-box-arrow-in-right"></i> Войти
                            </button>
                        </form>
                    </div>

                    <!-- Панель Регистрации -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="POST" action="">
                            <div class="input-icon-wrapper">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" name="username" class="form-control" placeholder="Имя пользователя" required>
                            </div>
                            <div class="input-icon-wrapper">
                                <i class="bi bi-envelope"></i>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="input-icon-wrapper">
                                <i class="bi bi-person-vcard"></i>
                                <input type="text" name="full_name" class="form-control" placeholder="Полное имя (необязательно)">
                            </div>
                            <div class="input-icon-wrapper">
                                <i class="bi bi-key"></i>
                                <input type="password" name="password" class="form-control" placeholder="Пароль (мин. 6 символов)" required>
                            </div>
                            <div class="input-icon-wrapper">
                                <i class="bi bi-key-fill"></i>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Подтверждение пароля" required>
                            </div>
                            <div class="form-text mb-3 small text-secondary">
                                <i class="bi bi-info-circle"></i> Поля, отмеченные звёздочкой, обязательны.
                            </div>
                            <button type="submit" name="register" class="btn btn-primary w-100">
                                <i class="bi bi-person-plus-fill"></i> Зарегистрироваться
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="auth-footer">
            <i class="bi bi-arrow-left-circle"></i> <a href="index.php">Вернуться на главную</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Активация табов Bootstrap
        var triggerTabList = [].slice.call(document.querySelectorAll('#authTabs button'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })
    </script>
</body>
</html>