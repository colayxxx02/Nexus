# NEXUS

A PHP website starter designed for GitHub Codespaces and iPad-friendly development.

## Stack

- PHP 8.3
- MariaDB 11.4
- PDO MySQL
- PHP's built-in development server
- Docker Compose for the local database service

## Start in GitHub Codespaces

1. Pull the latest changes from main.
2. Rebuild the Codespace container so the MariaDB service is created:

   Codespaces menu -> Rebuild Container

3. Wait for the app and db services to become ready.
4. Start the PHP website:

    php -S 0.0.0.0:8000 -t public

5. Open the forwarded port 8000.

The MariaDB database is created automatically by Docker Compose using the development settings in docker-compose.yml. The PHP app connects to the database service using the hostname db.

## MariaDB terminal login

From the Codespaces terminal:

    mariadb -h db -u nexus_app -p nexus

Development password:

    nexus_dev_password

These are local development credentials only. Do not use them in production.

## Project layout

- public/index.php — main web page and database status
- public/health.php — JSON health endpoint
- src/bootstrap.php — PDO MySQL connection and schema initialization
- database/schema.sql — MariaDB schema
- docker-compose.yml — PHP app and MariaDB services
- .devcontainer/ — GitHub Codespaces configuration

## Adding tables

Add CREATE TABLE statements to database/schema.sql. The schema is applied automatically when the PHP app connects. Use PHP prepared statements for INSERT, SELECT, UPDATE, and DELETE operations.

## Next development steps

1. Decide the first real module, such as users, inventory, appointments, or records.
2. Add its tables to database/schema.sql.
3. Add PHP pages under public/ and reusable logic under src/.
4. Keep real credentials out of Git.

## Security note

This is a development foundation. Before production, add authentication, authorization, CSRF protection, validation, HTTPS, backups, and production-grade secrets.
