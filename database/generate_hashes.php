<?php
/**
 * Генератор bcrypt хешів для паролів
 * Використовується для створення SQL-скриптів
 */

$passwords = [
    'test123' => password_hash('test123', PASSWORD_DEFAULT),
    'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
];

echo "Згенеровані bcrypt хеші:\n\n";

foreach ($passwords as $pass => $hash) {
    echo "Пароль: {$pass}\n";
    echo "Хеш:    {$hash}\n\n";
}

echo "\n--- SQL для вставки ---\n\n";

echo "-- admin (test123)\n";
echo "INSERT INTO users (login, password, role) VALUES ('admin', '{$passwords['test123']}', 'admin');\n\n";

echo "-- superadmin (admin123)\n";
echo "INSERT INTO users (login, password, role) VALUES ('superadmin', '{$passwords['admin123']}', 'admin');\n\n";

echo "-- testuser (test123)\n";
echo "INSERT INTO users (login, password, role) VALUES ('testuser', '{$passwords['test123']}', 'user');\n\n";











