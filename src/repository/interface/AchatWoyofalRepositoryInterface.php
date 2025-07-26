<?php

namespace Src\Repository\Interface;

use Src\Entity\AchatWoyofal;

interface AchatWoyofalRepositoryInterface
{
    public function findById(int $id): ?AchatWoyofal;
    
    public function findByReference(string $reference): ?AchatWoyofal;
    
    public function findByCodeRecharge(string $codeRecharge): ?AchatWoyofal;
    
    public function findByCompteur(string $numeroCompteur): array;
    
    public function findByClient(int $clientId): array;
    
    public function save(AchatWoyofal $achat): AchatWoyofal;
    
    public function generateReference(): string;
    
    public function generateCodeRecharge(): string;
    
    public function getAchatsStats(?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): array;
}
