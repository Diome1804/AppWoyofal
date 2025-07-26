#!/bin/bash
set -e

echo "=== AppWoyofal Startup ==="

# 1. Configuration de la base de données
echo "Configuring database..."
php scripts/setup_database.php || {
    echo "Database setup failed, but continuing..."
}

# 2. Démarrage du serveur PHP
echo "Starting PHP server on port $PORT..."
exec php -S 0.0.0.0:$PORT -t public
