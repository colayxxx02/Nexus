FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

USER root

# The base image may contain an outdated Yarn apt source. Remove it before apt update.
RUN find /etc/apt/sources.list.d -maxdepth 1 -type f -iname '*yarn*' -delete \
    && apt-get update \
    && apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install pdo_mysql \
    && php -m | grep -q '^pdo_mysql$' \
    && rm -rf /var/lib/apt/lists/*

USER vscode
