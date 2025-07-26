<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\TrancheToarifaire;
use Src\Repository\Interface\TrancheToarifaireRepositoryInterface;
use PDO;
use PDOException;

class TrancheToarifaireRepository implements TrancheToarifaireRepositoryInterface
{
    private PDO $pdo;
    
    public function __construct(Database $database)
    {
        $this->pdo = $database->getConnection();
    }

    public function findById(int $id): ?TrancheToarifaire
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tranches_tarifaires 
                WHERE id = ? AND actif = true
            ");
            
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ? TrancheToarifaire::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de la tranche : " . $e->getMessage()
            );
        }
    }

    public function findAllActives(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM tranches_tarifaires 
                WHERE actif = true 
                ORDER BY ordre ASC
            ");
            
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => TrancheToarifaire::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la récupération des tranches : " . $e->getMessage()
            );
        }
    }

    public function findForConsommation(float $consommationKwh): ?TrancheToarifaire
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tranches_tarifaires 
                WHERE actif = true 
                AND seuil_min <= ?
                AND (seuil_max IS NULL OR ? <= seuil_max)
                ORDER BY ordre ASC
                LIMIT 1
            ");
            
            $stmt->execute([$consommationKwh, $consommationKwh]);
            $result = $stmt->fetch();
            
            return $result ? TrancheToarifaire::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de tranche pour consommation : " . $e->getMessage()
            );
        }
    }

    public function save(TrancheToarifaire $tranche): TrancheToarifaire
    {
        try {
            if ($tranche->getId() === null) {
                return $this->insert($tranche);
            } else {
                return $this->update($tranche);
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde de la tranche : " . $e->getMessage()
            );
        }
    }

    public function delete(int $id): bool
    {
        try {
            // Soft delete - marquer comme inactif
            $stmt = $this->pdo->prepare("
                UPDATE tranches_tarifaires 
                SET actif = false, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la suppression de la tranche : " . $e->getMessage()
            );
        }
    }

    public function getOrderedTranches(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    id,
                    nom,
                    seuil_min,
                    seuil_max,
                    prix_kwh,
                    ordre,
                    CASE 
                        WHEN seuil_max IS NULL THEN CONCAT('À partir de ', seuil_min, ' kWh')
                        ELSE CONCAT('De ', seuil_min, ' à ', seuil_max, ' kWh')
                    END as description_seuil,
                    CONCAT(prix_kwh, ' FCFA/kWh') as prix_formatted
                FROM tranches_tarifaires 
                WHERE actif = true 
                ORDER BY ordre ASC
            ");
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la récupération des tranches ordonnées : " . $e->getMessage()
            );
        }
    }

    public function calculateOptimalTranche(float $montantDisponible): array
    {
        try {
            $tranches = $this->findAllActives();
            $result = [
                'kwh_total' => 0.0,
                'montant_utilise' => 0.0,
                'tranche_finale' => null,
                'details' => []
            ];
            
            $montantRestant = $montantDisponible;
            $kwhCumule = 0.0;
            
            foreach ($tranches as $tranche) {
                if ($montantRestant <= 0) break;
                
                $seuilMax = $tranche->getSeuilMax();
                $kwhDansLaTranche = $seuilMax ? ($seuilMax - $tranche->getSeuilMin()) : INF;
                
                // Limite par le seuil de la tranche
                if ($seuilMax && ($kwhCumule + $kwhDansLaTranche) > $seuilMax) {
                    $kwhDansLaTranche = $seuilMax - $kwhCumule;
                }
                
                // Limite par le montant disponible
                $kwhPossible = $montantRestant / $tranche->getPrixKwh();
                $kwhAUtiliser = min($kwhDansLaTranche, $kwhPossible);
                
                if ($kwhAUtiliser > 0) {
                    $montantTranche = $kwhAUtiliser * $tranche->getPrixKwh();
                    
                    $result['details'][] = [
                        'tranche' => $tranche->toArray(),
                        'kwh_utilises' => round($kwhAUtiliser, 3),
                        'montant_utilise' => round($montantTranche, 2)
                    ];
                    
                    $result['kwh_total'] += $kwhAUtiliser;
                    $result['montant_utilise'] += $montantTranche;
                    $result['tranche_finale'] = $tranche;
                    
                    $montantRestant -= $montantTranche;
                    $kwhCumule += $kwhAUtiliser;
                }
            }
            
            $result['kwh_total'] = round($result['kwh_total'], 3);
            $result['montant_utilise'] = round($result['montant_utilise'], 2);
            
            return $result;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul de la tranche optimale : " . $e->getMessage()
            );
        }
    }

    private function insert(TrancheToarifaire $tranche): TrancheToarifaire
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tranches_tarifaires (nom, seuil_min, seuil_max, prix_kwh, ordre, actif, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING *
        ");
        
        $stmt->execute([
            $tranche->getNom(),
            $tranche->getSeuilMin(),
            $tranche->getSeuilMax(),
            $tranche->getPrixKwh(),
            $tranche->getOrdre(),
            $tranche->isActif()
        ]);
        
        $result = $stmt->fetch();
        return TrancheToarifaire::toObject($result);
    }

    private function update(TrancheToarifaire $tranche): TrancheToarifaire
    {
        $stmt = $this->pdo->prepare("
            UPDATE tranches_tarifaires 
            SET nom = ?, seuil_min = ?, seuil_max = ?, prix_kwh = ?, ordre = ?, actif = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            RETURNING *
        ");
        
        $stmt->execute([
            $tranche->getNom(),
            $tranche->getSeuilMin(),
            $tranche->getSeuilMax(),
            $tranche->getPrixKwh(),
            $tranche->getOrdre(),
            $tranche->isActif(),
            $tranche->getId()
        ]);
        
        $result = $stmt->fetch();
        return TrancheToarifaire::toObject($result);
    }
}
