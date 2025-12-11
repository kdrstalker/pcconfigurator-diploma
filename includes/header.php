<?php
// Підключення до БД (якщо потрібно)
if (!isset($db)) {
    require_once __DIR__ . '/../config/db.php';
    $db = Database::getInstance();
}

// Перевірка сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Перевірка авторизації
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['login'] ?? 'Користувач') : '';
$userRole = $isLoggedIn ? ($_SESSION['role'] ?? 'user') : '';
$isAdmin = ($userRole === 'admin');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PC Configurator - Автоматизований підбір конфігурації ПК з перевіркою сумісності компонентів">
    <title><?= $pageTitle ?? 'PC Configurator - Збери свій ідеальний ПК' ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Навігація -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Лого -->
            <a class="navbar-brand fw-bold" href="/index.php">
                <i class="fas fa-microchip me-2"></i>PC Configurator
            </a>
            
            <!-- Кнопка для мобільного меню -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Меню -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">
                            <i class="fas fa-home me-1"></i>Головна
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/configurator.php">
                            <i class="fas fa-cogs me-1"></i>Конфігуратор
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <!-- Меню для залогінених користувачів -->
                        <?php if ($isAdmin): ?>
                            <!-- Адмін-панель (тільки для адміністраторів) -->
                            <li class="nav-item">
                                <a class="nav-link text-warning" href="/admin/index.php">
                                    <i class="fas fa-user-shield me-1"></i>Адмінка
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/my_builds.php">
                                <i class="fas fa-save me-1"></i>Мої збірки
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($userName) ?>
                                <?php if ($isAdmin): ?>
                                    <span class="badge bg-warning text-dark ms-1">Admin</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item disabled" href="#">
                                        <small class="text-muted">
                                            <i class="fas fa-id-badge me-2"></i>ID: <?= $_SESSION['user_id'] ?>
                                        </small>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item disabled" href="#">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt me-2"></i>Роль: <?= ucfirst($userRole) ?>
                                        </small>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($isAdmin): ?>
                                    <li>
                                        <a class="dropdown-item" href="/admin/index.php">
                                            <i class="fas fa-cog me-2"></i>Адмін-панель
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="/my_builds.php">
                                        <i class="fas fa-list me-2"></i>Мої збірки
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Вийти
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Меню для гостей -->
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Увійти
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2 px-3" href="/register.php">
                                <i class="fas fa-user-plus me-1"></i>Реєстрація
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Основний контент -->
    <main>


