<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\Client;
use Src\Repository\Interface\ClientRepositoryInterface;
use PDO;
use PDOException;

class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly Database $database
    ) {}

    private function getPdo(): PDO
    {
        return $this->database->getConnection();
    }
    
    public function findAll(): array
    {
        try {
            $stmt = $this->getPdo()->query("
                SELECT * FROM clients 
                WHERE actif = true 
                ORDER BY nom, prenom
            ");
            
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => Client::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la récupération des clients : " . $e->getMessage()
            );
        }
    }

    public function findById(int $id): ?Client
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM clients 
                WHERE id = ? AND actif = true
            ");
            
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ? Client::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche du client : " . $e->getMessage()
            );
        }
    }

    public function findByEmail(string $email): ?Client
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM clients 
                WHERE email = ? AND actif = true
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            return $result ? Client::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par email : " . $e->getMessage()
            );
        }
    }

    public function findByTelephone(string $telephone): ?Client
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM clients 
                WHERE telephone = ? AND actif = true
            ");
            
            $stmt->execute([$telephone]);
            $result = $stmt->fetch();
            
            return $result ? Client::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par téléphone : " . $e->getMessage()
            );
        }
    }

    public function save(Client $client): Client
    {
        try {
            if ($client->getId() === null) {
                return $this->insert($client);
            } else {
                return $this->update($client);
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde du client : " . $e->getMessage()
            );
        }
    }

    public function delete(int $id): bool
    {
        try {
            // Soft delete - marquer comme inactif
            $stmt = $this->getPdo()->prepare("
                UPDATE clients 
                SET actif = false, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la suppression du client : " . $e->getMessage()
            );
        }
    }

    public function searchByName(string $searchTerm): array
    {
        try {
            $searchTerm = "%{$searchTerm}%";
            
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM clients 
                WHERE (nom ILIKE ? OR prenom ILIKE ?) 
                AND actif = true 
                ORDER BY nom, prenom
                LIMIT 50
            ");
            
            $stmt->execute([$searchTerm, $searchTerm]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => Client::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de clients : " . $e->getMessage()
            );
        }
    }

    public function getClientsStats(): array
    {
        try {
            $stmt = $this->getPdo()->query("
                SELECT 
                    COUNT(*) as total_clients,
                    COUNT(*) FILTER (WHERE actif = true) as clients_actifs,
                    COUNT(*) FILTER (WHERE actif = false) as clients_inactifs,
                    COUNT(*) FILTER (WHERE created_at >= CURRENT_DATE - INTERVAL '30 days') as nouveaux_30j
                FROM clients
            ");
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques : " . $e->getMessage()
            );
        }
    }

    private function insert(Client $client): Client
    {
        $stmt = $this->getPdo()->prepare("
            INSERT INTO clients (nom, prenom, email, telephone, actif, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING *
        ");
        
        $stmt->execute([
            $client->getNom(),
            $client->getPrenom(),
            $client->getEmail(),
            $client->getTelephone(),
            $client->isActif()
        ]);
        
        $result = $stmt->fetch();
        return Client::toObject($result);
    }

    private function update(Client $client): Client
    {
        $stmt = $this->getPdo()->prepare("
            UPDATE clients 
            SET nom = ?, prenom = ?, email = ?, telephone = ?, actif = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            RETURNING *
        ");
        
        $stmt->execute([
            $client->getNom(),
            $client->getPrenom(),
            $client->getEmail(),
            $client->getTelephone(),
            $client->isActif(),
            $client->getId()
        ]);
        
        $result = $stmt->fetch();
        return Client::toObject($result);
    }
}
