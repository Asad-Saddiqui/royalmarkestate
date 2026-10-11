<?php
declare(strict_types=1);

// Read a local .env without executing its contents or overriding server variables.
function loadEnvironment(string $path): void
{
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $match)) continue;
        [$unused, $key, $value] = $match;
        if (getenv($key) !== false) continue;
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
    }
}

loadEnvironment(dirname(__DIR__) . '/.env');
