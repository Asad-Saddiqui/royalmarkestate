<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';

return [
    'host' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3306),
    'database' => getenv('DB_DATABASE') !== false ? getenv('DB_DATABASE') : '',
    'username' => getenv('DB_USERNAME') !== false ? getenv('DB_USERNAME') : '',
    'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
];
