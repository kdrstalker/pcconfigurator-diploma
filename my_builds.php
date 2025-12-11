<?php
/**
 * Мої збірки - Перегляд збережених конфігурацій
 */

declare(strict_types=1);

// Старт сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/config/db.php';

$db = Database::getInstance();
$pageTitle = 'Мої збірки - PC Configurator';
$userId = (int)$_SESSION['user_id'];

// Отримання збережених збірок
try {
    $stmt = $db->query(
        "SELECT id, build_name, total_price, total_tdp, created_at 
         FROM saved_builds 
         WHERE user_id = ? 
         ORDER BY created_at DESC",
        [$userId]
    );
    $builds = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching builds: " . $e->getMessage());
    $builds = [];
}

// Отримати компоненти для кожної збірки
foreach ($builds as &$build) {
    try {
        $stmt = $db->query(
            "SELECT c.*, cat.name as category_name, cat.slug as category_slug
             FROM build_items bi
             INNER JOIN components c ON bi.component_id = c.id
             INNER JOIN categories cat ON c.category_id = cat.id
             WHERE bi.build_id = ?
             ORDER BY cat.sort_order",
            [$build['id']]
        );
        $build['components'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching build components: " . $e->getMessage());
        $build['components'] = [];
    }
}
unset($build);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Стилі -->
<style>
.builds-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.build-card {
    transition: all 0.3s;
    border: 2px solid #e0e0e0;
}

.build-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #667eea;
}

.component-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.component-item:last-child {
    border-bottom: none;
}

.component-item:hover {
    background-color: #f8f9ff;
}

