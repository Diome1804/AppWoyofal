<?php

namespace Src\Repository\Interface;

use Src\Entity\TrancheToarifaire;

interface TrancheToarifaireRepositoryInterface
{
    public function findById(int $id): ?TrancheToarifaire;
    
    public function findAllActives(): array;
    
    public function findForConsommation(float $consommationKwh): ?TrancheToarifaire;
    
    public function save(TrancheToarifaire $tranche): TrancheToarifaire;
    
    public function delete(int $id): bool;
    
    public function getOrderedTranches(): array;
}
