<?php
// Test de connexion à Railway DB
try {
    $dsn = "pgsql:host=yamabiko.proxy.rlwy.net;port=33405;dbname=railway";
    $username = "postgres";
    $password = "piwMpVaBZIIcOyltuWqYmYHSsgGmiTTp";
    
    echo "Tentative de connexion à Railway...\n";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Connexion réussie!\n";
    
    // Test de création d'une table simple
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_connection (id SERIAL PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    echo "✅ Création de table test réussie!\n";
    
    // Nettoyer
    $pdo->exec("DROP TABLE IF EXISTS test_connection");
    echo "✅ Test de connexion Railway terminé avec succès!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
