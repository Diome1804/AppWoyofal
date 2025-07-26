<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class AchatWoyofal extends AbstractEntity
{
    private ?int $id;
    private string $reference;
    private string $codeRecharge;
    private string $numeroCompteur;
    private int $clientId;
    private float $montant;
    private float $kwhAchetes;
    private float $prixUnitaire;
    private int $trancheId;
    private \DateTime $dateAchat;
    private string $statut;
    private ?string $ipAddress;
    private ?string $userAgent;

    public function __construct(
        string $reference,
        string $codeRecharge,
        string $numeroCompteur,
        int $clientId,
        float $montant,
        float $kwhAchetes,
        float $prixUnitaire,
        int $trancheId,
        string $statut = 'success',
        ?\DateTime $dateAchat = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $id = null
    ) {
        $this->validateMontant($montant);
        $this->validateKwh($kwhAchetes);
        $this->validateStatut($statut);
        
        $this->id = $id;
        $this->reference = $reference;
        $this->codeRecharge = $codeRecharge;
        $this->numeroCompteur = $numeroCompteur;
        $this->clientId = $clientId;
        $this->montant = $montant;
        $this->kwhAchetes = $kwhAchetes;
        $this->prixUnitaire = $prixUnitaire;
        $this->trancheId = $trancheId;
        $this->dateAchat = $dateAchat ?? new \DateTime();
        $this->statut = $statut;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    public static function toObject(array $data): static
    {
        return new self(
            reference: $data['reference'],
            codeRecharge: $data['code_recharge'],
            numeroCompteur: $data['numero_compteur'],
            clientId: (int)$data['client_id'],
            montant: (float)$data['montant'],
            kwhAchetes: (float)$data['kwh_achetes'],
            prixUnitaire: (float)$data['prix_unitaire'],
            trancheId: (int)$data['tranche_id'],
            statut: $data['statut'] ?? 'success',
            dateAchat: isset($data['date_achat']) ? new \DateTime($data['date_achat']) : null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            id: $data['id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'code_recharge' => $this->codeRecharge,
            'numero_compteur' => $this->numeroCompteur,
            'client_id' => $this->clientId,
            'montant' => $this->montant,
            'kwh_achetes' => $this->kwhAchetes,
            'prix_unitaire' => $this->prixUnitaire,
            'tranche_id' => $this->trancheId,
            'date_achat' => $this->dateAchat->format('Y-m-d H:i:s'),
            'statut' => $this->statut,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCodeRecharge(): string
    {
        return $this->codeRecharge;
    }

    public function getNumeroCompteur(): string
    {
        return $this->numeroCompteur;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function getKwhAchetes(): float
    {
        return $this->kwhAchetes;
    }

    public function getPrixUnitaire(): float
    {
        return $this->prixUnitaire;
    }

    public function getTrancheId(): int
    {
        return $this->trancheId;
    }

    public function getDateAchat(): \DateTime
    {
        return $this->dateAchat;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    // Business methods
    public function isSuccess(): bool
    {
        return $this->statut === 'success';
    }

    public function getMontantFormatted(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public function getKwhFormatted(): string
    {
        return number_format($this->kwhAchetes, 2, ',', ' ') . ' kWh';
    }

    public function getPrixUnitaireFormatted(): string
    {
        return number_format($this->prixUnitaire, 0, ',', ' ') . ' FCFA/kWh';
    }

    public function getDateAchatFormatted(): string
    {
        return $this->dateAchat->format('d/m/Y H:i:s');
    }

    public function toReceipt(): array
    {
        return [
            'reference' => $this->reference,
            'code_recharge' => $this->codeRecharge,
            'numero_compteur' => $this->numeroCompteur,
            'montant' => $this->getMontantFormatted(),
            'kwh_achetes' => $this->getKwhFormatted(),
            'prix_unitaire' => $this->getPrixUnitaireFormatted(),
            'date_achat' => $this->getDateAchatFormatted(),
            'statut' => $this->statut
        ];
    }

    // Validation privée
    private function validateMontant(float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }
    }

    private function validateKwh(float $kwh): void
    {
        if ($kwh <= 0) {
            throw new \InvalidArgumentException('Le nombre de kWh doit être positif');
        }
    }

    private function validateStatut(string $statut): void
    {
        $statutsValides = ['success', 'pending', 'failed', 'cancelled'];
        if (!in_array($statut, $statutsValides)) {
            throw new \InvalidArgumentException('Statut invalide');
        }
    }
}
