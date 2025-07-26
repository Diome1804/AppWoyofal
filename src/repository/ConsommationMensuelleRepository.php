<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\ConsommationMensuelle;
use Src\Repository\Interface\ConsommationMensuelleRepositoryInterface;
use PDO;
use PDOException;

class ConsommationMensuelleRepository implements ConsommationMensuelleRepositoryInterface
{
    public function __construct(
        private readonly Database $database
    ) {}

    private function getPdo(): PDO
    {
        return $this->database->getConnection();
    }

    public function findByClientAndPeriod(int $clientId, int $mois, int $annee): ?ConsommationMensuelle
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM consommations_mensuelles 
                WHERE client_id = ? AND mois = ? AND annee = ?
            ");
            
            $stmt->execute([$clientId, $mois, $annee]);
            $result = $stmt->fetch();
            
            return $result ? ConsommationMensuelle::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de consommation : " . $e->getMessage()
            );
        }
    }

    public function findCurrentByClient(int $clientId): ?ConsommationMensuelle
    {
        $periode = ConsommationMensuelle::getCurrentPeriod();
        return $this->findByClientAndPeriod($clientId, $periode['mois'], $periode['annee']);
    }

    public function findByClient(int $clientId): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM consommations_mensuelles 
                WHERE client_id = ? 
                ORDER BY annee DESC, mois DESC
                LIMIT 24
            ");
            
            $stmt->execute([$clientId]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => ConsommationMensuelle::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche des consommations : " . $e->getMessage()
            );
        }
    }

    public function save(ConsommationMensuelle $consommation): ConsommationMensuelle
    {
        try {
            if ($consommation->getId() === null) {
                return $this->insert($consommation);
            } else {
                return $this->update($consommation);
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde de la consommation : " . $e->getMessage()
            );
        }
    }

    public function updateOrCreate(int $clientId, int $mois, int $annee, float $montant, float $kwh): ConsommationMensuelle
    {
        try {
            $existing = $this->findByClientAndPeriod($clientId, $mois, $annee);
            
            if ($existing) {
                // Mettre à jour la consommation existante
                $updated = $existing->addAchat($montant, $kwh);
                return $this->save($updated);
            } else {
                // Créer une nouvelle consommation
                $nouvelle = new ConsommationMensuelle(
                    clientId: $clientId,
                    mois: $mois,
                    annee: $annee,
                    totalAchats: $montant,
                    kwhTotal: $kwh,
                    nombreAchats: 1
                );
                return $this->save($nouvelle);
            }
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la mise à jour/création de consommation : " . $e->getMessage()
            );
        }
    }

    public function getMonthlyStats(int $mois, int $annee): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT 
                    COUNT(*) as total_clients,
                    SUM(total_achats) as montant_total,
                    SUM(kwh_total) as kwh_total,
                    SUM(nombre_achats) as achats_total,
                    AVG(total_achats) as montant_moyen,
                    AVG(kwh_total) as kwh_moyen,
                    AVG(nombre_achats) as achats_moyen
                FROM consommations_mensuelles 
                WHERE mois = ? AND annee = ?
            ");
            
            $stmt->execute([$mois, $annee]);
            $result = $stmt->fetch();
            
            return [
                'periode' => sprintf('%02d/%d', $mois, $annee),
                'total_clients' => (int)$result['total_clients'],
                'montant_total' => round((float)$result['montant_total'], 2),
                'kwh_total' => round((float)$result['kwh_total'], 3),
                'achats_total' => (int)$result['achats_total'],
                'montant_moyen' => round((float)$result['montant_moyen'], 2),
                'kwh_moyen' => round((float)$result['kwh_moyen'], 3),
                'achats_moyen' => round((float)$result['achats_moyen'], 1)
            ];
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques mensuelles : " . $e->getMessage()
            );
        }
    }

    public function getTopConsommateurs(int $mois, int $annee, int $limit = 10): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT 
                    cm.*,
                    c.nom,
                    c.prenom,
                    c.email
                FROM consommations_mensuelles cm
                INNER JOIN clients c ON cm.client_id = c.id
                WHERE cm.mois = ? AND cm.annee = ?
                ORDER BY cm.kwh_total DESC
                LIMIT ?
            ");
            
            $stmt->execute([$mois, $annee, $limit]);
            $results = $stmt->fetchAll();
            
            return array_map(function($row) {
                $consommation = ConsommationMensuelle::toObject($row);
                return [
                    'consommation' => $consommation,
                    'client' => [
                        'nom' => $row['nom'],
                        'prenom' => $row['prenom'],
                        'email' => $row['email']
                    ]
                ];
            }, $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche des top consommateurs : " . $e->getMessage()
            );
        }
    }

    private function insert(ConsommationMensuelle $consommation): ConsommationMensuelle
    {
        $stmt = $this->getPdo()->prepare("
            INSERT INTO consommations_mensuelles (
                client_id, mois, annee, total_achats, kwh_total, nombre_achats,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING *
        ");
        
        $stmt->execute([
            $consommation->getClientId(),
            $consommation->getMois(),
            $consommation->getAnnee(),
            $consommation->getTotalAchats(),
            $consommation->getKwhTotal(),
            $consommation->getNombreAchats()
        ]);
        
        $result = $stmt->fetch();
        return ConsommationMensuelle::toObject($result);
    }

    private function update(ConsommationMensuelle $consommation): ConsommationMensuelle
    {
        $stmt = $this->getPdo()->prepare("
            UPDATE consommations_mensuelles 
            SET total_achats = ?, kwh_total = ?, nombre_achats = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            RETURNING *
        ");
        
        $stmt->execute([
            $consommation->getTotalAchats(),
            $consommation->getKwhTotal(),
            $consommation->getNombreAchats(),
            $consommation->getId()
        ]);
        
        $result = $stmt->fetch();
        return ConsommationMensuelle::toObject($result);
    }
}
