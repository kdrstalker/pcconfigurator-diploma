<?php
/**
 * Адмін-панель - Видалення компонента
 */

declare(strict_types=1);

// Перевірка прав адміністратора
require_once __DIR__ . '/check_admin.php';

// Підключення до БД
require_once __DIR__ . '/../config/db.php';
$db = Database::getInstance();

// Отримання ID компонента
$componentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($componentId <= 0) {
    header('Location: /admin/index.php');
    exit;
}

// Підтвердження видалення (якщо прийшов POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Видалення компонента
        $stmt = $db->query("DELETE FROM components WHERE id = ?", [$componentId]);
        
        // Перенаправлення після успішного видалення
        header('Location: /admin/index.php?success=deleted');
        exit;
        
    } catch (PDOException $e) {
        error_log("Delete component error: " . $e->getMessage());
        $error = 'Помилка при видаленні компонента: ' . $e->getMessage();
    }
}

// Отримання інформації про компонент
try {
    $stmt = $db->query(
        "SELECT c.*, cat.name as category_name 
         FROM components c 
         INNER JOIN categories cat ON c.category_id = cat.id 
         WHERE c.id = ? 
         LIMIT 1",
        [$componentId]
    );
    
    $component = $stmt->fetch();
    
    if (!$component) {
        header('Location: /admin/index.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Component fetch error: " . $e->getMessage());
    header('Location: /admin/index.php');
    exit;
}

$pageTitle = 'Видалення компонента - Адмін-панель';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Шапка -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-trash-alt"></i> Видалення компонента
                    </h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/admin/index.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Назад до списку
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        
        <!-- Помилка -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Карта підтвердження -->
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Підтвердження видалення
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Увага!</strong> Ця дія незворотня. Компонент буде видалено назавжди.
                        </div>
                        
                        <h5 class="mb-3">Ви впевнені, що хочете видалити цей компонент?</h5>
                        
                        <!-- Інформація про компонент -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <?php if ($component['image']): ?>
                                            <img src="<?= htmlspecialchars($component['image']) ?>" 
                                                 alt="<?= htmlspecialchars($component['name']) ?>"
                                                 class="img-fluid rounded"
                                                 style="max-height: 150px;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                <i class="fas fa-image fa-3x opacity-50"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-9">
                                        <h4><?= htmlspecialchars($component['name']) ?></h4>
                                        
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <th width="150">ID:</th>
                                                <td><code><?= $component['id'] ?></code></td>
                                            </tr>
                                            <tr>
                                                <th>Категорія:</th>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($component['category_name']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Ціна:</th>
                                                <td>
                                                    <strong class="text-success">
                                                        <?= number_format((float)$component['price'], 2) ?> грн
                                                    </strong>
                                                </td>
                                            </tr>
                                            <?php if ($component['socket']): ?>
                                            <tr>
                                                <th>Socket:</th>
                                                <td><?= htmlspecialchars($component['socket']) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if ($component['ram_type']): ?>
                                            <tr>
                                                <th>RAM Type:</th>
                                                <td><?= htmlspecialchars($component['ram_type']) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if ($component['tdp']): ?>
                                            <tr>
                                                <th>TDP:</th>
                                                <td><?= $component['tdp'] ?> Вт</td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if ($component['psu_wattage']): ?>
                                            <tr>
                                                <th>PSU Wattage:</th>
                                                <td><?= $component['psu_wattage'] ?> Вт</td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th>Дата створення:</th>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d.m.Y H:i', strtotime($component['created_at'])) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Форма підтвердження -->
                        <form method="POST" action="/admin/delete_component.php?id=<?= $componentId ?>">
                            <input type="hidden" name="confirm" value="1">
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/admin/index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Скасувати
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Так, видалити компонент
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


