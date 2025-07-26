<?php
// Ajouter quelques données de test dans Railway
require_once "vendor/autoload.php";

// Configuration Railway
define('DB_CONNECTION', 'pgsql');
define('DB_HOST', 'yamabiko.proxy.rlwy.net');
define('DB_PORT', '33405');
define('DB_DATABASE', 'railway');
define('DB_USERNAME', 'postgres');
define('DB_PASSWORD', 'piwMpVaBZIIcOyltuWqYmYHSsgGmiTTp');

use App\Seeders\Seeder;

try {
    echo "=== Ajout de données de test dans Railway ===\n\n";
    
    $seeder = new Seeder();
    
    // Créer quelques clients et compteurs de test
    $seeder->seedClients();
    $seeder->seedCompteurs();
    
    echo "✅ Données de test ajoutées dans Railway!\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
