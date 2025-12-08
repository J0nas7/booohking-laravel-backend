# Use official PHP 8.4 with Apache
FROM php:8.4-apache

# Install dependencies for Laravel + extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Enable Apache mod_rewrite for Laravel pretty URLs
RUN a2enmod rewrite

# Change Apache DocumentRoot to Laravel's public folder and set Directory permissions
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
 && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/c\<Directory /var/www/html/public>\n\tOptions Indexes FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>' /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy Laravel project files into container
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set Laravel storage permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public

# Install PHP dependencies (Laravel)
RUN composer install --no-dev --optimize-autoloader

# Expose port 8080 like your GAE config
EXPOSE 8080

# Configure Apache to listen on port 8080 instead of default 80
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Use Apache as entrypoint
CMD ["apache2-foreground"]
