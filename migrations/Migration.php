<?php

namespace App\Migrations;

use App\Core\Database;
use PDO;

class Migration
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function run(): void
    {
        $this->createMigrationsTable();
        
        $migrations = [
            '001_create_clients_table' => [$this, 'createClientsTable'],
            '002_create_compteurs_table' => [$this, 'createCompteursTable'],
            '003_create_tranches_tarifaires_table' => [$this, 'createTranchesToarifairesTable'],
            '004_create_consommations_mensuelles_table' => [$this, 'createConsommationsMensuellesTable'],
            '005_create_achats_woyofal_table' => [$this, 'createAchatsWoyofalTable'],
            '006_create_logs_achats_table' => [$this, 'createLogsAchatsTable']
        ];
        
        foreach ($migrations as $name => $callback) {
            if (!$this->migrationExists($name)) {
                echo "Exécution de la migration: $name\n";
                call_user_func($callback);
                $this->recordMigration($name);
                echo "Migration $name terminée avec succès\n";
            } else {
                echo "Migration $name déjà exécutée\n";
            }
        }
    }
    
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }
    
    private function migrationExists(string $migration): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function recordMigration(string $migration): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }
    
    private function createClientsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS clients (
                id SERIAL PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE,
                telephone VARCHAR(20),
                actif BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX IF NOT EXISTS idx_clients_email ON clients(email);
            CREATE INDEX IF NOT EXISTS idx_clients_telephone ON clients(telephone);
            CREATE INDEX IF NOT EXISTS idx_clients_actif ON clients(actif);
        ";
        $this->pdo->exec($sql);
    }
    
    private function createCompteursTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS compteurs (
                id SERIAL PRIMARY KEY,
                numero VARCHAR(50) UNIQUE NOT NULL,
                client_id INTEGER NOT NULL,
                adresse TEXT,
                quartier VARCHAR(100),
                ville VARCHAR(100) DEFAULT 'Dakar',
                actif BOOLEAN DEFAULT TRUE,
                type_compteur VARCHAR(20) DEFAULT 'prepaye',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_compteurs_client 
                    FOREIGN KEY (client_id) 
                    REFERENCES clients(id) 
                    ON DELETE CASCADE
            );
            
            CREATE INDEX IF NOT EXISTS idx_compteurs_numero ON compteurs(numero);
            CREATE INDEX IF NOT EXISTS idx_compteurs_client_id ON compteurs(client_id);
            CREATE INDEX IF NOT EXISTS idx_compteurs_actif ON compteurs(actif);
        ";
        $this->pdo->exec($sql);
    }
    
    private function createTranchesToarifairesTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS tranches_tarifaires (
                id SERIAL PRIMARY KEY,
                nom VARCHAR(50) NOT NULL,
                seuil_min DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                seuil_max DECIMAL(10, 2),
                prix_kwh DECIMAL(10, 2) NOT NULL,
                ordre INTEGER NOT NULL,
                actif BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT check_prix_positif CHECK (prix_kwh > 0),
                CONSTRAINT check_seuil_coherent CHECK (seuil_max IS NULL OR seuil_max > seuil_min),
                CONSTRAINT unique_ordre UNIQUE (ordre)
            );
            
            CREATE INDEX IF NOT EXISTS idx_tranches_seuils ON tranches_tarifaires(seuil_min, seuil_max);
            CREATE INDEX IF NOT EXISTS idx_tranches_ordre ON tranches_tarifaires(ordre);
        ";
        $this->pdo->exec($sql);
    }
    
    private function createConsommationsMensuellesTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS consommations_mensuelles (
                id SERIAL PRIMARY KEY,
                client_id INTEGER NOT NULL,
                mois INTEGER NOT NULL CHECK (mois BETWEEN 1 AND 12),
                annee INTEGER NOT NULL CHECK (annee >= 2024),
                total_achats DECIMAL(15, 2) DEFAULT 0.00,
                kwh_total DECIMAL(12, 3) DEFAULT 0.000,
                nombre_achats INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_consommations_client 
                    FOREIGN KEY (client_id) 
                    REFERENCES clients(id) 
                    ON DELETE CASCADE,
                    
                CONSTRAINT unique_client_mois_annee 
                    UNIQUE (client_id, mois, annee)
            );
            
            CREATE INDEX IF NOT EXISTS idx_consommations_client_id ON consommations_mensuelles(client_id);
            CREATE INDEX IF NOT EXISTS idx_consommations_periode ON consommations_mensuelles(client_id, mois, annee);
        ";
        $this->pdo->exec($sql);
    }
    
    private function createAchatsWoyofalTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS achats_woyofal (
                id SERIAL PRIMARY KEY,
                reference VARCHAR(20) UNIQUE NOT NULL,
                code_recharge VARCHAR(20) UNIQUE NOT NULL,
                numero_compteur VARCHAR(50) NOT NULL,
                client_id INTEGER NOT NULL,
                montant DECIMAL(15, 2) NOT NULL,
                kwh_achetes DECIMAL(12, 3) NOT NULL,
                prix_unitaire DECIMAL(10, 2) NOT NULL,
                tranche_id INTEGER NOT NULL,
                date_achat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                statut VARCHAR(20) DEFAULT 'success',
                ip_address INET,
                user_agent TEXT,
                
                CONSTRAINT fk_achats_client 
                    FOREIGN KEY (client_id) 
                    REFERENCES clients(id) 
                    ON DELETE CASCADE,
                    
                CONSTRAINT fk_achats_tranche 
                    FOREIGN KEY (tranche_id) 
                    REFERENCES tranches_tarifaires(id),
                    
                CONSTRAINT check_montant_positif CHECK (montant > 0),
                CONSTRAINT check_kwh_positif CHECK (kwh_achetes > 0),
                CONSTRAINT check_statut_valide CHECK (statut IN ('success', 'pending', 'failed', 'cancelled'))
            );
            
            CREATE INDEX IF NOT EXISTS idx_achats_reference ON achats_woyofal(reference);
            CREATE INDEX IF NOT EXISTS idx_achats_code_recharge ON achats_woyofal(code_recharge);
            CREATE INDEX IF NOT EXISTS idx_achats_compteur ON achats_woyofal(numero_compteur);
            CREATE INDEX IF NOT EXISTS idx_achats_client_id ON achats_woyofal(client_id);
            CREATE INDEX IF NOT EXISTS idx_achats_date ON achats_woyofal(date_achat);
        ";
        $this->pdo->exec($sql);
    }
    
    private function createLogsAchatsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS logs_achats (
                id SERIAL PRIMARY KEY,
                numero_compteur VARCHAR(50),
                montant DECIMAL(15, 2),
                statut VARCHAR(20) NOT NULL,
                ip_address INET,
                user_agent TEXT,
                method VARCHAR(10) DEFAULT 'POST',
                endpoint VARCHAR(100),
                request_data JSONB,
                response_data JSONB,
                error_message TEXT,
                execution_time_ms INTEGER,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT check_statut_log_valide 
                CHECK (statut IN ('success', 'error', 'validation_error', 'insufficient_funds', 'compteur_not_found', 'server_error'))
            );
            
            CREATE INDEX IF NOT EXISTS idx_logs_timestamp ON logs_achats(timestamp);
            CREATE INDEX IF NOT EXISTS idx_logs_statut ON logs_achats(statut);
            CREATE INDEX IF NOT EXISTS idx_logs_compteur ON logs_achats(numero_compteur);
            CREATE INDEX IF NOT EXISTS idx_logs_request_data ON logs_achats USING GIN (request_data);
        ";
        $this->pdo->exec($sql);
    }
}
