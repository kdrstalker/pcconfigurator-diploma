<?php
/**
 * Тестовий скрипт для перевірки завантаження компонента
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/db.php';

$componentId = isset($_GET['id']) ? (int)$_GET['id'] : 39;

echo "<h1>Тест завантаження компонента ID: $componentId</h1>";

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM components WHERE id = ? LIMIT 1", [$componentId]);
    $component = $stmt->fetch();
    
    if (!$component) {
        echo "<p style='color:red;'>Компонент не знайдено!</p>";
        exit;
    }
    
    echo "<h2>Дані компонента:</h2>";
    echo "<table border='1' cellpadding='5'>";
    foreach ($component as $key => $value) {
        $type = gettype($value);
        $length = is_string($value) ? strlen($value) : '-';
        $display = is_null($value) ? 'NULL' : (is_string($value) && strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value);
        
        echo "<tr>";
        echo "<td><strong>$key</strong></td>";
        echo "<td>$type</td>";
        echo "<td>$length</td>";
        echo "<td>" . htmlspecialchars((string)$display) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Тест htmlspecialchars:</h2>";
    echo "<ul>";
    echo "<li>name: " . htmlspecialchars((string)($component['name'] ?? '')) . "</li>";
    echo "<li>socket: " . htmlspecialchars((string)($component['socket'] ?? '')) . "</li>";
    echo "<li>ram_type: " . htmlspecialchars((string)($component['ram_type'] ?? '')) . "</li>";
    echo "<li>tdp: " . htmlspecialchars((string)($component['tdp'] ?? '')) . "</li>";
    echo "<li>psu_wattage: " . htmlspecialchars((string)($component['psu_wattage'] ?? '')) . "</li>";
    echo "<li>specs_json length: " . strlen($component['specs_json'] ?? '') . "</li>";
    echo "</ul>";
    
    echo "<p style='color:green;'><strong>✓ Всі дані завантажені успішно!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>ПОМИЛКА:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}



