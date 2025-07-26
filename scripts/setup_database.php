<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/bootstrap.php';

use App\Migrations\Migration;
use App\Seeders\Seeder;

try {
    echo "=== Configuration de la base de données AppWoyofal ===\n\n";

// En production, ne pas seeder les données de test
$isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';
    
    // Exécuter les migrations
    echo "1. Exécution des migrations...\n";
    $migration = new Migration();
    $migration->run();
    echo "\n";
    
    // Exécuter les seeders seulement en développement
    if (!$isProduction) {
        echo "2. Exécution des seeders...\n";
        $seeder = new Seeder();
        $seeder->run();
    } else {
        echo "2. Seeders ignorés en production\n";
        // Créer uniquement les tranches tarifaires officielles
        $seeder = new Seeder();
        $seeder->seedTranchesToarifaires();
        echo "✓ Tranches tarifaires créées\n";
    }
    echo "\n";
    
    // Afficher les données de test
    echo "3. Données de test disponibles :\n";
    $testData = $seeder->getTestData();
    
    echo "Clients de test :\n";
    foreach ($testData['clients_test'] as $nom => $data) {
        echo "  - $nom: compteur {$data['compteur']}, email {$data['email']}\n";
    }
    
    echo "\nCompteurs disponibles : " . implode(', ', $testData['compteurs_test']) . "\n";
    echo "Montants de test : " . implode(', ', $testData['montants_test']) . " FCFA\n";
    
    echo "\n=== Configuration terminée avec succès! ===\n";
    echo "Vous pouvez maintenant tester l'API avec :\n";
    echo "docker-compose up -d\n";
    echo "curl -X POST http://localhost:8081/api/woyofal/achat \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\"compteur\":\"123456789\",\"montant\":5000}'\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la configuration : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}
