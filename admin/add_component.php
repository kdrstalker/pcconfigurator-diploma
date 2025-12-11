<?php
/**
 * Адмін-панель - Додавання компонента
 */

declare(strict_types=1);

// Перевірка прав адміністратора
require_once __DIR__ . '/check_admin.php';

// Підключення до БД
require_once __DIR__ . '/../config/db.php';
$db = Database::getInstance();

$errors = [];
$success = false;

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
    
    // Збереження в БД
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO components 
                    (category_id, name, price, image, socket, ram_type, tdp, psu_wattage, specs_json) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $category_id,
                $name,
                $price,
                $image ?: null,
                $socket,
                $ram_type,
                $tdp,
                $psu_wattage,
                $specs_json
            ];
            
            $db->query($sql, $params);
            
            // Перенаправлення після успішного додавання
            header('Location: /admin/index.php?success=added');
            exit;
            
        } catch (PDOException $e) {
            error_log("Add component error: " . $e->getMessage());
            $errors[] = 'Помилка при збереженні компонента: ' . $e->getMessage();
        }
    }
}

// Отримання категорій
try {
    $stmt = $db->query("SELECT id, name, slug FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

$pageTitle = 'Додати компонент - Адмін-панель';
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
                        <i class="fas fa-plus-circle"></i> Додати компонент
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
                    <i class="fas fa-edit"></i> Форма додавання компонента
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/add_component.php">
                    
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
                                    placeholder="Наприклад: AMD Ryzen 5 7600X"
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
                                    value="<?= htmlspecialchars($_POST['image'] ?? '') ?>"
                                    placeholder="https://example.com/image.jpg"
                                >
                                <small class="text-muted">Посилання на зображення товару</small>
                            </div>
                        </div>
                        
                        <!-- Характеристики для сумісності -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-cogs"></i> Характеристики для сумісності
                            </h6>
                            
                            <div class="alert alert-info alert-sm">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Які поля заповнювати:</strong><br>
                                    <strong>CPU:</strong> Socket, TDP<br>
                                    <strong>Motherboard:</strong> Socket, RAM Type<br>
                                    <strong>RAM:</strong> RAM Type<br>
                                    <strong>GPU:</strong> TDP<br>
                                    <strong>PSU:</strong> PSU Wattage<br>
                                    <strong>Case, Storage:</strong> можна залишити пустими
                                </small>
                            </div>
                            
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
                                <small class="text-muted">Тип сокету процесора/материнської плати</small>
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
                                    value="<?= htmlspecialchars($_POST['tdp'] ?? '') ?>"
                                    min="0"
                                    placeholder="Наприклад: 105"
                                >
                                <small class="text-muted">Споживання енергії в Ватах</small>
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
                                    value="<?= htmlspecialchars($_POST['psu_wattage'] ?? '') ?>"
                                    min="0"
                                    placeholder="Наприклад: 650"
                                >
                                <small class="text-muted">Потужність блоку живлення</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Додаткові характеристики JSON -->
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-code"></i> Додаткові характеристики (JSON) 
                        <span class="badge bg-secondary">Опціонально</span>
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
                            placeholder='{"cores": 6, "threads": 12, "base_clock": "4.7 GHz"}'
                        ><?= htmlspecialchars($_POST['specs_json'] ?? '') ?></textarea>
                        <small class="text-muted">
                            Додаткові характеристики у форматі JSON. 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#jsonHelpModal">Показати приклад</a>
                        </small>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/admin/index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Скасувати
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Зберегти компонент
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальне вікно з прикладом JSON -->
    <div class="modal fade" id="jsonHelpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i> Приклад JSON характеристик
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Формат JSON для різних категорій:</p>
                    
                    <h6>Процесор (CPU):</h6>
                    <pre class="bg-light p-3"><code>{
  "cores": 6,
  "threads": 12,
  "base_clock": "4.7 GHz",
  "boost_clock": "5.3 GHz"
}</code></pre>
                    
                    <h6>Відеокарта (GPU):</h6>
                    <pre class="bg-light p-3"><code>{
  "vram": "8GB GDDR6",
  "bus_width": "128-bit",
  "recommended_psu": "550W"
}</code></pre>
                    
                    <h6>RAM:</h6>
                    <pre class="bg-light p-3"><code>{
  "capacity": "16GB",
  "speed": "3200 MHz",
  "kit": "2x8GB",
  "cas_latency": "CL16"
}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>