.build-stats {
    background: linear-gradient(135deg, #f8f9ff 0%, #e8eaff 100%);
    border-radius: 10px;
    padding: 1rem;
}

.empty-state {
    padding: 4rem 2rem;
    text-align: center;
}

.empty-state i {
    font-size: 5rem;
    color: #ddd;
    margin-bottom: 1.5rem;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}
</style>

<!-- Header -->
<div class="builds-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-save me-3"></i>Мої збірки
                </h1>
                <p class="lead opacity-90 mb-0">
                    Перегляд та управління збереженими конфігураціями
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/configurator.php" class="btn btn-light btn-lg">
                    <i class="fas fa-plus me-2"></i>Нова збірка
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    
    <?php if (empty($builds)): ?>
        
        <!-- Пустий стан -->
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3 class="text-muted mb-3">У вас поки немає збережених збірок</h3>
            <p class="text-muted mb-4">
                Створіть свою першу конфігурацію за допомогою нашого конфігуратора
            </p>
            <a href="/configurator.php" class="btn btn-primary btn-lg">
                <i class="fas fa-cogs me-2"></i>Перейти до конфігуратора
            </a>
        </div>
        
    <?php else: ?>
        
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-layer-group fa-3x text-primary mb-3"></i>
                        <h3 class="mb-0"><?= count($builds) ?></h3>
                        <p class="text-muted mb-0">Всього збірок</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                        <h3 class="mb-0">
                            <?php 
                            $totalValue = array_sum(array_column($builds, 'total_price'));
                            echo number_format($totalValue, 2, '.', ' ');
                            ?> грн
                        </h3>
                        <p class="text-muted mb-0">Загальна вартість</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
                        <h3 class="mb-0">
                            <?php 
                            $avgTDP = count($builds) > 0 ? array_sum(array_column($builds, 'total_tdp')) / count($builds) : 0;
                            echo round($avgTDP);
                            ?> W
                        </h3>
                        <p class="text-muted mb-0">Середній TDP</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Список збірок -->
        <div class="row">
            <?php foreach ($builds as $build): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card build-card h-100">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="fas fa-desktop me-2 text-primary"></i>
                                        <?= htmlspecialchars($build['build_name']) ?>
                                    </h5>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('d.m.Y H:i', strtotime($build['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            id="buildMenu<?= $build['id'] ?>" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="buildMenu<?= $build['id'] ?>">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="loadBuildToConfigurator(<?= $build['id'] ?>)">
                                                <i class="fas fa-edit me-2"></i>Редагувати
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="exportToPDF(<?= $build['id'] ?>, '<?= htmlspecialchars($build['build_name'], ENT_QUOTES) ?>', this); return false;">
                                                <i class="fas fa-file-pdf me-2"></i>Експорт в PDF
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteBuild(<?= $build['id'] ?>)">
                                                <i class="fas fa-trash me-2"></i>Видалити
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            
                            <!-- Статистика збірки -->
                            <div class="build-stats mb-3">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="small text-muted">Компонентів</div>
                                        <div class="fw-bold"><?= count($build['components']) ?></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Вартість</div>
                                        <div class="fw-bold text-success"><?= number_format((float)$build['total_price'], 2) ?> грн</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">TDP</div>
                                        <div class="fw-bold text-warning"><?= $build['total_tdp'] ?> W</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Список компонентів -->
                            <div class="components-list">
                                <?php foreach ($build['components'] as $component): ?>
                                    <div class="component-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="small text-muted"><?= htmlspecialchars($component['category_name']) ?></div>
                                                <div class="fw-bold"><?= htmlspecialchars($component['name']) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-success fw-bold"><?= number_format((float)$component['price'], 2) ?> грн</div>
                                                <?php if ($component['tdp']): ?>
                                                    <div class="small text-muted"><?= $component['tdp'] ?>W</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <button class="btn btn-primary flex-grow-1" onclick="loadBuildToConfigurator(<?= $build['id'] ?>)">
                                    <i class="fas fa-edit me-2"></i>Відкрити в конфігураторі
                                </button>
                                <button class="btn btn-outline-danger" onclick="exportToPDF(<?= $build['id'] ?>, '<?= htmlspecialchars($build['build_name'], ENT_QUOTES) ?>', this)">
                                    <i class="fas fa-file-pdf me-2"></i>PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<!-- Бібліотеки для PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<!-- JavaScript -->
<script>
// Ініціалізація jsPDF
const { jsPDF } = window.jspdf;

/**
 * Транслітерація кирилиці в латиницю
 */
function transliterate(text) {
    const map = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'h', 'ґ': 'g', 'д': 'd', 'е': 'e', 'є': 'ye',
        'ж': 'zh', 'з': 'z', 'и': 'y', 'і': 'i', 'ї': 'yi', 'й': 'y', 'к': 'k', 'л': 'l',
        'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
        'ф': 'f', 'х': 'kh', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ь': '', 'ю': 'yu', 'я': 'ya',
        'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'H', 'Ґ': 'G', 'Д': 'D', 'Е': 'E', 'Є': 'Ye',
        'Ж': 'Zh', 'З': 'Z', 'И': 'Y', 'І': 'I', 'Ї': 'Yi', 'Й': 'Y', 'К': 'K', 'Л': 'L',
        'М': 'M', 'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U',
        'Ф': 'F', 'Х': 'Kh', 'Ц': 'Ts', 'Ч': 'Ch', 'Ш': 'Sh', 'Щ': 'Shch', 'Ь': '', 'Ю': 'Yu', 'Я': 'Ya',
        'ы': 'y', 'э': 'e', 'Ы': 'Y', 'Э': 'E', 'ъ': '', 'Ъ': ''
    };
    
    return text.split('').map(char => map[char] || char).join('');
}

/**
 * Експорт збірки в PDF
 */
async function exportToPDF(buildId, buildName, buttonElement) {
    let originalText = '';
    
    try {
        // Показати індикатор завантаження
        if (buttonElement) {
            originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            buttonElement.disabled = true;
        }
        
        // Отримати дані збірки
        const response = await fetch('/api/get_build_details.php?build_id=' + buildId, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch build data');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch data');
        }
        
        const build = data.data;
        
        // Створити PDF
        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        let yPosition = 20;
        
        // === HEADER ===
        // Логотип/іконка
        doc.setFillColor(102, 126, 234); // #667eea
        doc.circle(20, yPosition, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.text('PC', 20, yPosition + 1, { align: 'center' });
        
        // Заголовок
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(20);
        doc.setFont(undefined, 'bold');
        doc.text('PC Configurator', 32, yPosition + 2);
        
        // Дата генерації (англійською)
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.setTextColor(100, 100, 100);
        const now = new Date();
        const currentDate = now.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric'
        }) + ' ' + now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false
        });
        doc.text(currentDate, pageWidth - 20, yPosition + 2, { align: 'right' });
        
        yPosition += 15;
        
        // Лінія-розділювач
        doc.setDrawColor(102, 126, 234);
        doc.setLineWidth(0.5);
        doc.line(20, yPosition, pageWidth - 20, yPosition);
        
        yPosition += 10;
        
        // === НАЗВА ЗБІРКИ ===
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(0, 0, 0);
        // Транслітерація кирилиці
        const buildNameTranslit = transliterate(buildName);
        doc.text(buildNameTranslit, 20, yPosition);
        
        yPosition += 10;
        
        // === СТАТИСТИКА ===
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        
        // Рамка для статистики
        const statsBoxY = yPosition;
        doc.setFillColor(248, 249, 255); // #f8f9ff
        doc.roundedRect(20, statsBoxY, pageWidth - 40, 25, 3, 3, 'F');
        
        yPosition += 8;
        
        // Колонки статистики
        const col1X = 30;
        const col2X = (pageWidth / 2);
        const col3X = pageWidth - 70;
        
        doc.setTextColor(100, 100, 100);
        doc.text('Components:', col1X, yPosition);
        doc.text('Total Price:', col2X, yPosition);
        doc.text('Power (TDP):', col3X, yPosition);
        
        yPosition += 7;
        
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text(String(build.components.length), col1X, yPosition);
        
        doc.setTextColor(40, 167, 69); // green
        doc.text(formatPrice(build.total_price) + ' UAH', col2X, yPosition);
        
        doc.setTextColor(255, 193, 7); // warning/yellow
        doc.text(String(build.total_tdp) + ' W', col3X, yPosition);
        
        yPosition += 15;
        
        // === ТАБЛИЦЯ КОМПОНЕНТІВ ===
        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text('Build Components', 20, yPosition);
        
        yPosition += 8;
        
        // Заголовок таблиці
        doc.setFillColor(102, 126, 234);
        doc.rect(20, yPosition - 5, pageWidth - 40, 8, 'F');
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(255, 255, 255);
        doc.text('Category', 25, yPosition);
        doc.text('Model', 70, yPosition);
        doc.text('Price', pageWidth - 40, yPosition, { align: 'right' });
        
        yPosition += 8;
        
        // Мапінг категорій на англійську
        const categoryMap = {
            'Процесор': 'CPU',
            'Материнська плата': 'Motherboard',
            'Оперативна пам\'ять': 'RAM',
            'Відеокарта': 'GPU',
            'Блок живлення': 'PSU',
            'Корпус': 'Case',
            'Накопичувач': 'Storage'
        };
        
        // Рядки таблиці
        doc.setFont(undefined, 'normal');
        doc.setTextColor(0, 0, 0);
        
        build.components.forEach((component, index) => {
            // Перевірка чи потрібна нова сторінка
            if (yPosition > pageHeight - 30) {
                doc.addPage();
                yPosition = 20;
            }
            
            // Фон рядка (зебра-стиль)
            if (index % 2 === 0) {
                doc.setFillColor(248, 249, 250);
                doc.rect(20, yPosition - 5, pageWidth - 40, 8, 'F');
            }
            
            // Категорія (англійською)
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(8);
            const categoryEn = categoryMap[component.category_name] || component.category_name;
            doc.text(categoryEn, 25, yPosition - 1);
            
            // Назва компонента
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            const componentName = doc.splitTextToSize(component.name, 90);
            doc.text(componentName[0], 25, yPosition + 3);
            
            // Ціна
            doc.setTextColor(40, 167, 69);
            doc.setFont(undefined, 'bold');
            doc.text(formatPrice(component.price) + ' UAH', pageWidth - 25, yPosition + 1, { align: 'right' });
            
            doc.setFont(undefined, 'normal');
            
            yPosition += 10;
        });
        
        yPosition += 5;
        
        // === ПІДСУМОК ===
        // Лінія-розділювач
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.3);
        doc.line(20, yPosition, pageWidth - 20, yPosition);
        
        yPosition += 8;
        
        // Загальна вартість
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(0, 0, 0);
        doc.text('TOTAL PRICE:', pageWidth - 90, yPosition);
        
        doc.setFontSize(14);
        doc.setTextColor(40, 167, 69);
        doc.text(formatPrice(build.total_price) + ' UAH', pageWidth - 20, yPosition, { align: 'right' });
        
        yPosition += 10;
        
        // === FOOTER ===
        const footerY = pageHeight - 15;
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text('Generated by PC Configurator | https://pcconfigurator.local', pageWidth / 2, footerY, { align: 'center' });
        
        // Зберегти PDF (транслітерована назва файлу)
        const fileNameClean = transliterate(buildName).replace(/[^a-zA-Z0-9\s-]/g, '').trim();
        const fileName = (fileNameClean || 'PC_Build') + '.pdf';
        doc.save(fileName);
        
        // Відновити кнопку
        if (buttonElement) {
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
        }
        
        // Показати повідомлення
        showToast('success', 'PDF generated successfully!');
        
    } catch (error) {
        console.error('Error exporting PDF:', error);
        alert('PDF export error: ' + error.message);
        
        // Відновити кнопку
        if (buttonElement) {
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
        }
    }
}

/**
 * Форматувати ціну
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/**
 * Показати toast повідомлення
 */
function showToast(type, message) {
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'info': '#17a2b8'
    };
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-left: 4px solid ${colors[type]};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 15px 20px;
        border-radius: 4px;
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
    `;
    toast.innerHTML = `<strong>${message}</strong>`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Завантажити збірку в конфігуратор
 */
function loadBuildToConfigurator(buildId) {
    // TODO: Реалізувати завантаження збірки в конфігуратор
    // Поки що просто переходимо на сторінку конфігуратора
    window.location.href = '/configurator.php?build_id=' + buildId;
}

/**
 * Видалити збірку
 */
async function deleteBuild(buildId) {
    if (!confirm('Ви впевнені що хочете видалити цю збірку? Цю дію неможливо скасувати.')) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ build_id: buildId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Показати повідомлення
            alert('Збірку успішно видалено');
            
            // Перезавантажити сторінку
            window.location.reload();
        } else {
            alert('Помилка видалення: ' + (data.error || 'Невідома помилка'));
        }
    } catch (error) {
        console.error('Error deleting build:', error);
        alert('Помилка видалення збірки');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

