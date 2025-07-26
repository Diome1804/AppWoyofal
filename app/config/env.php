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
// Priorité à DATABASE_URL si disponible (Railway/Render)
if (isset($_ENV['DATABASE_URL']) && !empty($_ENV['DATABASE_URL'])) {
    $url = parse_url($_ENV['DATABASE_URL']);
    define('DB_CONNECTION', 'pgsql');
    define('DB_HOST', $url['host']);
    define('DB_PORT', $url['port'] ?? '5432');
    define('DB_DATABASE', ltrim($url['path'], '/'));
    define('DB_USERNAME', $url['user']);
    define('DB_PASSWORD', $url['pass']);
} else {
    // Fallback vers variables séparées
    define('DB_CONNECTION', $_ENV['DB_CONNECTION'] ?? 'pgsql');
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
    define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'appwoyofal');
    define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'postgres');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
}
