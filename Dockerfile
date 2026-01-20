# Dockerfile pour Symfony avec PHP 8.2, OPcache et Composer
FROM php:8.2-fpm

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_MEMORY_SIZE=256 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration OPcache pour la production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=${PHP_OPCACHE_MEMORY_SIZE}'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=${PHP_OPCACHE_MAX_ACCELERATED_FILES}'; \
    echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_file_override=1'; \
    echo 'opcache.optimization_level=0x7FFFBFFF'; \
    echo 'opcache.max_wasted_percentage=10'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP-FPM pour la production
RUN { \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 50'; \
    echo 'pm.start_servers = 10'; \
    echo 'pm.min_spare_servers = 5'; \
    echo 'pm.max_spare_servers = 20'; \
    echo 'pm.max_requests = 500'; \
    echo 'pm.status_path = /status'; \
    } >> /usr/local/etc/php-fpm.d/www.conf

# Configuration PHP générale
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 20M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'date.timezone = Africa/Dakar'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Création du répertoire de travail (aligné avec Nginx : /var/www/gestion-demande)
WORKDIR /var/www/gestion-demande

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances Composer (sans dev en production)
RUN if [ "$APP_ENV" = "prod" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; \
    else \
        composer install --optimize-autoloader --no-interaction --prefer-dist; \
    fi

# Définition des permissions
RUN chown -R www-data:www-data /var/www/gestion-demande \
    && chmod -R 755 /var/www/gestion-demande \
    && chmod -R 775 /var/www/gestion-demande/var

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Commande par défaut
CMD ["php-fpm"]
