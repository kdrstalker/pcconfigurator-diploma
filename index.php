<?php
/**
 * Головна сторінка - PC Configurator
 */

declare(strict_types=1);

// Підключення до БД
require_once __DIR__ . '/config/db.php';

// Отримання екземпляру БД
$db = Database::getInstance();

// Заголовок сторінки
$pageTitle = 'Головна - PC Configurator';

// Підключення шапки
require_once __DIR__ . '/includes/header.php';

// Отримання категорій з бази даних
try {
    $query = "SELECT id, slug, name FROM categories ORDER BY sort_order LIMIT 4";
    $stmt = $db->query($query);
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
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <!-- Повідомлення після дій -->
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Вітаємо!</strong> Реєстрацію успішно завершено. Тепер ви можете зберігати свої збірки.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Ви успішно вийшли з системи. Дякуємо за використання PC Configurator!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-3 fw-bold">
                    <i class="fas fa-laptop-code me-3"></i>
                    Збери свій ідеальний ПК
                </h1>
                <p class="lead">
                    Автоматична перевірка сумісності компонентів.<br>
                    Обирай комплектуючі та отримуй готову збірку без помилок!
                </p>
                <div class="mt-4">
                    <a href="/configurator.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-play me-2"></i>Почати збірку
                    </a>
                    <a href="#categories" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-info-circle me-2"></i>Дізнатись більше
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Переваги -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>Автоматична перевірка</h4>
                    <p class="text-muted">Система автоматично перевіряє сумісність всіх компонентів</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fas fa-money-bill-wave fa-3x text-primary mb-3"></i>
                    <h4>Оптимальна ціна</h4>
                    <p class="text-muted">Підбирай компоненти під свій бюджет з актуальними цінами</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4">
                    <i class="fas fa-save fa-3x text-warning mb-3"></i>
                    <h4>Збереження збірок</h4>
                    <p class="text-muted">Зберігай свої конфігурації та повертайся до них у будь-який час</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Популярні категорії -->
<section class="py-5" id="categories">
    <div class="container">
        <h2 class="text-center section-title">Популярні категорії</h2>
        
        <?php if (empty($categories)): ?>
            <div class="alert alert-warning text-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Категорії не знайдено. Переконайтеся, що база даних ініціалізована.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon">
                                    <i class="fas <?= $categoryIcons[$category['slug']] ?? 'fa-cube' ?>"></i>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                <a href="/configurator.php?category=<?= urlencode($category['slug']) ?>" class="btn btn-outline-primary mt-3">
                                    <i class="fas fa-arrow-right me-2"></i>Переглянути
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="/configurator.php" class="btn btn-primary btn-lg">
                <i class="fas fa-cogs me-2"></i>Перейти до конфігуратора
            </a>
        </div>
    </div>
</section>

<!-- Як це працює -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title">Як це працює?</h2>
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="p-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">1</span>
                    </div>
                    <h5>Обери категорію</h5>
                    <p class="text-muted small">Почни з процесора або материнської плати</p>
                </div>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="p-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">2</span>
                    </div>
                    <h5>Додавай компоненти</h5>
                    <p class="text-muted small">Система фільтрує тільки сумісні деталі</p>
                </div>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="p-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">3</span>
                    </div>
                    <h5>Перевір сумісність</h5>
                    <p class="text-muted small">Автоматична перевірка всіх параметрів</p>
                </div>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="p-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">4</span>
                    </div>
                    <h5>Збережи збірку</h5>
                    <p class="text-muted small">Зберігай та порівнюй різні конфігурації</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Підключення підвалу
require_once __DIR__ . '/includes/footer.php';
?>


