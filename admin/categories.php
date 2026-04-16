<?php
// admin/categories.php - управление категориями
require_once '../config.php';

// Проверка прав администратора
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Создаем таблицу категорий, если её нет
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `admindoor1_categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `category_id` varchar(50) DEFAULT NULL,
            `description` text,
            `parent_id` int(11) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `image` varchar(500) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `parent_id` (`parent_id`),
            KEY `category_id` (`category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    // Таблица уже существует
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $name = clean($_POST['name']);
        $category_id = clean($_POST['category_id']);
        $description = clean($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $sort_order = intval($_POST['sort_order']);
        $image = clean($_POST['image']);

        if (empty($name)) {
            $error = 'Название категории обязательно';
        } else {
            try {
                if ($id > 0) {
                    // Обновление
                    $stmt = $db->prepare("
                        UPDATE admindoor1_categories SET 
                        name = ?, category_id = ?, description = ?, 
                        parent_id = ?, sort_order = ?, image = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $category_id, $description, $parent_id, $sort_order, $image, $id]);
                    $message = 'Категория успешно обновлена';
                } else {
                    // Добавление
                    $stmt = $db->prepare("
                        INSERT INTO admindoor1_categories 
                        (name, category_id, description, parent_id, sort_order, image, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $category_id, $description, $parent_id, $sort_order, $image]);
                    $message = 'Категория успешно добавлена';
                }

                // Логирование
                $log_stmt = $db->prepare("INSERT INTO admindoor1_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    $id > 0 ? 'category_updated' : 'category_created',
                    "Категория: $name",
                    $_SERVER['REMOTE_ADDR']
                ]);

                redirect('categories.php?action=list&message=' . urlencode($message));
            } catch (PDOException $e) {
                $error = 'Ошибка базы данных: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        try {
            // Проверяем, есть ли товары в этой категории
            $stmt = $db->prepare("SELECT COUNT(*) FROM " . TABLE_PRODUCTS . " WHERE categoryId = ? OR category_name = (SELECT name FROM admindoor1_categories WHERE id = ?)");
            $stmt->execute([$id, $id]);
            $products_count = $stmt->fetchColumn();

            if ($products_count > 0) {
                $error = 'Нельзя удалить категорию, в которой есть товары';
            } else {
                // Получаем информацию о категории для лога
                $stmt = $db->prepare("SELECT name FROM admindoor1_categories WHERE id = ?");
                $stmt->execute([$id]);
                $cat = $stmt->fetch();

                // Удаляем
                $stmt = $db->prepare("DELETE FROM admindoor1_categories WHERE id = ?");
                $stmt->execute([$id]);

                // Логирование
                $log_stmt = $db->prepare("INSERT INTO admindoor1_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([
                    $_SESSION['user_id'],
                    'category_deleted',
                    "Удалена категория: " . ($cat['name'] ?? 'ID: ' . $id),
                    $_SERVER['REMOTE_ADDR']
                ]);

                redirect('categories.php?action=list&message=' . urlencode('Категория успешно удалена'));
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении: ' . $e->getMessage();
        }
    }
}

// Получение сообщения из URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Получение списка категорий
$categories = $db->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM " . TABLE_PRODUCTS . " WHERE categoryId = c.category_id OR category_name = c.name) as products_count,
           (SELECT name FROM admindoor1_categories WHERE id = c.parent_id) as parent_name
    FROM admindoor1_categories c
    ORDER BY c.sort_order, c.name
")->fetchAll();

// Для выпадающего списка родителей
$all_categories = $db->query("SELECT id, name FROM admindoor1_categories ORDER BY name")->fetchAll();

// Если нужно редактирование
$category = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM admindoor1_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        redirect('categories.php?action=list&error=' . urlencode('Категория не найдена'));
    }
}

$page_title = 'Управление категориями';
$page_icon = 'tags';
include 'header.php';
?>

<div class="main-content">
    <div class="navbar">
        <h5 class="mb-0">
            <i class="bi bi-tags"></i> 
            <?php if ($action === 'add'): ?>
                Добавление категории
            <?php elseif ($action === 'edit'): ?>
                Редактирование категории: <?= htmlspecialchars($category['name'] ?? '') ?>
            <?php else: ?>
                Управление категориями
            <?php endif; ?>
        </h5>
        <div>
            <span class="me-3"><i class="bi bi-clock"></i> <?= date('d.m.Y H:i') ?></span>
            <span><i class="bi bi-person-circle"></i> <?= $_SESSION['user_name'] ?? $_SESSION['username'] ?></span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- Кнопка добавления -->
        <div class="mb-3">
            <a href="categories.php?action=add" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Добавить категорию
            </a>
            <a href="products.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-box"></i> К товарам
            </a>
        </div>

        <!-- Таблица категорий -->
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>ID категории</th>
                        <th>Родитель</th>
                        <th>Сортировка</th>
                        <th>Товаров</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                            <?php if ($cat['image']): ?>
                                <br><img src="<?= htmlspecialchars($cat['image']) ?>" style="max-height: 30px;">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($cat['category_id'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($cat['parent_name'] ?? '—') ?></td>
                        <td><?= $cat['sort_order'] ?></td>
                        <td>
                            <span class="badge bg-<?= $cat['products_count'] > 0 ? 'success' : 'secondary' ?>">
                                <?= $cat['products_count'] ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($cat['created_at'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')"
                                        <?= $cat['products_count'] > 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-tags" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p class="mt-2">Категории не найдены</p>
                            <a href="categories.php?action=add" class="btn btn-sm btn-success">
                                Создать первую категорию
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <div class="form-section">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Название категории *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($category['name'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ID категории (для фильтрации)</label>
                            <input type="text" name="category_id" class="form-control" 
                                   value="<?= htmlspecialchars($category['category_id'] ?? '') ?>"
                                   placeholder="30, 31, и т.д.">
                            <small class="text-muted">Укажите числовой ID категории из товаров</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="4"><?= 
                                htmlspecialchars($category['description'] ?? '') 
                            ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Родительская категория</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Нет (корневая категория)</option>
                                <?php foreach ($all_categories as $parent): ?>
                                    <?php if ($parent['id'] != $id): ?>
                                        <option value="<?= $parent['id'] ?>" 
                                                <?= ($category['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parent['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Порядок сортировки</label>
                            <input type="number" name="sort_order" class="form-control" 
                                   value="<?= htmlspecialchars($category['sort_order'] ?? '0') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">URL изображения</label>
                            <input type="url" name="image" class="form-control" 
                                   value="<?= htmlspecialchars($category['image'] ?? '') ?>">
                            <?php if (!empty($category['image'])): ?>
                                <img src="<?= htmlspecialchars($category['image']) ?>" 
                                     class="img-thumbnail mt-2" style="max-height: 100px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" name="save" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Сохранить
                    </button>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Отмена
                    </a>
                </div>
            </form>
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
                <p>Вы уверены, что хотите удалить категорию <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-danger">Это действие нельзя отменить!</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="categories.php" id="deleteForm">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Отмена
                    </button>
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Удалить
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteCategoryName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

</body>
</html>