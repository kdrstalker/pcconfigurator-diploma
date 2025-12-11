<?php
/**
 * Сторінка входу
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

// Обробка форми входу
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отримання даних
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Базова валідація
    if (empty($login)) {
        $errors[] = 'Введіть логін';
    }
    
    if (empty($password)) {
        $errors[] = 'Введіть пароль';
    }
    
    // Якщо поля заповнені
    if (empty($errors)) {
        try {
            // Пошук користувача в БД (з роллю)
            $stmt = $db->query(
                "SELECT id, login, password, role FROM users WHERE login = ? LIMIT 1",
                [$login]
            );
            
            $user = $stmt->fetch();
            
            // Перевірка чи існує користувач та чи правильний пароль
            if ($user && password_verify($password, $user['password'])) {
                // Успішний вхід - зберігаємо дані в сесію
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['role'] = $user['role'] ?? 'user'; // Роль користувача
                
                // Перенаправлення залежно від ролі
                if ($_SESSION['role'] === 'admin') {
                    // Адміністратор йде в адмінку (якщо не вказано інше)
                    $redirect = $_GET['redirect'] ?? '/admin/index.php';
                } else {
                    // Звичайний користувач на головну
                    $redirect = $_GET['redirect'] ?? '/index.php';
                }
                
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Невірний логін або пароль';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors[] = 'Помилка входу. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Вхід - PC Configurator';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Форма входу -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-sign-in-alt me-2"></i>Вхід
                        </h2>
                        
                        <!-- Повідомлення про успішну реєстрацію -->
                        <?php if (isset($_GET['registered'])): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                Реєстрацію завершено! Тепер ви можете увійти.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Повідомлення про вихід -->
                        <?php if (isset($_GET['logout'])): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Ви успішно вийшли з системи.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Виведення помилок -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Форма -->
                        <form method="POST" action="/login.php">
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
                                    placeholder="Введіть ваш логін"
                                    value="<?= htmlspecialchars($login ?? '') ?>"
                                    required
                                    autofocus
                                >
                            </div>
                            
                            <!-- Пароль -->
                            <div class="mb-4">
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
                                >
                            </div>
                            
                            <!-- Кнопка входу -->
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Увійти
                            </button>
                            
                            <!-- Посилання на реєстрацію -->
                            <div class="text-center">
                                <p class="mb-0">
                                    Немає акаунта? 
                                    <a href="/register.php" class="text-decoration-none">
                                        <i class="fas fa-user-plus me-1"></i>Зареєструватися
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

