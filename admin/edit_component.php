<?php
/**
 * Адмін-панель - Редагування компонента
 */

declare(strict_types=1);

// Включити відображення помилок для відлагодження
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Перевірка прав адміністратора
require_once __DIR__ . '/check_admin.php';

// Підключення до БД
require_once __DIR__ . '/../config/db.php';
$db = Database::getInstance();

$errors = [];
$componentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($componentId <= 0) {
    header('Location: /admin/index.php');
    exit;
}

// Отримання компонента з БД
try {
    $stmt = $db->query("SELECT * FROM components WHERE id = ? LIMIT 1", [$componentId]);
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

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних
    $name = trim($_POST['name'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $image = trim($_POST['image'] ?? '');
    $socket = trim($_POST['socket'] ?? '') ?: null;
    $ram_type = trim($_POST['ram_type'] ?? '') ?: null;
    $tdp = isset($_POST['tdp']) && $_POST['tdp'] !== '' ? (int)$_POST['tdp'] : null;
    $psu_wattage = isset($_POST['psu_wattage']) && $_POST['psu_wattage'] !== '' ? (int)$_POST['psu_wattage'] : null;
    $specs_json_raw = trim($_POST['specs_json'] ?? '');
    
    // Валідація
    if (empty($name)) {
        $errors[] = 'Назва компонента є обов\'язковою';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Оберіть категорію';
    }
    
    if ($price < 0) {
        $errors[] = 'Ціна не може бути від\'ємною';
    }
    
    // Валідація JSON
    $specs_json = null;
    if (!empty($specs_json_raw)) {
        $decoded = json_decode($specs_json_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Некоректний JSON формат характеристик: ' . json_last_error_msg();
        } else {
            $specs_json = $specs_json_raw;
        }
    }
    
    // Оновлення в БД
    if (empty($errors)) {
        try {
            $sql = "UPDATE components SET 
                    category_id = ?,
                    name = ?,
                    price = ?,
                    image = ?,
                    socket = ?,
                    ram_type = ?,
                    tdp = ?,
                    psu_wattage = ?,
                    specs_json = ?
                    WHERE id = ?";
            
            $params = [
                $category_id,
                $name,
                $price,
                $image ?: null,
                $socket,
                $ram_type,
                $tdp,
                $psu_wattage,
                $specs_json,
                $componentId
            ];
            
            $db->query($sql, $params);
            
            // Перенаправлення після успішного оновлення
            header('Location: /admin/index.php?success=updated');
            exit;
            
        } catch (PDOException $e) {
            error_log("Update component error: " . $e->getMessage());
            $errors[] = 'Помилка при оновленні компонента: ' . $e->getMessage();
        }
    }
} else {
    // Заповнюємо поля з даних компонента
    $_POST['name'] = $component['name'];
    $_POST['category_id'] = $component['category_id'];
    $_POST['price'] = $component['price'];
    $_POST['image'] = $component['image'] ?? '';
    $_POST['socket'] = $component['socket'] ?? '';
    $_POST['ram_type'] = $component['ram_type'] ?? '';
    $_POST['tdp'] = $component['tdp'] ?? '';
    $_POST['psu_wattage'] = $component['psu_wattage'] ?? '';
    $_POST['specs_json'] = $component['specs_json'] ?? '';
}

// Отримання категорій
try {
    $stmt = $db->query("SELECT id, name, slug FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

$pageTitle = 'Редагувати компонент - Адмін-панель';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                        <i class="fas fa-edit"></i> Редагувати компонент
                    </h1>
                    <p class="mb-0 mt-1 opacity-75">
                        <small>ID: <?= $componentId ?></small>
                    </p>
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
        
        <!-- Помилки -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Помилка:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Форма -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Форма редагування компонента
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/edit_component.php?id=<?= $componentId ?>">
                    
                    <div class="row">
                        <!-- Основна інформація -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-info-circle"></i> Основна інформація
                            </h6>
                            
                            <!-- Назва -->
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Назва компонента <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                    required
                                >
                            </div>
                            
                            <!-- Категорія -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    Категорія <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Оберіть категорію...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Ціна -->
                            <div class="mb-3">
                                <label for="price" class="form-label">
                                    Ціна (грн) <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="price" 
                                    name="price" 
                                    value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>"
                                    step="0.01"
                                    min="0"
                                    required
                                >
                            </div>
                            
                            <!-- Зображення -->
                            <div class="mb-3">
                                <label for="image" class="form-label">
                                    URL зображення
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="image" 
                                    name="image" 
                                    value="<?= htmlspecialchars((string)($_POST['image'] ?? '')) ?>"
                                >
                                <?php if (!empty($_POST['image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($_POST['image']) ?>" 
                                             alt="Preview" 
                                             class="img-thumbnail"
                                             style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Характеристики для сумісності -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-cogs"></i> Характеристики для сумісності
                            </h6>
                            
                            <!-- Socket -->
                            <div class="mb-3">
                                <label for="socket" class="form-label">
                                    Socket (для CPU та Motherboard)
                                </label>
                                <select class="form-select" id="socket" name="socket">
                                    <option value="">Не вказано</option>
                                    <optgroup label="AMD">
                                        <option value="AM5" <?= (isset($_POST['socket']) && $_POST['socket'] === 'AM5') ? 'selected' : '' ?>>AM5 (Ryzen 7000)</option>
                                        <option value="AM4" <?= (isset($_POST['socket']) && $_POST['socket'] === 'AM4') ? 'selected' : '' ?>>AM4 (Ryzen 5000/3000)</option>
                                        <option value="sTRX4" <?= (isset($_POST['socket']) && $_POST['socket'] === 'sTRX4') ? 'selected' : '' ?>>sTRX4 (Threadripper)</option>
                                    </optgroup>
                                    <optgroup label="Intel">
                                        <option value="LGA1700" <?= (isset($_POST['socket']) && $_POST['socket'] === 'LGA1700') ? 'selected' : '' ?>>LGA1700 (Core 12-14 Gen)</option>
                                        <option value="LGA1200" <?= (isset($_POST['socket']) && $_POST['socket'] === 'LGA1200') ? 'selected' : '' ?>>LGA1200 (Core 10-11 Gen)</option>
                                        <option value="LGA1151" <?= (isset($_POST['socket']) && $_POST['socket'] === 'LGA1151') ? 'selected' : '' ?>>LGA1151 (Core 6-9 Gen)</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <!-- RAM Type -->
                            <div class="mb-3">
                                <label for="ram_type" class="form-label">
                                    Тип пам'яті (для RAM та Motherboard)
                                </label>
                                <select class="form-select" id="ram_type" name="ram_type">
                                    <option value="">Не вказано</option>
                                    <option value="DDR4" <?= (isset($_POST['ram_type']) && $_POST['ram_type'] === 'DDR4') ? 'selected' : '' ?>>DDR4</option>
                                    <option value="DDR5" <?= (isset($_POST['ram_type']) && $_POST['ram_type'] === 'DDR5') ? 'selected' : '' ?>>DDR5</option>
                                </select>
                            </div>
                            
                            <!-- TDP -->
                            <div class="mb-3">
                                <label for="tdp" class="form-label">
                                    TDP (Вт) - для CPU та GPU
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="tdp" 
                                    name="tdp" 
                                    value="<?= htmlspecialchars((string)($_POST['tdp'] ?? '')) ?>"
                                    min="0"
                                    placeholder="Наприклад: 105"
                                >
                            </div>
                            
                            <!-- PSU Wattage -->
                            <div class="mb-3">
                                <label for="psu_wattage" class="form-label">
                                    Потужність БЖ (Вт) - тільки для PSU
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="psu_wattage" 
                                    name="psu_wattage" 
                                    value="<?= htmlspecialchars((string)($_POST['psu_wattage'] ?? '')) ?>"
                                    min="0"
                                    placeholder="Наприклад: 650"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Додаткові характеристики JSON -->
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-code"></i> Додаткові характеристики (JSON)
                    </h6>
                    <div class="mb-3">
                        <label for="specs_json" class="form-label">
                            JSON характеристики
                        </label>
                        <textarea 
                            class="form-control font-monospace" 
                            id="specs_json" 
                            name="specs_json" 
                            rows="8"
                            placeholder='{"cores": 6, "frequency": "3.7 GHz"}'
                        ><?= htmlspecialchars((string)($_POST['specs_json'] ?? '')) ?></textarea>
                        <small class="text-muted">
                            Додаткові характеристики у форматі JSON.
                        </small>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                        <a href="/admin/delete_component.php?id=<?= $componentId ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Ви впевнені, що хочете видалити цей компонент?')">
                            <i class="fas fa-trash"></i> Видалити
                        </a>
                        <div>
                            <a href="/admin/index.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times"></i> Скасувати
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Зберегти зміни
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>









