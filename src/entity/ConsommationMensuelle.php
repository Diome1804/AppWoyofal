<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class ConsommationMensuelle extends AbstractEntity
{
    private ?int $id;
    private int $clientId;
    private int $mois;
    private int $annee;
    private float $totalAchats;
    private float $kwhTotal;
    private int $nombreAchats;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        int $clientId,
        int $mois,
        int $annee,
        float $totalAchats = 0.0,
        float $kwhTotal = 0.0,
        int $nombreAchats = 0,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->validateMois($mois);
        $this->validateAnnee($annee);
        
        $this->id = $id;
        $this->clientId = $clientId;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->totalAchats = $totalAchats;
        $this->kwhTotal = $kwhTotal;
        $this->nombreAchats = $nombreAchats;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function toObject(array $data): static
    {
        return new self(
            clientId: (int)$data['client_id'],
            mois: (int)$data['mois'],
            annee: (int)$data['annee'],
            totalAchats: (float)($data['total_achats'] ?? 0.0),
            kwhTotal: (float)($data['kwh_total'] ?? 0.0),
            nombreAchats: (int)($data['nombre_achats'] ?? 0),
            id: $data['id'] ?? null,
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'mois' => $this->mois,
            'annee' => $this->annee,
            'total_achats' => $this->totalAchats,
            'kwh_total' => $this->kwhTotal,
            'nombre_achats' => $this->nombreAchats,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getMois(): int
    {
        return $this->mois;
    }

    public function getAnnee(): int
    {
        return $this->annee;
    }

    public function getTotalAchats(): float
    {
        return $this->totalAchats;
    }

    public function getKwhTotal(): float
    {
        return $this->kwhTotal;
    }

    public function getNombreAchats(): int
    {
        return $this->nombreAchats;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // Business methods
    public function addAchat(float $montant, float $kwh): self
    {
        return new self(
            $this->clientId,
            $this->mois,
            $this->annee,
            $this->totalAchats + $montant,
            $this->kwhTotal + $kwh,
            $this->nombreAchats + 1,
            $this->id,
            $this->createdAt,
            new \DateTime()
        );
    }

    public function getMoyenneParAchat(): float
    {
        if ($this->nombreAchats === 0) {
            return 0.0;
        }
        
        return round($this->totalAchats / $this->nombreAchats, 2);
    }

    public function getPrixMoyenKwh(): float
    {
        if ($this->kwhTotal === 0) {
            return 0.0;
        }
        
        return round($this->totalAchats / $this->kwhTotal, 2);
    }

    public function getPeriodeFormatted(): string
    {
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $moisNoms[$this->mois] . ' ' . $this->annee;
    }

    public function getTotalAchatsFormatted(): string
    {
        return number_format($this->totalAchats, 0, ',', ' ') . ' FCFA';
    }

    public function getKwhTotalFormatted(): string
    {
        return number_format($this->kwhTotal, 2, ',', ' ') . ' kWh';
    }

    public function isCurrentMonth(): bool
    {
        $now = new \DateTime();
        return $this->mois === (int)$now->format('n') && 
               $this->annee === (int)$now->format('Y');
    }

    public static function getCurrentPeriod(): array
    {
        $now = new \DateTime();
        return [
            'mois' => (int)$now->format('n'),
            'annee' => (int)$now->format('Y')
        ];
    }

    // Validation privée
    private function validateMois(int $mois): void
    {
        if ($mois < 1 || $mois > 12) {
            throw new \InvalidArgumentException('Le mois doit être entre 1 et 12');
        }
    }

    private function validateAnnee(int $annee): void
    {
        if ($annee < 2024) {
            throw new \InvalidArgumentException('L\'année doit être supérieure ou égale à 2024');
        }
    }
}
