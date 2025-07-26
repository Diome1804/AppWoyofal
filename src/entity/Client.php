<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class Client extends AbstractEntity
{
    private ?int $id;
    private string $nom;
    private string $prenom;
    private ?string $email;
    private ?string $telephone;
    private bool $actif;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        string $nom,
        string $prenom,
        ?string $email = null,
        ?string $telephone = null,
        bool $actif = true,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->actif = $actif;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function toObject(array $data): static
    {
        return new self(
            nom: $data['nom'],
            prenom: $data['prenom'],
            email: $data['email'] ?? null,
            telephone: $data['telephone'] ?? null,
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
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
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

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
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
    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function activate(): self
    {
        return new self(
            $this->nom,
            $this->prenom,
            $this->email,
            $this->telephone,
            true,
            $this->id,
            $this->createdAt,
            new \DateTime()
        );
    }

    public function deactivate(): self
    {
        return new self(
            $this->nom,
            $this->prenom,
            $this->email,
            $this->telephone,
            false,
            $this->id,
            $this->createdAt,
            new \DateTime()
        );
    }
}
