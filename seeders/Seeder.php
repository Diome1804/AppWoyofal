<?php

namespace App\Seeders;

use App\Core\Database;
use PDO;

class Seeder
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function run(): void
    {
        echo "Début du seeding...\n";
        
        $this->seedTranchesToarifaires();
        $this->seedClients();
        $this->seedCompteurs();
        $this->seedConsommationsMensuelles();
        
        echo "Seeding terminé avec succès!\n";
    }
    
    public function seedTranchesToarifaires(): void
    {
        echo "Seeding des tranches tarifaires...\n";
        
        // Supprimer les données existantes pour permettre re-run
        $this->pdo->exec("DELETE FROM tranches_tarifaires");
        $this->pdo->exec("ALTER SEQUENCE tranches_tarifaires_id_seq RESTART WITH 1");
        
        $tranches = [
            ['Tranche 1 - Social', 0.00, 150.00, 91.00, 1],
            ['Tranche 2 - Normal', 150.01, 250.00, 102.00, 2],
            ['Tranche 3 - Intermédiaire', 250.01, 400.00, 116.00, 3],
            ['Tranche 4 - Elevé', 400.01, null, 132.00, 4]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO tranches_tarifaires (nom, seuil_min, seuil_max, prix_kwh, ordre, actif) 
            VALUES (?, ?, ?, ?, ?, true)
        ");
        
        foreach ($tranches as $tranche) {
            $stmt->execute($tranche);
        }
        
        echo "✓ " . count($tranches) . " tranches tarifaires créées\n";
    }
    
    private function seedClients(): void
    {
        echo "Seeding des clients de test...\n";
        
        // Supprimer les données existantes
        $this->pdo->exec("DELETE FROM clients");
        $this->pdo->exec("ALTER SEQUENCE clients_id_seq RESTART WITH 1");
        
        $clients = [
            ['DIOP', 'Amadou', 'amadou.diop@example.com', '771234567'],
            ['FALL', 'Fatou', 'fatou.fall@example.com', '775678912'],
            ['NDIAYE', 'Moussa', 'moussa.ndiaye@example.com', '779876543'],
            ['SECK', 'Aïcha', 'aicha.seck@example.com', '773456789'],
            ['SARR', 'Ousmane', 'ousmane.sarr@example.com', '776543210'],
            ['KANE', 'Mariama', 'mariama.kane@example.com', '778901234'],
            ['BA', 'Ibrahima', 'ibrahima.ba@example.com', '772345678'],
            ['GUEYE', 'Awa', 'awa.gueye@example.com', '774567890']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO clients (nom, prenom, email, telephone, actif) 
            VALUES (?, ?, ?, ?, true)
        ");
        
        foreach ($clients as $client) {
            $stmt->execute($client);
        }
        
        echo "✓ " . count($clients) . " clients de test créés\n";
    }
    
    private function seedCompteurs(): void
    {
        echo "Seeding des compteurs...\n";
        
        // Supprimer les données existantes
        $this->pdo->exec("DELETE FROM compteurs");
        $this->pdo->exec("ALTER SEQUENCE compteurs_id_seq RESTART WITH 1");
        
        $compteurs = [
            ['123456789', 1, 'Rue 10 x Rue 15, Medina', 'Medina', 'Dakar'],
            ['987654321', 2, 'Avenue Blaise Diagne, HLM', 'HLM', 'Dakar'],
            ['456789123', 3, 'Route de Rufisque, Keur Massar', 'Keur Massar', 'Pikine'],
            ['789123456', 4, 'Cité Millionnaire, Grand Yoff', 'Grand Yoff', 'Dakar'],
            ['321654987', 5, 'Quartier Résidentiel, Almadies', 'Almadies', 'Dakar'],
            ['654987321', 6, 'Zone de Captage, Thiaroye', 'Thiaroye', 'Pikine'],
            ['147258369', 7, 'Boulevard du Centenaire, Plateau', 'Plateau', 'Dakar'],
            ['963852741', 8, 'Cité Mixta, Guédiawaye', 'Guédiawaye', 'Guédiawaye']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO compteurs (numero, client_id, adresse, quartier, ville, actif, type_compteur) 
            VALUES (?, ?, ?, ?, ?, true, 'prepaye')
        ");
        
        foreach ($compteurs as $compteur) {
            $stmt->execute($compteur);
        }
        
        echo "✓ " . count($compteurs) . " compteurs créés\n";
    }
    
    private function seedConsommationsMensuelles(): void
    {
        echo "Seeding des consommations mensuelles...\n";
        
        // Créer des consommations pour le mois actuel et le mois précédent
        $moisActuel = (int)date('n');
        $anneeActuelle = (int)date('Y');
        $moisPrecedent = $moisActuel === 1 ? 12 : $moisActuel - 1;
        $anneePrecedente = $moisActuel === 1 ? $anneeActuelle - 1 : $anneeActuelle;
        
        $consommations = [
            // Mois précédent - quelques clients avec consommation
            [1, $moisPrecedent, $anneePrecedente, 15000.00, 150.5, 3],
            [2, $moisPrecedent, $anneePrecedente, 8500.00, 85.2, 2],
            [3, $moisPrecedent, $anneePrecedente, 22000.00, 195.8, 4],
            [4, $moisPrecedent, $anneePrecedente, 5000.00, 50.0, 1],
            
            // Mois actuel - début de consommation pour test des tranches
            [1, $moisActuel, $anneeActuelle, 7500.00, 75.0, 1],
            [2, $moisActuel, $anneeActuelle, 12000.00, 110.5, 2],
            [5, $moisActuel, $anneeActuelle, 3000.00, 30.0, 1]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO consommations_mensuelles (client_id, mois, annee, total_achats, kwh_total, nombre_achats) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (client_id, mois, annee) DO NOTHING
        ");
        
        foreach ($consommations as $consommation) {
            $stmt->execute($consommation);
        }
        
        echo "✓ " . count($consommations) . " enregistrements de consommation créés\n";
    }
    
    public function getTestData(): array
    {
        return [
            'clients_test' => [
                'amadou_diop' => ['email' => 'amadou.diop@example.com', 'compteur' => '123456789'],
                'fatou_fall' => ['email' => 'fatou.fall@example.com', 'compteur' => '987654321'],
                'moussa_ndiaye' => ['email' => 'moussa.ndiaye@example.com', 'compteur' => '456789123']
            ],
            'compteurs_test' => ['123456789', '987654321', '456789123', '789123456'],
            'montants_test' => [1000, 2500, 5000, 10000, 15000, 25000, 50000]
        ];
    }
}
