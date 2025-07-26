<?php

// Détecter l'environnement
$environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'local';

if ($environment === 'production' || getenv('RENDER')) {
    // Production : variables déjà disponibles
} else {
    // Local : charger le fichier .env
    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
    }
}

// Définir les constantes de base de données
define('DB_CONNECTION', $_ENV['DB_CONNECTION'] ?? 'pgsql');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'appwoyofal');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'postgres');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
