<?php
// profile.php - страница профиля пользователя
$page_title = 'Личный кабинет';
require_once 'config.php';
require_once 'auth.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'profile.php';
    redirect('login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Получаем данные пользователя
$stmt = $db->prepare("SELECT * FROM admindoor1_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = clean($_POST['full_name']);
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone'] ?? '');
        $address = clean($_POST['address'] ?? '');
        
        // Валидация
        if (empty($email)) {
            $error = 'Email обязателен для заполнения';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Укажите корректный email';
        } else {
            // Проверка, не занят ли email другим пользователем
            $stmt = $db->prepare("SELECT id FROM admindoor1_users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Этот email уже используется другим пользователем';
            } else {
                // Обновление данных (предполагаем, что в таблице есть поля phone и address)
                // Если их нет, нужно добавить или убрать
                $stmt = $db->prepare("UPDATE admindoor1_users SET full_name = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$full_name, $email, $user_id])) {
                    $_SESSION['user_name'] = $full_name;
                    $message = 'Профиль успешно обновлен';
                    
                    // Обновляем данные пользователя
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                } else {
                    $error = 'Ошибка при обновлении профиля';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password)) {
            $error = 'Заполните все поля';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Новые пароли не совпадают';
        } elseif (strlen($new_password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } else {
            // Проверка текущего пароля
            if (password_verify($current_password, $user['password'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admindoor1_users SET password = ? WHERE id = ?");
                if ($stmt->execute([$new_hash, $user_id])) {
                    $message = 'Пароль успешно изменен';
                } else {
                    $error = 'Ошибка при изменении пароля';
                }
            } else {
                $error = 'Неверный текущий пароль';
            }
        }
    }
}

// Получаем статистику заказов
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(total) as total_spent
    FROM admindoor1_orders 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Получаем последние заказы
$stmt = $db->prepare("
    SELECT * FROM admindoor1_orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

include 'header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Боковое меню профиля -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="display-1 mb-3">
                        <i class="bi bi-person-circle text-secondary"></i>
                    </div>
                    <h5><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?><br>
                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>">
                            <?= $user['role'] === 'admin' ? 'Администратор' : 'Покупатель' ?>
                        </span>
                    </p>
                    <hr>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToSection('profile')">
                            <i class="bi bi-person"></i> Личные данные
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToSection('password')">
                            <i class="bi bi-key"></i> Безопасность
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToSection('orders')">
                            <i class="bi bi-box"></i> Мои заказы
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-graph-up"></i> Статистика</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <small class="text-muted">Всего заказов:</small>
                            <strong class="float-end"><?= $stats['total_orders'] ?? 0 ?></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted">Выполнено:</small>
                            <strong class="float-end"><?= $stats['completed_orders'] ?? 0 ?></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted">Потрачено:</small>
                            <strong class="float-end"><?= number_format($stats['total_spent'] ?? 0, 0, ',', ' ') ?> ₽</strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted">На сайте с:</small>
                            <strong class="float-end"><?= date('d.m.Y', strtotime($user['created_at'])) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="col-md-9">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Личные данные -->
            <div class="card shadow-sm mb-4" id="profile-section">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Личные данные</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                                <small class="text-muted">Имя пользователя изменить нельзя</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Полное имя</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Смена пароля -->
            <div class="card shadow-sm mb-4" id="password-section">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Смена пароля</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Текущий пароль</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Новый пароль</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Подтверждение</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="bi bi-shield-lock"></i> Изменить пароль
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Последние заказы -->
            <div class="card shadow-sm" id="orders-section">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-box"></i> Последние заказы</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">Все заказы <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted text-center py-3">У вас пока нет заказов</p>
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-door-closed"></i> Перейти в каталог
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>№ заказа</th>
                                        <th>Дата</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($order['order_number']) ?></strong></td>
                                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= number_format($order['total'], 0, ',', ' ') ?> ₽</td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'new' => 'info',
                                                'processing' => 'warning',
                                                'confirmed' => 'primary',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $status_texts = [
                                                'new' => 'Новый',
                                                'processing' => 'В обработке',
                                                'confirmed' => 'Подтвержден',
                                                'shipped' => 'Отправлен',
                                                'delivered' => 'Доставлен',
                                                'cancelled' => 'Отменен'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $status_colors[$order['status']] ?>">
                                                <?= $status_texts[$order['status']] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="showOrderDetails(<?= $order['id'] ?>)">
                                                <i class="bi bi-eye"></i> Подробнее
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно деталей заказа -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Загрузка... -->
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s;
    }
    .card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    }
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 20px;
    }
    .btn-outline-primary {
        border-color: #3D3D3D;
        color: #3D3D3D;
    }
    .btn-outline-primary:hover {
        background: #3D3D3D;
        color: white;
    }
    .btn-primary {
        background: #3D3D3D;
        border-color: #3D3D3D;
    }
    .btn-primary:hover {
        background: #2d2d2d;
        border-color: #2d2d2d;
    }
</style>

<script>
function scrollToSection(section) {
    const element = document.getElementById(section + '-section');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

function showOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderModal'));
    const modalBody = document.getElementById('orderModalBody');
    
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    
    fetch('get-order-details.php?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Ошибка загрузки данных</div>';
        });
}
</script>

<?php include 'footer.php'; ?>