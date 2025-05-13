# Use the official PHP image with Apache
FROM php:8.1-apache

# Install dependencies (optional: zip for composer or other PHP extensions)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip

# Enable Apache mod_rewrite (important for frameworks or URL rewriting)
RUN a2enmod rewrite

# Copy your project into the Apache web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
