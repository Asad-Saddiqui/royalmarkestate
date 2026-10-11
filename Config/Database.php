<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/connection.php';
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            try {
                self::initializeSchema(self::$connection);
            } catch (Throwable $exception) {
                self::$connection = null;
                throw $exception;
            }
        }

        return self::$connection;
    }

    private static function initializeSchema(PDO $connection): void
    {
        // Probe first: normal requests do not need CREATE privileges or execute DDL.
        $tables = [
            'admin_users' => 'CREATE TABLE IF NOT EXISTS admin_users (id INT UNSIGNED PRIMARY KEY, email VARCHAR(254) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'properties' => 'CREATE TABLE IF NOT EXISTS properties (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(200) NOT NULL UNIQUE, payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'contact_messages' => "CREATE TABLE IF NOT EXISTS contact_messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(254) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', interest VARCHAR(120) NOT NULL DEFAULT '', message TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($tables as $table => $sql) {
            try {
                $connection->query('SELECT 1 FROM `' . $table . '` LIMIT 0');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '42S02') throw $exception;
                $connection->exec($sql);
            }
        }
    }
}
