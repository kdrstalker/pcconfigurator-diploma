<?php
/**
 * API: Отримати збереженні збірки користувача
 * GET /api/get_builds.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';

setCorsHeaders();

try {
    // Перевірка авторизації
    $user = requireAuth();
    
    $db = Database::getInstance();
    
    // Отримати всі збірки користувача
    $sql = "SELECT id, build_name, total_price, total_tdp, created_at 
            FROM saved_builds 
            WHERE user_id = ? 
            ORDER BY created_at DESC";
    
    $stmt = $db->query($sql, [$user['id']]);
    $builds = $stmt->fetchAll();
    
    // Для кожної збірки отримати компоненти
    foreach ($builds as &$build) {
        $sql = "SELECT c.*, cat.slug as category_slug
                FROM build_items bi
                INNER JOIN components c ON bi.component_id = c.id
                INNER JOIN categories cat ON c.category_id = cat.id
                WHERE bi.build_id = ?";
        
        $stmt = $db->query($sql, [$build['id']]);
        $build['components'] = $stmt->fetchAll();
    }
    
    sendSuccess([
        'total_builds' => count($builds),
        'builds' => $builds
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_builds): " . $e->getMessage());
    sendError('Помилка отримання збірок', 500);
}





