<?php
/**
 * API: Автоматична генерація збірки
 * POST /api/auto_build.php
 * Body: { "task": "gaming_aaa", "budget": 35000 }
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/AutoBuilder.php';

setCorsHeaders();

try {
    // Отримати вхідні дані
    $input = getJsonInput();
    
    if (!$input) {
        sendError('Невалідний JSON', 400);
    }
    
    validateRequired($input, ['task', 'budget']);
    
    $taskType = $input['task'];
    $budget = (int)$input['budget'];
    
    // Валідація бюджету
    if ($budget < 10000) {
        sendError('Бюджет занадто малий. Мінімум: 10 000 грн', 400);
    }
    
    if ($budget > 500000) {
        sendError('Бюджет занадто великий. Максимум: 500 000 грн', 400);
    }
    
    // Валідація типу завдання
    $availableTaskTypes = array_keys(AutoBuilder::getTaskTypes());
    if (!in_array($taskType, $availableTaskTypes)) {
        sendError(
            'Невідомий тип завдання. Доступні: ' . implode(', ', $availableTaskTypes),
            400
        );
    }
    
    // Генерація збірки
    $db = Database::getInstance();
    $autoBuilder = new AutoBuilder($db);
    
    $result = $autoBuilder->generateBuild($taskType, $budget);
    
    if ($result['success']) {
        sendSuccess($result);
    } else {
        sendError(
            'Не вдалося згенерувати збірку',
            400,
            [
                'errors' => $result['errors'],
                'partial_build' => $result['build'] ?? null
            ]
        );
    }
    
} catch (Exception $e) {
    error_log("API Error (auto_build): " . $e->getMessage());
    sendError('Помилка генерації збірки', 500);
}





