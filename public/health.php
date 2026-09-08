<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json');

try {
    nexusDatabase()->query('SELECT 1')->fetchColumn();
    echo json_encode([
        'status' => 'ok',
        'app' => 'NEXUS',
        'database' => 'connected',
    ], JSON_PRETTY_PRINT);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'app' => 'NEXUS',
        'database' => 'unavailable',
    ], JSON_PRETTY_PRINT);
}
