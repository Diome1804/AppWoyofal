# Image PHP avec serveur intégré pour Render
FROM php:8.3-cli

# Installer les dépendances système + extensions PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer en premier (cache Docker)
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copier le reste de l'application
COPY . .

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port
EXPOSE $PORT

# Script de démarrage
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]