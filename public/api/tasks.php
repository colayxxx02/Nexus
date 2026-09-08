<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$requestedDate = (string) ($_GET['date'] ?? $today);
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDate);
$selectedDate = $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $requestedDate ? $requestedDate : $today;

try {
    $statement = nexusDatabase()->prepare(
        'SELECT id, title, task_date, start_time, status FROM tasks WHERE task_date = :task_date ORDER BY start_time IS NULL, start_time, created_at'
    );
    $statement->execute(['task_date' => $selectedDate]);
    echo json_encode(['tasks' => $statement->fetchAll()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['error' => 'Unable to load planner tasks.']);
}
