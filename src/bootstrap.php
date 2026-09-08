<?php

declare(strict_types=1);

function nexusDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = getenv('NEXUS_DB_PATH') ?: dirname(__DIR__) . '/data/nexus.sqlite';
    $databaseDirectory = dirname($databasePath);

    if (!is_dir($databaseDirectory)) {
        mkdir($databaseDirectory, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $schema = file_get_contents($schemaPath);

    if ($schema === false) {
        throw new RuntimeException('Unable to read the database schema.');
    }

    $pdo->exec($schema);

    return $pdo;
}
