<?php
/**
 * Перевірка прав доступу адміністратора
 * Підключається на всіх сторінках адмінки
 */

declare(strict_types=1);

// Старт сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    // Користувач не залогінений - перенаправити на вхід
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: /login.php?redirect={$currentUrl}");
    exit;
}

// Перевірка ролі адміністратора
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Користувач не адміністратор - заборонити доступ
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Доступ заборонено</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i> Доступ заборонено
                            </h4>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-5x text-danger mb-4"></i>
                            <h5>У вас немає прав для доступу до адмін-панелі</h5>
                            <p class="text-muted">
                                Ця сторінка доступна тільки для адміністраторів.
                            </p>
                            <hr>
                            <p class="mb-0">
                                <strong>Поточний користувач:</strong> <?= htmlspecialchars($_SESSION['login'] ?? 'Невідомий') ?><br>
                                <strong>Роль:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></span>
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <a href="/index.php" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Повернутися на головну
                                </a>
                                <a href="/logout.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-sign-out-alt"></i> Вийти
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Користувач є адміністратором - продовжити виконання











