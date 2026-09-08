# NEXUS

A PHP and MariaDB daily planner designed for GitHub Codespaces and iPad-friendly development.

## Current MVP: Daily Planner

NEXUS is currently a simple single-user task and activity organizer. It lets you:

- Add tasks with a title, date, time, priority, category, and notes
- View tasks for today or any selected date
- Mark tasks as done or pending
- Delete tasks
- See pending and completed counts for the selected date

## Stack

- PHP 8.3
- MariaDB 11.4
- PDO MySQL
- PHP's built-in development server
- Docker Compose for the local database service

## Start in GitHub Codespaces

The Codespaces configuration starts the PHP server automatically after the container is running. Open forwarded port 8000 to view NEXUS.

If you need to start it manually:

    php -S 0.0.0.0:8000 -t public

The MariaDB database is created automatically by Docker Compose. The app connects to the database service using the hostname db.

## MariaDB terminal login

From the Codespaces terminal:

    mariadb -h db -u nexus_app -p nexus

Development password:

    nexus_dev_password

These are local development credentials only. Do not use them in production.

## Project layout

- public/index.php — daily planner interface and task actions
- public/health.php — JSON health endpoint
- src/bootstrap.php — PDO MySQL connection and schema initialization
- database/schema.sql — MariaDB schema and tasks table
- docker-compose.yml — PHP app and MariaDB services
- .devcontainer/ — GitHub Codespaces configuration

## Next client-request options

1. Add user login and accounts.
2. Add task editing.
3. Add weekly calendar view.
4. Add recurring tasks and reminders.
5. Add reports and productivity summaries.

## Security note

This is a development foundation. Before production, add authentication, authorization, CSRF protection, validation, HTTPS, backups, and production-grade secrets.


## Planner alarms

Click **Enable alarms** before using reminders. The browser checks scheduled tasks every 15 seconds and can play a sound, vibrate where supported, and show a notification. The planner page must stay open for browser alarms to work; background reminders when the page is closed will need a server scheduler and notification service.
