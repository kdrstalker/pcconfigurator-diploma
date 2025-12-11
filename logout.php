<?php
/**
 * Скрипт виходу з системи
 */

declare(strict_types=1);

// Старт сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Знищення всіх змінних сесії
$_SESSION = [];

// Видалення cookie сесії (якщо є)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Знищення сесії
session_destroy();

// Перенаправлення на головну сторінку
header('Location: /index.php?logout=1');
exit;











