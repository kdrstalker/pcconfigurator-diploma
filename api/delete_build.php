<?php
/**
 * API: Видалення збереженої збірки
 * 
 * POST /api/delete_build.php
 * Body: { "build_id": 1 }
 * 
 * Requires: Authorization (session)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

// Headers
setCorsHeaders();

// Перевірка методу
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Метод не дозволений. Використовуйте POST', 405);
}

// Перевірка авторизації
$user = requireAuth();

// Отримати дані
$input = getJsonInput();
if (!$input) {
    sendError('Невалідний JSON', 400);
}

// Валідація
validateRequired($input, ['build_id']);

$buildId = (int)$input['build_id'];
$userId = (int)$user['id'];

if ($buildId <= 0) {
    sendError('Невірний ID збірки', 400);
}

try {
    $db = Database::getInstance();
    
    // Перевірити що збірка належить користувачу
    $stmt = $db->query(
        "SELECT id, build_name, user_id FROM saved_builds WHERE id = ?",
        [$buildId]
    );
    $build = $stmt->fetch();
    
    if (!$build) {
        sendError('Збірка не знайдена', 404);
    }
    
    if ((int)$build['user_id'] !== $userId) {
        sendError('Ви не маєте прав на видалення цієї збірки', 403);
    }
    
    // Видалити збірку (build_items видаляться автоматично через ON DELETE CASCADE)
    $stmt = $db->query(
        "DELETE FROM saved_builds WHERE id = ?",
        [$buildId]
    );
    
    // Відповідь
    sendSuccess([
        'build_id' => $buildId,
        'build_name' => $build['build_name']
    ], 'Збірку успішно видалено');
    
} catch (PDOException $e) {
    error_log("Error deleting build: " . $e->getMessage());
    sendError('Помилка бази даних', 500);
} catch (Exception $e) {
    error_log("Error in delete_build: " . $e->getMessage());
    sendError('Помилка сервера', 500);
}




