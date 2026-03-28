FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip git curl libssl-dev pkg-config

# Install MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install dependencies
RUN composer install --ignore-platform-req=ext-mongodb

# Expose port
EXPOSE 10000

# Start server
CMD ["php", "-S", "0.0.0.0:10000"]