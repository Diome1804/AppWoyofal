<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/bootstrap.php';

use App\Seeders\Seeder;

try {
    echo "=== Exécution des seeders AppWoyofal ===\n\n";
    
    $seeder = new Seeder();
    $seeder->run();
    
    // Afficher les données de test
    echo "\n--- Données de test disponibles ---\n";
    $testData = $seeder->getTestData();
    
    echo "Compteurs de test : " . implode(', ', $testData['compteurs_test']) . "\n";
    echo "Montants de test : " . implode(', ', $testData['montants_test']) . " FCFA\n";
    
    echo "\n✅ Seeding terminé avec succès!\n";
    echo "\nCommande de test :\n";
    echo "curl -X POST http://localhost:8081/api/woyofal/achat \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\"compteur\":\"123456789\",\"montant\":5000}'\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du seeding : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}
