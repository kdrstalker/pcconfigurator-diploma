<?php
/**
 * Database Connection Class
 * Патерн Singleton - забезпечує одне підключення до БД
 */

declare(strict_types=1);

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    // Параметри підключення
    private const HOST = 'localhost';
    private const DB_NAME = 'pc_configurator';
    private const USERNAME = 'root';
    private const PASSWORD = 'root';
    private const CHARSET = 'utf8mb4';

    /**
     * Приватний конструктор (Singleton pattern)
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Заборона клонування об'єкта
     */
    private function __clone() {}

    /**
     * Заборона десеріалізації
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Отримання єдиного екземпляру класу
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Підключення до бази даних
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::HOST,
                self::DB_NAME,
                self::CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false, // Повертати числа як числа, а не рядки
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::CHARSET
            ];

            $this->connection = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Помилка підключення до бази даних. Спробуйте пізніше.");
        }
    }

    /**
     * Отримання PDO з'єднання
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Виконання запиту з prepared statements
     * 
     * @param string $query SQL запит
     * @param array $params Параметри для підстановки
     * @return PDOStatement
     */
    public function query(string $query, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }
}



