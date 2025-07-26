<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class Compteur extends AbstractEntity
{
    private ?int $id;
    private string $numero;
    private int $clientId;
    private ?string $adresse;
    private ?string $quartier;
    private string $ville;
    private bool $actif;
    private string $typeCompteur;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        string $numero,
        int $clientId,
        ?string $adresse = null,
        ?string $quartier = null,
        string $ville = 'Dakar',
        bool $actif = true,
        string $typeCompteur = 'prepaye',
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->validateNumero($numero);
        
        $this->id = $id;
        $this->numero = $numero;
        $this->clientId = $clientId;
        $this->adresse = $adresse;
        $this->quartier = $quartier;
        $this->ville = $ville;
        $this->actif = $actif;
        $this->typeCompteur = $typeCompteur;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function toObject(array $data): static
    {
        return new self(
            numero: $data['numero'],
            clientId: (int)$data['client_id'],
            adresse: $data['adresse'] ?? null,
            quartier: $data['quartier'] ?? null,
            ville: $data['ville'] ?? 'Dakar',
            actif: $data['actif'] ?? true,
            typeCompteur: $data['type_compteur'] ?? 'prepaye',
            id: $data['id'] ?? null,
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'client_id' => $this->clientId,
            'adresse' => $this->adresse,
            'quartier' => $this->quartier,
            'ville' => $this->ville,
            'actif' => $this->actif,
            'type_compteur' => $this->typeCompteur,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function getTypeCompteur(): string
    {
        return $this->typeCompteur;
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
    public function getAdresseComplete(): string
    {
        $parts = array_filter([$this->adresse, $this->quartier, $this->ville]);
        return implode(', ', $parts);
    }

    public function activate(): self
    {
        return new self(
            $this->numero,
            $this->clientId,
            $this->adresse,
            $this->quartier,
            $this->ville,
            true,
            $this->typeCompteur,
            $this->id,
            $this->createdAt,
            new \DateTime()
        );
    }

    public function deactivate(): self
    {
        return new self(
            $this->numero,
            $this->clientId,
            $this->adresse,
            $this->quartier,
            $this->ville,
            false,
            $this->typeCompteur,
            $this->id,
            $this->createdAt,
            new \DateTime()
        );
    }

    // Validation privée
    private function validateNumero(string $numero): void
    {
        if (!preg_match('/^[0-9]{8,12}$/', $numero)) {
            throw new \InvalidArgumentException(
                'Le numéro de compteur doit contenir entre 8 et 12 chiffres'
            );
        }
    }
}
