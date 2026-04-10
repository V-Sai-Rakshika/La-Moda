FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb-1.17.2 \
    && docker-php-ext-enable mongodb

RUN php -m | grep mongodb

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "/app"]