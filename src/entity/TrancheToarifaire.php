<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class TrancheToarifaire extends AbstractEntity
{
    private ?int $id;
    private string $nom;
    private float $seuilMin;
    private ?float $seuilMax;
    private float $prixKwh;
    private int $ordre;
    private bool $actif;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        string $nom,
        float $seuilMin,
        float $prixKwh,
        int $ordre,
        ?float $seuilMax = null,
        bool $actif = true,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->validateSeuils($seuilMin, $seuilMax);
        $this->validatePrix($prixKwh);
        
        $this->id = $id;
        $this->nom = $nom;
        $this->seuilMin = $seuilMin;
        $this->seuilMax = $seuilMax;
        $this->prixKwh = $prixKwh;
        $this->ordre = $ordre;
        $this->actif = $actif;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function toObject(array $data): static
    {
        return new self(
            nom: $data['nom'],
            seuilMin: (float)$data['seuil_min'],
            prixKwh: (float)$data['prix_kwh'],
            ordre: (int)$data['ordre'],
            seuilMax: isset($data['seuil_max']) ? (float)$data['seuil_max'] : null,
            actif: $data['actif'] ?? true,
            id: $data['id'] ?? null,
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'seuil_min' => $this->seuilMin,
            'seuil_max' => $this->seuilMax,
            'prix_kwh' => $this->prixKwh,
            'ordre' => $this->ordre,
            'actif' => $this->actif,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getSeuilMin(): float
    {
        return $this->seuilMin;
    }

    public function getSeuilMax(): ?float
    {
        return $this->seuilMax;
    }

    public function getPrixKwh(): float
    {
        return $this->prixKwh;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function isActif(): bool
    {
        return $this->actif;
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
    public function isApplicableFor(float $consommationKwh): bool
    {
        if (!$this->actif) {
            return false;
        }
        
        $dansSeuilMin = $consommationKwh >= $this->seuilMin;
        $dansSeuilMax = $this->seuilMax === null || $consommationKwh <= $this->seuilMax;
        
        return $dansSeuilMin && $dansSeuilMax;
    }

    public function calculateKwhForAmount(float $montant): float
    {
        return round($montant / $this->prixKwh, 3);
    }

    public function calculateAmountForKwh(float $kwh): float
    {
        return round($kwh * $this->prixKwh, 2);
    }

    public function getSeuilDescription(): string
    {
        if ($this->seuilMax === null) {
            return "À partir de {$this->seuilMin} kWh";
        }
        
        return "De {$this->seuilMin} à {$this->seuilMax} kWh";
    }

    public function getPrixFormatted(): string
    {
        return number_format($this->prixKwh, 0, ',', ' ') . ' FCFA/kWh';
    }

    // Validation privée
    private function validateSeuils(float $seuilMin, ?float $seuilMax): void
    {
        if ($seuilMin < 0) {
            throw new \InvalidArgumentException('Le seuil minimum ne peut pas être négatif');
        }
        
        if ($seuilMax !== null && $seuilMax <= $seuilMin) {
            throw new \InvalidArgumentException('Le seuil maximum doit être supérieur au seuil minimum');
        }
    }

    private function validatePrix(float $prix): void
    {
        if ($prix <= 0) {
            throw new \InvalidArgumentException('Le prix par kWh doit être positif');
        }
    }
}
