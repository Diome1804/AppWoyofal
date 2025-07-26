<?php
// Configuration temporaire pour Railway
require_once "vendor/autoload.php";

// Définir les constantes pour Railway
define('DB_CONNECTION', 'pgsql');
define('DB_HOST', 'yamabiko.proxy.rlwy.net');
define('DB_PORT', '33405');
define('DB_DATABASE', 'railway');
define('DB_USERNAME', 'postgres');
define('DB_PASSWORD', 'piwMpVaBZIIcOyltuWqYmYHSsgGmiTTp');

// Forcer l'environnement en production pour ne pas créer les données de test
$_ENV['APP_ENV'] = 'production';

use App\Migrations\Migration;
use App\Seeders\Seeder;

try {
    echo "=== Configuration de Railway DB ===\n\n";
    
    // Exécuter les migrations
    echo "1. Exécution des migrations sur Railway...\n";
    $migration = new Migration();
    $migration->run();
    echo "\n";
    
    // Créer seulement les tranches tarifaires (pas les données de test)
    echo "2. Création des tranches tarifaires...\n";
    $seeder = new Seeder();
    $seeder->seedTranchesToarifaires();
    echo "\n";
    
    echo "✅ Railway DB configurée avec succès!\n";
    echo "Tables créées: clients, compteurs, tranches_tarifaires, consommations_mensuelles, achats_woyofal, logs_achats\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
