<?php
/**
 * Адмін-панель - Головна сторінка
 * Управління компонентами ПК
 */

declare(strict_types=1);

// Перевірка прав адміністратора
require_once __DIR__ . '/check_admin.php';

// Підключення до БД
require_once __DIR__ . '/../config/db.php';
$db = Database::getInstance();

// Отримання фільтрів
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Побудова SQL запиту з фільтрами
$sql = "SELECT 
            c.id,
            c.name,
            c.price,
            c.socket,
            c.ram_type,
            c.tdp,
            c.psu_wattage,
            c.created_at,
            cat.name as category_name,
            cat.slug as category_slug
        FROM components c
        INNER JOIN categories cat ON c.category_id = cat.id
        WHERE 1=1";

$params = [];

if ($categoryFilter > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND c.name LIKE ?";
    $params[] = "%{$searchQuery}%";
}

$sql .= " ORDER BY cat.sort_order, c.name";

try {
    $stmt = $db->query($sql, $params);
    $components = $stmt->fetchAll();
    
    // Отримання категорій для фільтра
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
    
    // Статистика
    $stmt = $db->query("SELECT COUNT(*) as total FROM components");
    $totalComponents = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT category_id) as total FROM components");
    $totalCategories = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Admin panel error: " . $e->getMessage());
    $components = [];
    $categories = [];
}

$pageTitle = 'Адмін-панель - Управління компонентами';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .component-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Шапка адмінки -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-user-shield"></i> Адмін-панель
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['login']) ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/index.php" class="btn btn-light me-2">
                        <i class="fas fa-home"></i> На сайт
                    </a>
                    <a href="/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt"></i> Вийти
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        
        <!-- Повідомлення -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php
                switch ($_GET['success']) {
                    case 'added':
                        echo 'Компонент успішно додано!';
                        break;
                    case 'updated':
                        echo 'Компонент успішно оновлено!';
                        break;
                    case 'deleted':
                        echo 'Компонент успішно видалено!';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                        <h2 class="mb-0"><?= $totalComponents ?></h2>
                        <p class="text-muted mb-0">Всього компонентів</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-3x text-success mb-3"></i>
                        <h2 class="mb-0"><?= count($categories) ?></h2>
                        <p class="text-muted mb-0">Категорій</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-warning mb-3"></i>
                        <h2 class="mb-0"><?= $totalCategories ?></h2>
                        <p class="text-muted mb-0">Активних категорій</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Заголовок та кнопки -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0">
                            <i class="fas fa-microchip"></i> Управління компонентами
                        </h4>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="/admin/add_component.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Додати компонент
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Фільтри -->
            <div class="card-body bg-light">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input 
                            type="text" 
                            class="form-control" 
                            name="search" 
                            placeholder="Пошук за назвою..."
                            value="<?= htmlspecialchars($searchQuery) ?>"
                        >
                    </div>
                    <div class="col-md-5">
                        <select class="form-select" name="category">
                            <option value="0">Всі категорії</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Фільтр
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Таблиця компонентів -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($components)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>Компоненти не знайдено</h5>
                        <p class="mb-0">Додайте перший компонент для початку роботи.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="componentsTable" class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Назва</th>
                                    <th>Категорія</th>
                                    <th>Ціна</th>
                                    <th>Характеристики</th>
                                    <th>Дата</th>
                                    <th class="text-center">Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($components as $comp): ?>
                                    <tr>
                                        <td><code><?= $comp['id'] ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($comp['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($comp['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                <?= number_format((float)$comp['price'], 2) ?> грн
                                            </strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php if ($comp['socket']): ?>
                                                    <span class="badge bg-info">Socket: <?= htmlspecialchars($comp['socket']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($comp['ram_type']): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($comp['ram_type']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($comp['tdp']): ?>
                                                    <span class="badge bg-warning text-dark">TDP: <?= $comp['tdp'] ?>W</span>
                                                <?php endif; ?>
                                                <?php if ($comp['psu_wattage']): ?>
                                                    <span class="badge bg-success">PSU: <?= $comp['psu_wattage'] ?>W</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d.m.Y', strtotime($comp['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/admin/edit_component.php?id=<?= $comp['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Редагувати">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/admin/delete_component.php?id=<?= $comp['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Ви впевнені, що хочете видалити цей компонент?')"
                                                   title="Видалити">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Ініціалізація DataTables
        $(document).ready(function() {
            $('#componentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/uk.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>


