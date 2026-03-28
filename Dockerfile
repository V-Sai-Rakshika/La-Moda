FROM php:8.2-cli

# Install required extensions
RUN apt-get update && apt-get install -y \
    unzip git curl \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install dependencies
RUN composer install

# Expose port
EXPOSE 10000

# Start server
CMD ["php", "-S", "0.0.0.0:10000"]