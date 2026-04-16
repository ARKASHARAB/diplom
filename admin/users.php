<?php
// admin/users.php - управление пользователями
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $full_name = clean($_POST['full_name']);
        $role = clean($_POST['role']);
        
        $data = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        try {
            if ($id > 0) {
                // Обновление
                $sql = "UPDATE admindoor1_users SET 
                        username=:username, email=:email, full_name=:full_name, role=:role";
                if (isset($data['password'])) {
                    $sql .= ", password=:password";
                }
                $sql .= " WHERE id=:id";
                $data['id'] = $id;
                $message = 'Пользователь обновлен';
            } else {
                // Добавление
                if (empty($_POST['password'])) {
                    $error = 'Пароль обязателен при создании';
                } else {
                    $sql = "INSERT INTO admindoor1_users (username, password, email, full_name, role, created_at, updated_at) 
                            VALUES (:username, :password, :email, :full_name, :role, NOW(), NOW())";
                    $message = 'Пользователь добавлен';
                }
            }

            if (empty($error)) {
                $stmt = $db->prepare($sql);
                $stmt->execute($data);
                
                // Логирование
                $log_stmt = $db->prepare("INSERT INTO admindoor1_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    $id > 0 ? 'user_updated' : 'user_created',
                    "Пользователь: $username",
                    $_SERVER['REMOTE_ADDR']
                ]);

                redirect('users.php?action=list&message=' . urlencode($message));
            }
        } catch (PDOException $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        if ($id == $_SESSION['user_id']) {
            $error = 'Нельзя удалить самого себя';
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM admindoor1_users WHERE id = ?");
                $stmt->execute([$id]);
                
                $log_stmt = $db->prepare("INSERT INTO admindoor1_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    'user_deleted',
                    "Удален пользователь ID: $id",
                    $_SERVER['REMOTE_ADDR']
                ]);

                redirect('users.php?action=list&message=' . urlencode('Пользователь удален'));
            } catch (PDOException $e) {
                $error = 'Ошибка при удалении: ' . $e->getMessage();
            }
        }
    }
}

// Получение сообщения
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Получение списка пользователей
$users = $db->query("SELECT * FROM admindoor1_users ORDER BY id DESC")->fetchAll();

// Получение данных пользователя для редактирования
$user = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM admindoor1_users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background: #3D3D3D; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu a { display: block; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-menu a.active { background: #2d2d2d; border-left: 4px solid #e74c3c; }
        .main-content { margin-left: 25px; padding: 20px; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 15px; border-radius: 10px; }
        .user-table { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .role-admin { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .role-user { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .role-manager { background-color: #ffc107; color: black; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>


    <div class="main-content">
        <div class="navbar">
            <h5 class="mb-0"><i class="bi bi-people"></i> Управление пользователями</h5>
            <div>
                <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
                <span><i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?></span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="mb-3">
                <a href="users.php?action=add" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Добавить пользователя
                </a>
            </div>

            <div class="user-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя пользователя</th>
                            <th>Email</th>
                            <th>Полное имя</th>
                            <th>Роль</th>
                            <th>Последний вход</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['username']) ?></strong>
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info">Это вы</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['full_name'] ?? '-') ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="role-admin">Администратор</span>
                                <?php elseif ($u['role'] === 'manager'): ?>
                                    <span class="role-manager">Менеджер</span>
                                <?php else: ?>
                                    <span class="role-user">Пользователь</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : '-' ?></td>
                            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <div class="card">
                <div class="card-header">
                    <h5><?= $action === 'add' ? 'Добавление' : 'Редактирование' ?> пользователя</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Имя пользователя *</label>
                            <input type="text" name="username" class="form-control" required 
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Полное имя</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль <?= $action === 'edit' ? '(оставьте пустым, чтобы не менять)' : '*' ?></label>
                            <input type="password" name="password" class="form-control" <?= $action === 'add' ? 'required' : '' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                            <select name="role" class="form-select">
                                <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                <option value="manager" <?= ($user['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Администратор</option>
                            </select>
                        </div>
                        <button type="submit" name="save" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Сохранить
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Отмена
                        </a>
                    </form>
                </div>
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
                    <p>Удалить пользователя <strong id="deleteUserName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="id" id="deleteUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="delete" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(id, username) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = username;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>