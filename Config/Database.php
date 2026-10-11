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
            'company_profile' => 'CREATE TABLE IF NOT EXISTS company_profile (id TINYINT UNSIGNED PRIMARY KEY, payload LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'catalog_entries' => 'CREATE TABLE IF NOT EXISTS catalog_entries (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE KEY catalog_name (kind,name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
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
        $columns = $connection->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_UNIQUE);
        foreach (['name' => "VARCHAR(100) NOT NULL DEFAULT ''", 'role' => "VARCHAR(10) NOT NULL DEFAULT 'admin'", 'is_blocked' => 'TINYINT(1) NOT NULL DEFAULT 0'] as $column => $definition) {
            if (!isset($columns[$column])) {
                try { $connection->exec('ALTER TABLE admin_users ADD COLUMN `' . $column . '` ' . $definition); }
                catch (PDOException $exception) { if (($exception->errorInfo[1] ?? null) !== 1060) throw $exception; }
            }
        }
        if (!str_contains($columns['id']['Extra'] ?? '', 'auto_increment')) {
            $connection->exec('ALTER TABLE admin_users MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }
}
