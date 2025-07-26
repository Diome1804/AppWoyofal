# Image PHP avec serveur intégré pour production
FROM php:8.3-cli

# Metadata
LABEL maintainer="AppWoyofal Team"
LABEL version="1.0.0"
LABEL description="API Woyofal - Système de recharge électrique prépayée"

# Installer les dépendances système + extensions PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer utilisateur non-root pour sécurité
RUN useradd -m -s /bin/bash appuser

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer en premier (optimisation cache Docker)
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-cache

# Copier le code source
COPY . .

# Supprimer les fichiers non nécessaires en production
RUN rm -rf \
    docker* \
    *.md \
    .git* \
    tests/ \
    setup_railway.php \
    check_railway_data.php \
    add_test_data_railway.php \
    test_railway_connection.php \
    start.sh

# Configurer les permissions
RUN chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html

# Basculer vers utilisateur non-root
USER appuser

# Exposer le port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health.php || exit 1

# Script de démarrage
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]