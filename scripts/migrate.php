<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/bootstrap.php';

use App\Migrations\Migration;

try {
    echo "=== Exécution des migrations AppWoyofal ===\n\n";
    
    $migration = new Migration();
    $migration->run();
    
    echo "\n✅ Migrations terminées avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors des migrations : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}
