<?php
/**
 * Сторінка реєстрації
 */

declare(strict_types=1);

// Підключення до БД
require_once __DIR__ . '/config/db.php';

// Старт сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Якщо вже залогінений - перенаправити на головну
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$errors = [];
$success = false;

// Обробка форми реєстрації
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання та очищення даних
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Валідація
    if (empty($login)) {
        $errors[] = 'Логін є обов\'язковим полем';
    } elseif (strlen($login) < 3) {
        $errors[] = 'Логін має містити мінімум 3 символи';
    } elseif (strlen($login) > 50) {
        $errors[] = 'Логін не може бути довшим за 50 символів';
    }

    if (empty($email)) {
        $errors[] = 'Email є обов\'язковим полем';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некоректний формат email';
    }

    if (empty($password)) {
        $errors[] = 'Пароль є обов\'язковим полем';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль має містити мінімум 6 символів';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Паролі не співпадають';
    }

    // Якщо валідація пройшла успішно
    if (empty($errors)) {
        try {
            // Перевірка чи існує користувач з таким логіном
            $stmt = $db->query(
                "SELECT id FROM users WHERE login = ? LIMIT 1",
                [$login]
            );
            
            if ($stmt->fetch()) {
                $errors[] = 'Користувач з таким логіном вже існує';
            } else {
                // Хешування пароля
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Додавання користувача в БД (роль за замовчуванням 'user')
                $stmt = $db->query(
                    "INSERT INTO users (login, password, role) VALUES (?, ?, 'user')",
                    [$login, $passwordHash]
                );
                
                // Отримання ID нового користувача
                $userId = (int) $db->getConnection()->lastInsertId();
                
                // Автоматичний вхід після реєстрації
                $_SESSION['user_id'] = $userId;
                $_SESSION['login'] = $login;
                $_SESSION['role'] = 'user'; // Нові користувачі завжди мають роль 'user'
                
                // Перенаправлення на головну
                header('Location: /index.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = 'Помилка реєстрації. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Реєстрація - PC Configurator';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Форма реєстрації -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-user-plus me-2"></i>Реєстрація
                        </h2>
                        
                        <!-- Виведення помилок -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Помилка:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Форма -->
                        <form method="POST" action="/register.php">
                            <!-- Логін -->
                            <div class="mb-3">
                                <label for="login" class="form-label">
                                    <i class="fas fa-user me-1"></i>Логін
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="login" 
                                    name="login" 
                                    placeholder="Введіть логін"
                                    value="<?= htmlspecialchars($login ?? '') ?>"
                                    required
                                    minlength="3"
                                    maxlength="50"
                                >
                                <small class="text-muted">Мінімум 3 символи</small>
                            </div>
                            
                            <!-- Email (для інформації, не зберігається в поточній структурі БД) -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="example@email.com"
                                    value="<?= htmlspecialchars($email ?? '') ?>"
                                    required
                                >
                                <small class="text-muted">Для зв'язку та відновлення паролю</small>
                            </div>
                            
                            <!-- Пароль -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Пароль
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Введіть пароль"
                                    required
                                    minlength="6"
                                >
                                <small class="text-muted">Мінімум 6 символів</small>
                            </div>
                            
                            <!-- Підтвердження пароля -->
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Підтвердження пароля
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Повторіть пароль"
                                    required
                                >
                            </div>
                            
                            <!-- Кнопка реєстрації -->
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Зареєструватися
                            </button>
                            
                            <!-- Посилання на вхід -->
                            <div class="text-center">
                                <p class="mb-0">
                                    Вже є акаунт? 
                                    <a href="/login.php" class="text-decoration-none">
                                        <i class="fas fa-sign-in-alt me-1"></i>Увійти
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Інформаційний блок -->
                <div class="alert alert-info mt-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Після реєстрації ви зможете:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Зберігати свої конфігурації ПК</li>
                        <li>Переглядати історію збірок</li>
                        <li>Порівнювати різні варіанти</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

