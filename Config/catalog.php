<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
function catalogNames(string $kind): array {
    $query=Database::getConnection()->prepare('SELECT name FROM catalog_entries WHERE kind=? ORDER BY name');
    $query->execute([$kind]);
    return $query->fetchAll(PDO::FETCH_COLUMN);
}
