<?php
// Vérifier les données dans Railway
require_once "vendor/autoload.php";

try {
    $dsn = "pgsql:host=yamabiko.proxy.rlwy.net;port=33405;dbname=railway";
    $username = "postgres";
    $password = "piwMpVaBZIIcOyltuWqYmYHSsgGmiTTp";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "=== DONNÉES DANS RAILWAY DB ===\n\n";
    
    // 1. Tranches tarifaires
    echo "📊 TRANCHES TARIFAIRES:\n";
    $stmt = $pdo->query("SELECT nom, seuil_min, seuil_max, prix_kwh FROM tranches_tarifaires ORDER BY ordre");
    $tranches = $stmt->fetchAll();
    foreach ($tranches as $tranche) {
        echo "  • {$tranche['nom']}: {$tranche['seuil_min']}-" . ($tranche['seuil_max'] ?? '∞') . " kWh à {$tranche['prix_kwh']} FCFA/kWh\n";
    }
    echo "\n";
    
    // 2. Clients
    echo "👤 CLIENTS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $clientCount = $stmt->fetch()['count'];
    echo "  • Total: $clientCount clients\n";
    if ($clientCount > 0) {
        $stmt = $pdo->query("SELECT nom, prenom, id FROM clients LIMIT 3");
        $clients = $stmt->fetchAll();
        foreach ($clients as $client) {
            echo "    - {$client['nom']} {$client['prenom']} (ID: {$client['id']})\n";
        }
    }
    echo "\n";
    
    // 3. Compteurs
    echo "🔌 COMPTEURS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM compteurs");
    $compteurCount = $stmt->fetch()['count'];
    echo "  • Total: $compteurCount compteurs\n";
    if ($compteurCount > 0) {
        $stmt = $pdo->query("SELECT numero, client_id FROM compteurs LIMIT 3");
        $compteurs = $stmt->fetchAll();
        foreach ($compteurs as $compteur) {
            echo "    - {$compteur['numero']} (Client ID: {$compteur['client_id']})\n";
        }
    }
    echo "\n";
    
    // 4. Consommations
    echo "⚡ CONSOMMATIONS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM consommations_mensuelles");
    $consoCount = $stmt->fetch()['count'];
    echo "  • Total: $consoCount enregistrements\n\n";
    
    // 5. Achats
    echo "💰 ACHATS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM achats_woyofal");
    $achatCount = $stmt->fetch()['count'];
    echo "  • Total: $achatCount achats\n\n";
    
    // 6. Logs
    echo "📝 LOGS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM logs_achats");
    $logCount = $stmt->fetch()['count'];
    echo "  • Total: $logCount logs\n\n";
    
    echo "✅ Railway DB prête pour production!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
