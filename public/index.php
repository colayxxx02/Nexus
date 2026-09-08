<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');
session_start();

require_once dirname(__DIR__) . '/src/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function validPlannerDate(string $value, string $fallback): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? $value : $fallback;
}

function plannerTime(?string $value): ?string
{
    $value = trim((string) $value);
    return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value . ':00' : null;
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$selectedDate = validPlannerDate((string) ($_GET['date'] ?? $today), $today);
$flash = $_SESSION['planner_flash'] ?? null;
unset($_SESSION['planner_flash']);
$errorMessage = null;
$tasks = [];
$pendingCount = 0;
$doneCount = 0;

try {
    $database = nexusDatabase();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $returnDate = validPlannerDate((string) ($_POST['return_date'] ?? $today), $today);

        if ($action === 'create') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $taskDate = validPlannerDate((string) ($_POST['task_date'] ?? $today), $today);
            $priority = (string) ($_POST['priority'] ?? 'medium');
            $category = trim((string) ($_POST['category'] ?? 'General')) ?: 'General';
            $notes = trim((string) ($_POST['notes'] ?? '')) ?: null;
            $startTime = plannerTime($_POST['start_time'] ?? null);

            if ($title === '') {
                $_SESSION['planner_flash'] = 'Please add a task title.';
            } elseif (!in_array($priority, ['low', 'medium', 'high'], true)) {
                $_SESSION['planner_flash'] = 'Please choose a valid priority.';
            } else {
                $statement = $database->prepare(
                    'INSERT INTO tasks (title, notes, task_date, start_time, priority, category)
' .
                    'VALUES (:title, :notes, :task_date, :start_time, :priority, :category)'
                );
                $statement->execute([
                    'title' => $title,
                    'notes' => $notes,
                    'task_date' => $taskDate,
                    'start_time' => $startTime,
                    'priority' => $priority,
                    'category' => $category,
                ]);
                $_SESSION['planner_flash'] = 'Task added to your planner.';
                $returnDate = $taskDate;
            }
        } elseif ($action === 'toggle') {
            $taskId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($taskId) {
                $statement = $database->prepare(
                    "UPDATE tasks SET status = IF(status = 'done', 'pending', 'done') WHERE id = :id"
                );
                $statement->execute(['id' => $taskId]);
                $_SESSION['planner_flash'] = 'Task status updated.';
            }
        } elseif ($action === 'delete') {
            $taskId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($taskId) {
                $statement = $database->prepare('DELETE FROM tasks WHERE id = :id');
                $statement->execute(['id' => $taskId]);
                $_SESSION['planner_flash'] = 'Task removed from your planner.';
            }
        }

        header('Location: ?date=' . urlencode($returnDate));
        exit;
    }

    $statement = $database->prepare(
        'SELECT id, title, notes, task_date, start_time, priority, category, status
' .
        'FROM tasks WHERE task_date = :task_date
' .
        "ORDER BY status = 'done', start_time IS NULL, start_time, created_at"
    );
    $statement->execute(['task_date' => $selectedDate]);
    $tasks = $statement->fetchAll();

    foreach ($tasks as $task) {
        if ($task['status'] === 'done') {
            $doneCount++;
        } else {
            $pendingCount++;
        }
    }
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

$displayDate = DateTimeImmutable::createFromFormat('!Y-m-d', $selectedDate);
$displayDateLabel = $displayDate instanceof DateTimeImmutable ? $displayDate->format('l, F j, Y') : $selectedDate;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEXUS Daily Planner</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div>
                <div class="eyebrow">NEXUS planner</div>
                <h1>Plan your day.</h1>
                <p class="subtitle">Keep your tasks and activities in one calm, simple view.</p>
            </div>
            <form class="date-picker" method="get">
                <label for="date" class="sr-only">Planner date</label>
                <input id="date" type="date" name="date" value="<?= e($selectedDate) ?>">
                <button class="btn btn-light" type="submit">View date</button>
            </form>
        </header>

        <?php if ($flash !== null): ?>
            <div class="flash"><?= e((string) $flash) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <div class="error"><strong>Database error:</strong> <?= e($errorMessage) ?></div>
        <?php endif; ?>

        <section class="grid">
            <aside class="panel">
                <h2>Add a task</h2>
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="return_date" value="<?= e($selectedDate) ?>">
                    <div class="field">
                        <label for="title">Task or activity</label>
                        <input id="title" name="title" type="text" maxlength="200" placeholder="e.g. Study PHP" required>
                    </div>
                    <div class="two-fields">
                        <div class="field">
                            <label for="task_date">Date</label>
                            <input id="task_date" name="task_date" type="date" value="<?= e($selectedDate) ?>" required>
                        </div>
                        <div class="field">
                            <label for="start_time">Time</label>
                            <input id="start_time" name="start_time" type="time">
                        </div>
                    </div>
                    <div class="two-fields">
                        <div class="field">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="category">Category</label>
                            <input id="category" name="category" type="text" maxlength="80" value="General">
                        </div>
                    </div>
                    <div class="field">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Optional details..."></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Add to planner</button>
                </form>
            </aside>

            <section class="panel">
                <div class="list-header">
                    <div>
                        <h2><?= e($displayDateLabel) ?></h2>
                        <small><?= $selectedDate === $today ? 'Today' : 'Selected date' ?></small>
                    </div>
                    <div class="stats">
                        <div class="stat"><strong><?= $pendingCount ?></strong><span>To do</span></div>
                        <div class="stat"><strong><?= $doneCount ?></strong><span>Done</span></div>
                    </div>
                </div>

                <?php if ($tasks === []): ?>
                    <div class="empty">No tasks yet for this date.<br>Add your first activity on the left.</div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <article class="task <?= $task['status'] === 'done' ? 'done' : '' ?>">
                            <form method="post">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                <input type="hidden" name="return_date" value="<?= e($selectedDate) ?>">
                                <button class="check" type="submit" aria-label="Mark task <?= $task['status'] === 'done' ? 'pending' : 'done' ?>" <?= $task['status'] === 'done' ? 'checked' : '' ?>>✓</button>
                            </form>
                            <div>
                                <p class="task-title"><?= e((string) $task['title']) ?></p>
                                <div class="task-meta">
                                    <?php if ($task['start_time'] !== null): ?><span class="pill">🕒 <?= e(substr((string) $task['start_time'], 0, 5)) ?></span><?php endif; ?>
                                    <span class="pill"><?= e((string) $task['category']) ?></span>
                                    <span class="pill priority-<?= e((string) $task['priority']) ?>"><?= e(ucfirst((string) $task['priority'])) ?></span>
                                </div>
                                <?php if ($task['notes'] !== null && $task['notes'] !== ''): ?><p class="task-notes"><?= e((string) $task['notes']) ?></p><?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <form method="post" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                    <input type="hidden" name="return_date" value="<?= e($selectedDate) ?>">
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </section>
    </main>
    <script src="assets/app.js" defer></script>
</body>
</html>
