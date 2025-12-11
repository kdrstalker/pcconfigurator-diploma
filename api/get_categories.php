<?php
/**
 * API: Отримати список категорій
 * GET /api/get_categories.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';

setCorsHeaders();

try {
    $db = Database::getInstance();
    
    $sql = "SELECT id, slug, name, sort_order 
            FROM categories 
            ORDER BY sort_order ASC";
    
    $stmt = $db->query($sql);
    $categories = $stmt->fetchAll();
    
    sendSuccess($categories);
    
} catch (Exception $e) {
    error_log("API Error (get_categories): " . $e->getMessage());
    sendError('Помилка отримання категорій', 500);
}





