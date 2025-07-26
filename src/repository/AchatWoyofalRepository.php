<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\AchatWoyofal;
use Src\Repository\Interface\AchatWoyofalRepositoryInterface;
use PDO;
use PDOException;

class AchatWoyofalRepository implements AchatWoyofalRepositoryInterface
{
    public function __construct(
        private readonly Database $database
    ) {}

    private function getPdo(): PDO
    {
        return $this->database->getConnection();
    }

    public function findById(int $id): ?AchatWoyofal
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM achats_woyofal 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ? AchatWoyofal::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de l'achat : " . $e->getMessage()
            );
        }
    }

    public function findByReference(string $reference): ?AchatWoyofal
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM achats_woyofal 
                WHERE reference = ?
            ");
            
            $stmt->execute([$reference]);
            $result = $stmt->fetch();
            
            return $result ? AchatWoyofal::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par référence : " . $e->getMessage()
            );
        }
    }

    public function findByCodeRecharge(string $codeRecharge): ?AchatWoyofal
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM achats_woyofal 
                WHERE code_recharge = ?
            ");
            
            $stmt->execute([$codeRecharge]);
            $result = $stmt->fetch();
            
            return $result ? AchatWoyofal::toObject($result) : null;
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par code de recharge : " . $e->getMessage()
            );
        }
    }

    public function findByCompteur(string $numeroCompteur): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM achats_woyofal 
                WHERE numero_compteur = ? 
                ORDER BY date_achat DESC
                LIMIT 50
            ");
            
            $stmt->execute([$numeroCompteur]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => AchatWoyofal::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par compteur : " . $e->getMessage()
            );
        }
    }

    public function findByClient(int $clientId): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM achats_woyofal 
                WHERE client_id = ? 
                ORDER BY date_achat DESC
                LIMIT 100
            ");
            
            $stmt->execute([$clientId]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => AchatWoyofal::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche par client : " . $e->getMessage()
            );
        }
    }

    public function save(AchatWoyofal $achat): AchatWoyofal
    {
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO achats_woyofal (
                    reference, code_recharge, numero_compteur, client_id, 
                    montant, kwh_achetes, prix_unitaire, tranche_id, 
                    statut, ip_address, user_agent, date_achat
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING *
            ");
            
            $stmt->execute([
                $achat->getReference(),
                $achat->getCodeRecharge(),
                $achat->getNumeroCompteur(),
                $achat->getClientId(),
                $achat->getMontant(),
                $achat->getKwhAchetes(),
                $achat->getPrixUnitaire(),
                $achat->getTrancheId(),
                $achat->getStatut(),
                $achat->getIpAddress(),
                $achat->getUserAgent(),
                $achat->getDateAchat()->format('Y-m-d H:i:s')
            ]);
            
            $result = $stmt->fetch();
            return AchatWoyofal::toObject($result);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde de l'achat : " . $e->getMessage()
            );
        }
    }

    public function generateReference(): string
    {
        try {
            $stmt = $this->getPdo()->query("SELECT generate_reference()");
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            // Fallback si la fonction PostgreSQL n'existe pas
            return $this->generateReferenceFallback();
        }
    }

    public function generateCodeRecharge(): string
    {
        try {
            $stmt = $this->getPdo()->query("SELECT generate_code_recharge()");
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            // Fallback si la fonction PostgreSQL n'existe pas
            return $this->generateCodeRechargeFallback();
        }
    }

    public function getAchatsStats(?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): array
    {
        try {
            $whereClause = "WHERE statut = 'success'";
            $params = [];
            
            if ($dateDebut) {
                $whereClause .= " AND date_achat >= ?";
                $params[] = $dateDebut->format('Y-m-d H:i:s');
            }
            
            if ($dateFin) {
                $whereClause .= " AND date_achat <= ?";
                $params[] = $dateFin->format('Y-m-d H:i:s');
            }
            
            $stmt = $this->getPdo()->prepare("
                SELECT 
                    COUNT(*) as total_achats,
                    SUM(montant) as montant_total,
                    SUM(kwh_achetes) as kwh_total,
                    AVG(montant) as montant_moyen,
                    AVG(kwh_achetes) as kwh_moyen,
                    AVG(prix_unitaire) as prix_moyen,
                    COUNT(DISTINCT numero_compteur) as compteurs_uniques,
                    COUNT(DISTINCT client_id) as clients_uniques
                FROM achats_woyofal 
                $whereClause
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            // Formatage des résultats
            return [
                'total_achats' => (int)$result['total_achats'],
                'montant_total' => round((float)$result['montant_total'], 2),
                'kwh_total' => round((float)$result['kwh_total'], 3),
                'montant_moyen' => round((float)$result['montant_moyen'], 2),
                'kwh_moyen' => round((float)$result['kwh_moyen'], 3),
                'prix_moyen' => round((float)$result['prix_moyen'], 2),
                'compteurs_uniques' => (int)$result['compteurs_uniques'],
                'clients_uniques' => (int)$result['clients_uniques']
            ];
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques : " . $e->getMessage()
            );
        }
    }

    private function generateReferenceFallback(): string
    {
        do {
            $reference = 'WYF' . date('ymd') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $existing = $this->findByReference($reference);
        } while ($existing !== null);
        
        return $reference;
    }

    private function generateCodeRechargeFallback(): string
    {
        do {
            // Générer un code de 20 chiffres
            $code = '';
            for ($i = 0; $i < 20; $i++) {
                $code .= mt_rand(0, 9);
            }
            $existing = $this->findByCodeRecharge($code);
        } while ($existing !== null);
        
        return $code;
    }
}
