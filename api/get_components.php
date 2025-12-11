<?php
/**
 * API: Отримати компоненти категорії
 * POST /api/get_components.php
 * Body: { "category": "cpu" }
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Component.php';

setCorsHeaders();

try {
    // Отримати вхідні дані
    $input = getJsonInput();
    
    if (!$input) {
        sendError('Невалідний JSON', 400);
    }
    
    validateRequired($input, ['category']);
    
    $categorySlug = $input['category'];
    
    // Отримати компоненти
    $db = Database::getInstance();
    $component = new Component($db);
    
    $components = $component->getByCategory($categorySlug);
    
    sendSuccess($components);
    
} catch (Exception $e) {
    error_log("API Error (get_components): " . $e->getMessage());
    sendError('Помилка отримання компонентів', 500);
}





