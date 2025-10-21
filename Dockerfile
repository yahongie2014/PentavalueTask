# Use PHP CLI image
FROM php:8.2-cli

WORKDIR /var/www

# Install system deps and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copy composer binary from the official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose port and start built-in PHP server (for simple testing only)
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
