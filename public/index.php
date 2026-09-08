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
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --ink: #18212f;
            --muted: #6d7788;
            --line: #e5e9f0;
            --panel: #ffffff;
            --soft: #f5f7fb;
            --brand: #5068d8;
            --brand-dark: #3f53b7;
            --green: #1b7a45;
            --red: #b33a33;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--soft); color: var(--ink); }
        button, input, select, textarea { font: inherit; }
        button { cursor: pointer; }
        .shell { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 30px 0 60px; }
        .topbar { display: flex; justify-content: space-between; align-items: end; gap: 20px; margin-bottom: 24px; }
        .eyebrow { color: var(--brand); font-size: .76rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        h1 { margin: 7px 0 4px; font-size: clamp(2rem, 6vw, 3.4rem); letter-spacing: -.06em; }
        .subtitle { color: var(--muted); margin: 0; }
        .date-picker { display: flex; align-items: center; gap: 8px; }
        .date-picker input, .field input, .field select, .field textarea { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--ink); padding: 11px 12px; }
        .date-picker input { width: auto; }
        .btn { border: 0; border-radius: 10px; padding: 11px 15px; font-weight: 750; }
        .btn-primary { background: var(--brand); color: white; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-light { background: #eef1f8; color: var(--ink); }
        .btn-danger { background: transparent; color: var(--red); padding: 7px 0; }
        .grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 18px; padding: 22px; box-shadow: 0 12px 30px rgba(40, 55, 85, .06); }
        .panel h2 { margin: 0 0 18px; font-size: 1.05rem; }
        .field { margin-bottom: 13px; }
        .field label { display: block; color: var(--muted); font-size: .82rem; font-weight: 750; margin-bottom: 6px; }
        .field textarea { min-height: 80px; resize: vertical; }
        .two-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .stats { display: flex; gap: 10px; margin-bottom: 16px; }
        .stat { flex: 1; background: var(--soft); border-radius: 12px; padding: 13px; }
        .stat strong { display: block; font-size: 1.35rem; }
        .stat span { color: var(--muted); font-size: .78rem; }
        .flash { background: #edf9f1; border: 1px solid #ccebd8; color: var(--green); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-weight: 650; }
        .error { background: #fff1f0; border: 1px solid #f2cfcb; color: var(--red); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
        .list-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 15px; }
        .list-header h2 { margin: 0; }
        .list-header small { color: var(--muted); }
        .task { display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: start; padding: 15px 0; border-top: 1px solid var(--line); }
        .task:first-of-type { border-top: 0; }
        .check { width: 22px; height: 22px; accent-color: var(--brand); margin-top: 2px; }
        .task-title { margin: 0 0 5px; font-weight: 800; }
        .task.done .task-title { color: #8a93a1; text-decoration: line-through; }
        .task-meta { display: flex; flex-wrap: wrap; gap: 7px; color: var(--muted); font-size: .78rem; }
        .pill { border-radius: 999px; padding: 4px 8px; background: #eef1f8; }
        .priority-high { background: #fff0ee; color: #a62e26; }
        .priority-medium { background: #fff8df; color: #876800; }
        .priority-low { background: #edf9f1; color: #17663a; }
        .task-notes { color: var(--muted); font-size: .88rem; margin: 8px 0 0; white-space: pre-wrap; }
        .task-actions { display: flex; flex-direction: column; align-items: end; gap: 4px; }
        .empty { text-align: center; color: var(--muted); padding: 42px 15px; border-top: 1px solid var(--line); }
        @media (max-width: 780px) {
            .topbar { align-items: stretch; flex-direction: column; }
            .date-picker { align-items: stretch; }
            .date-picker input { flex: 1; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
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
</body>
</html>
