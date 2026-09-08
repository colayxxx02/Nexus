FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

USER vscode
