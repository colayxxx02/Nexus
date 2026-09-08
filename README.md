# NEXUS

A PHP website starter designed for GitHub Codespaces and iPad-friendly development.

## Stack

- PHP 8.3
- PDO with SQLite
- PHP's built-in development server
- No framework or package manager required for the first iteration

## Start in GitHub Codespaces

1. Open the repository in a Codespace.
2. Wait for the PHP dev container to finish creating.
3. Run this command:

    php -S 0.0.0.0:8000 -t public

4. Open the forwarded port 8000.

The database is created automatically at data/nexus.sqlite on the first request.

## Project layout

- public/index.php — main web page and database status
- public/health.php — JSON health endpoint
- src/bootstrap.php — PDO connection and schema initialization
- database/schema.sql — database schema
- data/ — local SQLite database files; ignored by Git
- .devcontainer/ — GitHub Codespaces configuration

## Next development steps

1. Decide the first real module, such as users, inventory, appointments, or records.
2. Add its tables to database/schema.sql.
3. Add PHP pages under public/ and reusable logic under src/.
4. Keep credentials and environment-specific settings out of Git.

## Security note

This is a development foundation. Before production, add authentication, authorization, CSRF protection, validation, HTTPS, backups, and a production database configuration.
