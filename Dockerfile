FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip git curl libssl-dev pkg-config

# Install latest MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Install dependencies (ignore platform mismatch during build)
RUN composer install --ignore-platform-reqs

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]