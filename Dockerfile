FROM php:8.3.11-fpm

# Update package list and install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    postgresql-client \
    libpq-dev \
    libicu-dev \
    nodejs \
    npm \
    nginx \
    supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install required packages
RUN docker-php-ext-install pdo pgsql pdo_pgsql gd bcmath zip intl \
    && docker-php-ext-configure intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www/html/

# Copy the codebase
COPY . ./

# Run composer install for production and give permissions
RUN composer install --ignore-platform-req=php --ignore-platform-req=ext-intl --no-dev --optimize-autoloader \
    && composer clear-cache \
    && php artisan package:discover --ansi \
    && chmod -R 775 storage \
    && chown -R www-data:www-data storage \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache

# Copy configuration files
COPY ./nginx-render.conf /etc/nginx/sites-available/default
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./scripts/render-entrypoint /usr/local/bin/render-entrypoint

# Give permissions
RUN chmod a+x /usr/local/bin/render-entrypoint

# Expose port (Render will map this to the PORT env var)
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# working ec2 own
# FROM php:8.3.11-fpm

# # Update package list and install dependencies
# RUN apt-get update && apt-get install -y \
#     libzip-dev \
#     libpng-dev \
#     postgresql-client \
#     libpq-dev \
#     libicu-dev \
#     nodejs \
#     npm \
#     nginx \
#     supervisor \
#     && apt-get clean \
#     && rm -rf /var/lib/apt/lists/*

# # Install Composer
# COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
# ENV COMPOSER_ALLOW_SUPERUSER=1

# # Install required packages
# RUN docker-php-ext-install pdo pgsql pdo_pgsql gd bcmath zip intl \
#     && docker-php-ext-configure intl \
#     && pecl install redis \
#     && docker-php-ext-enable redis

# # Set working directory
# WORKDIR /var/www/html/

# # Copy the codebase
# COPY . ./

# # Run composer install for production and give permissions
# RUN composer install --ignore-platform-req=php --ignore-platform-req=ext-intl --no-dev --optimize-autoloader \
#     && composer clear-cache \
#     && php artisan package:discover --ansi \
#     && chmod -R 775 storage \
#     && chown -R www-data:www-data storage \
#     && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache

# # Copy configuration files
# COPY ./nginx-render.conf /etc/nginx/sites-available/default
# COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# COPY ./scripts/render-entrypoint /usr/local/bin/render-entrypoint

# # Give permissions
# RUN chmod a+x /usr/local/bin/render-entrypoint

# # Expose port (Render will map this to the PORT env var)
# EXPOSE 10000

# ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
# CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


