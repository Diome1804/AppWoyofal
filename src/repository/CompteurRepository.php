<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\Compteur;
use Src\Repository\Interface\CompteurRepositoryInterface;
use PDO;
use PDOException;

class CompteurRepository implements CompteurRepositoryInterface
{
    private PDO $pdo;
    
    public function __construct(Database $database)
    {
        $this->pdo = $database->getConnection();
    }

    public function findById(int $id): ?Compteur
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM compteurs 
                WHERE id = ? AND actif = true
            ");
            
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ? Compteur::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche du compteur : " . $e->getMessage()
            );
        }
    }

    public function findByNumero(string $numero): ?Compteur
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM compteurs 
                WHERE numero = ? AND actif = true
            ");
            
            $stmt->execute([$numero]);
            $result = $stmt->fetch();
            
            return $result ? Compteur::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par numéro : " . $e->getMessage()
            );
        }
    }

    public function findByClientId(int $clientId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM compteurs 
                WHERE client_id = ? AND actif = true 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$clientId]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => Compteur::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par client : " . $e->getMessage()
            );
        }
    }

    public function findActifs(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT c.*, cl.nom, cl.prenom 
                FROM compteurs c
                LEFT JOIN clients cl ON c.client_id = cl.id
                WHERE c.actif = true AND cl.actif = true
                ORDER BY c.created_at DESC
            ");
            
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => Compteur::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la récupération des compteurs actifs : " . $e->getMessage()
            );
        }
    }

    public function save(Compteur $compteur): Compteur
    {
        try {
            if ($compteur->getId() === null) {
                return $this->insert($compteur);
            } else {
                return $this->update($compteur);
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde du compteur : " . $e->getMessage()
            );
        }
    }

    public function delete(int $id): bool
    {
        try {
            // Soft delete - marquer comme inactif
            $stmt = $this->pdo->prepare("
                UPDATE compteurs 
                SET actif = false, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la suppression du compteur : " . $e->getMessage()
            );
        }
    }

    public function activate(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE compteurs 
                SET actif = true, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de l'activation du compteur : " . $e->getMessage()
            );
        }
    }

    public function deactivate(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE compteurs 
                SET actif = false, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la désactivation du compteur : " . $e->getMessage()
            );
        }
    }

    public function existsByNumero(string $numero): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM compteurs 
                WHERE numero = ?
            ");
            
            $stmt->execute([$numero]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la vérification d'existence : " . $e->getMessage()
            );
        }
    }

    public function findCompteurWithClient(string $numero): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.*,
                    cl.nom as client_nom,
                    cl.prenom as client_prenom,
                    cl.email as client_email,
                    cl.telephone as client_telephone
                FROM compteurs c
                INNER JOIN clients cl ON c.client_id = cl.id
                WHERE c.numero = ? AND c.actif = true AND cl.actif = true
            ");
            
            $stmt->execute([$numero]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return null;
            }
            
            return [
                'compteur' => Compteur::toObject($result),
                'client' => [
                    'id' => $result['client_id'],
                    'nom' => $result['client_nom'],
                    'prenom' => $result['client_prenom'],
                    'email' => $result['client_email'],
                    'telephone' => $result['client_telephone']
                ]
            ];
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche compteur avec client : " . $e->getMessage()
            );
        }
    }

    private function insert(Compteur $compteur): Compteur
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO compteurs (numero, client_id, adresse, quartier, ville, actif, type_compteur, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING *
        ");
        
        $stmt->execute([
            $compteur->getNumero(),
            $compteur->getClientId(),
            $compteur->getAdresse(),
            $compteur->getQuartier(),
            $compteur->getVille(),
            $compteur->isActif(),
            $compteur->getTypeCompteur()
        ]);
        
        $result = $stmt->fetch();
        return Compteur::toObject($result);
    }

    private function update(Compteur $compteur): Compteur
    {
        $stmt = $this->pdo->prepare("
            UPDATE compteurs 
            SET client_id = ?, adresse = ?, quartier = ?, ville = ?, actif = ?, type_compteur = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            RETURNING *
        ");
        
        $stmt->execute([
            $compteur->getClientId(),
            $compteur->getAdresse(),
            $compteur->getQuartier(),
            $compteur->getVille(),
            $compteur->isActif(),
            $compteur->getTypeCompteur(),
            $compteur->getId()
        ]);
        
        $result = $stmt->fetch();
        return Compteur::toObject($result);
    }
}
