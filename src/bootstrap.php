<?php

declare(strict_types=1);

function nexusDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('NEXUS_DB_HOST') ?: 'db';
    $port = getenv('NEXUS_DB_PORT') ?: '3306';
    $database = getenv('NEXUS_DB_NAME') ?: 'nexus';
    $username = getenv('NEXUS_DB_USER') ?: 'nexus_app';
    $password = getenv('NEXUS_DB_PASSWORD') ?: 'nexus_dev_password';
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    $lastException = null;

    for ($attempt = 1; $attempt <= 15; $attempt++) {
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            break;
        } catch (PDOException $exception) {
            $lastException = $exception;
            sleep(1);
        }
    }

    if (!$pdo instanceof PDO) {
        throw new RuntimeException(
            'Unable to connect to MariaDB: ' . ($lastException?->getMessage() ?? 'unknown error'),
            0,
            $lastException
        );
    }

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $schema = file_get_contents($schemaPath);

    if ($schema === false) {
        throw new RuntimeException('Unable to read the database schema.');
    }

    $pdo->exec($schema);

    return $pdo;
}
