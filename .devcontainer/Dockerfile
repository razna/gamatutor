FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

# Ekstensi PHP tambahan yang dibutuhkan SiKredit (PDF, zip, format Indonesia)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libzip-dev libicu-dev \
    && docker-php-ext-install gd zip intl \
    && rm -rf /var/lib/apt/lists/*
