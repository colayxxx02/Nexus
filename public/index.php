<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$databaseReady = false;
$errorMessage = null;

try {
    $database = nexusDatabase();
    $databaseReady = (bool) $database->query("SELECT 1")->fetchColumn();
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEXUS</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; background: #f5f7fb; color: #172033; }
        main { max-width: 760px; margin: 0 auto; padding: 56px 20px; }
        .card { background: white; border: 1px solid #e3e8f0; border-radius: 18px; padding: 32px; box-shadow: 0 14px 36px rgba(32, 52, 84, .08); }
        .eyebrow { color: #5068d8; font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 10px 0; font-size: clamp(2.2rem, 8vw, 4rem); letter-spacing: -.06em; }
        p { color: #5e6b80; line-height: 1.65; }
        .status { display: flex; align-items: center; gap: 10px; margin-top: 28px; padding: 14px 16px; border-radius: 12px; background: <?= $databaseReady ? '#edf9f1' : '#fff1f0' ?>; color: <?= $databaseReady ? '#17663a' : '#a12620' ?>; font-weight: 700; }
        .dot { width: 10px; height: 10px; border-radius: 999px; background: currentColor; }
        code { background: #f0f2f7; border-radius: 6px; padding: 2px 6px; }
    </style>
</head>
<body>
    <main>
        <section class="card">
            <div class="eyebrow">PHP system foundation</div>
            <h1>NEXUS is ready.</h1>
            <p>Your Codespaces starter is running. This page confirms that PHP can load the application and connect to the local database.</p>
            <div class="status">
                <span class="dot"></span>
                <?= $databaseReady ? 'Database connected' : 'Database connection needs attention' ?>
            </div>
            <?php if ($errorMessage !== null): ?>
                <p><strong>Developer message:</strong> <code><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></code></p>
            <?php endif; ?>
            <p>Next, add the first module and its tables to <code>database/schema.sql</code>.</p>
        </section>
    </main>
</body>
</html>
