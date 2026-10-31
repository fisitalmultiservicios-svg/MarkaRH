# syntax=docker/dockerfile:1
FROM php:8.3-cli-bookworm
RUN --mount=type=secret,id=ca_bundle,required=false \
    if [ -f /run/secrets/ca_bundle ]; then export SSL_CERT_FILE=/run/secrets/ca_bundle CURL_CA_BUNDLE=/run/secrets/ca_bundle; fi; \
    apt-get update && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j2 pdo_mysql pdo_sqlite intl zip mbstring bcmath \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
