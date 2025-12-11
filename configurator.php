<?php
/**
 * Конфігуратор ПК - Головна сторінка
 */

declare(strict_types=1);

// Підключення до БД
require_once __DIR__ . '/config/db.php';

$db = Database::getInstance();
$pageTitle = 'Конфігуратор ПК - PC Configurator';

// Отримання категорій для ручного режиму
try {
    $stmt = $db->query("SELECT id, slug, name, sort_order FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Іконки для категорій
$categoryIcons = [
    'cpu' => 'fa-microchip',
    'motherboard' => 'fa-memory',
    'ram' => 'fa-server',
    'gpu' => 'fa-display',
    'psu' => 'fa-bolt',
    'case' => 'fa-box',
    'storage' => 'fa-hard-drive'
];

// Отримати категорію з URL (якщо є)
$openCategory = isset($_GET['category']) ? $_GET['category'] : null;

// Перевірити чи існує така категорія
if ($openCategory) {
    $categoryExists = false;
    foreach ($categories as $cat) {
        if ($cat['slug'] === $openCategory) {
            $categoryExists = true;
            break;
        }
    }
    if (!$categoryExists) {
        $openCategory = null;
    }
}

// Підключення header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Стилі для конфігуратора -->
<style>
.configurator-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.mode-tabs {
    background: white;
    border-radius: 15px;
    padding: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.mode-tab {
    flex: 1;
    padding: 1rem 2rem;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.mode-tab:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.mode-tab.active {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.mode-tab i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}

.component-card {
    transition: all 0.3s;
    cursor: pointer;
    height: 100%;
}

.component-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.component-card.selected {
    border: 3px solid #667eea;
    background: #f8f9ff;
}

.build-summary {
    position: sticky;
    top: 20px;
}

.auto-form {
    max-width: 600px;
    margin: 0 auto;
}

.budget-option {
    padding: 1.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.budget-option:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.budget-option.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

#buildResult {
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<!-- Header конфігуратора -->
<div class="configurator-header">
    <div class="container">
        <h1 class="text-center mb-3">
            <i class="fas fa-cogs me-3"></i>Конфігуратор ПК
        </h1>
        <p class="text-center lead opacity-90">
            Зберіть ідеальний комп'ютер з автоматичною перевіркою сумісності
        </p>
    </div>
</div>

<div class="container mb-5">
    
    <!-- Перемикач режимів -->
    <div class="mode-tabs">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="mode-tab active" id="manualTab" onclick="switchMode('manual')">
                    <i class="fas fa-hand-pointer"></i>
                    <h5 class="mb-1">Ручний режим</h5>
                    <p class="mb-0 small">Оберіть кожен компонент самостійно</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mode-tab" id="autoTab" onclick="switchMode('auto')">
                    <i class="fas fa-magic"></i>
                    <h5 class="mb-1">Автоматичний підбір</h5>
                    <p class="mb-0 small">Збірка за завданнями та бюджетом</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- РУЧНИЙ РЕЖИМ -->
    <div id="manualMode" class="mode-content">
        <div class="row">
            <!-- Ліва колонка: Вибір компонентів -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Оберіть компоненти
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Accordion категорій -->
                        <div class="accordion" id="categoriesAccordion">
                            <?php foreach ($categories as $index => $category): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $category['id'] ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?= $category['id'] ?>"
                                                data-category="<?= $category['slug'] ?>">
                                            <i class="fas <?= $categoryIcons[$category['slug']] ?? 'fa-cube' ?> me-2"></i>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            <span class="badge bg-secondary ms-2" id="badge-<?= $category['slug'] ?>">Не обрано</span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $category['id'] ?>" 
                                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                         data-bs-parent="#categoriesAccordion">
                                        <div class="accordion-body">
                                            <!-- Компоненти завантажуються через JS -->
                                            <div id="components-<?= $category['slug'] ?>" 
                                                 class="row g-3" 
                                                 data-category="<?= $category['slug'] ?>">
                                                <div class="col-12 text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Завантаження...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Права колонка: Збірка -->
            <div class="col-lg-4">
                <div class="build-summary">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>Ваша збірка
                            </h5>
                        </div>
                        <div class="card-body" id="buildSummary">
                            <p class="text-muted text-center">
                                <i class="fas fa-info-circle"></i><br>
                                Оберіть компоненти зліва
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Загальна вартість:</strong>
                                <span class="h5 mb-0 text-success" id="totalPrice">0.00 грн</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">Загальний TDP:</small>
                                <span id="totalTDP">0 W</span>
                            </div>
                            <button class="btn btn-primary w-100 mb-2" 
                                    id="validateBtn" 
                                    onclick="validateBuild()" 
                                    disabled>
                                <i class="fas fa-check-circle me-2"></i>Перевірити сумісність
                            </button>
                            <button class="btn btn-success w-100" 
                                    id="saveBuildBtn" 
                                    onclick="saveBuild()" 
                                    disabled>
                                <i class="fas fa-save me-2"></i>Зберегти збірку
                            </button>
                        </div>
                    </div>
                    
                    <!-- Алерт сумісності -->
                    <div id="compatibilityAlert" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- АВТОМАТИЧНИЙ РЕЖИМ -->
    <div id="autoMode" class="mode-content" style="display: none;">
        <div class="auto-form">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0">
                        <i class="fas fa-magic me-2"></i>Автоматичний підбір конфігурації
                    </h5>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Вибір завдання -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-gamepad me-2"></i>Для чого комп'ютер?
                        </label>
                        <select class="form-select form-select-lg" id="taskType">
                            <option value="">Оберіть завдання...</option>
                            <option value="cyber_sport">🎮 Кіберспорт (CS2, Dota, Valorant)</option>
                            <option value="gaming_aaa">🏆 ААА Геймінг (Cyberpunk, GTA VI)</option>
                            <option value="work_3d">🎨 3D Робота (Blender, Maya)</option>
                            <option value="streaming">📹 Стрімінг (Twitch, YouTube)</option>
                            <option value="office">💼 Офіс / Навчання</option>
                        </select>
                        <div class="form-text" id="taskDescription"></div>
                    </div>
                    
                    <!-- Вибір бюджету -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-wallet me-2"></i>Ваш бюджет
                        </label>
                        
                        <!-- Швидкий вибір -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="budget-option" data-budget="25000" onclick="selectBudget(25000)">
                                    <div class="fw-bold">25 000 грн</div>
                                    <small>Мінімальний</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="budget-option" data-budget="45000" onclick="selectBudget(45000)">
                                    <div class="fw-bold">45 000 грн</div>
                                    <small>Оптимальний</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="budget-option" data-budget="75000" onclick="selectBudget(75000)">
                                    <div class="fw-bold">75 000 грн</div>
                                    <small>Прогресивний</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="budget-option" data-budget="125000" onclick="selectBudget(125000)">
                                    <div class="fw-bold">125 000 грн</div>
                                    <small>Максимальний</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Кастомний бюджет -->
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">
                                <i class="fas fa-hryvnia-sign"></i>
                            </span>
                            <input type="number" 
                                   class="form-control" 
                                   id="customBudget" 
                                   placeholder="Або введіть свій бюджет"
                                   min="15000"
                                   max="500000"
                                   step="1000">
                            <span class="input-group-text">грн</span>
                        </div>
                        <div class="form-text">Діапазон: 15 000 - 500 000 грн</div>
                    </div>
                    
                    <!-- Кнопка генерації -->
                    <button class="btn btn-primary btn-lg w-100" 
                            id="generateBtn" 
                            onclick="generateBuild()">
                        <i class="fas fa-magic me-2"></i>Згенерувати збірку
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Результат автопідбору -->
        <div id="buildResult" class="mt-4" style="display: none;">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Згенерована збірка
                        </h5>
                        <span class="badge bg-light text-dark" id="resultPrice"></span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Статистика -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-microchip fa-2x text-primary mb-2"></i>
                                <div class="small text-muted">Компонентів</div>
                                <div class="fw-bold" id="statComponents">0</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                <div class="small text-muted">Використано</div>
                                <div class="fw-bold" id="statBudget">0%</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-bolt fa-2x text-warning mb-2"></i>
                                <div class="small text-muted">TDP</div>
                                <div class="fw-bold" id="statTDP">0 W</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-plug fa-2x text-info mb-2"></i>
                                <div class="small text-muted">Запас БЖ</div>
                                <div class="fw-bold" id="statPSU">0 W</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Список компонентів -->
                    <div id="resultComponents"></div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-outline-secondary" onclick="resetAutoBuild()">
                            <i class="fas fa-redo me-2"></i>Спробувати ще раз
                        </button>
                        <button class="btn btn-primary" onclick="editManually()">
                            <i class="fas fa-edit me-2"></i>Редагувати вручну
                        </button>
                        <button class="btn btn-success" onclick="saveAutoBuild()">
                            <i class="fas fa-save me-2"></i>Зберегти збірку
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- JavaScript конфігуратора -->
<script>
// Передати категорію для автоматичного відкриття
window.openCategoryOnLoad = <?= $openCategory ? json_encode($openCategory) : 'null' ?>;
</script>
<script src="/assets/js/configurator.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

