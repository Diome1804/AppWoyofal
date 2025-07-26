<?php

namespace Src\Repository\Interface;

use Src\Entity\ConsommationMensuelle;

interface ConsommationMensuelleRepositoryInterface
{
    public function findByClientAndPeriod(int $clientId, int $mois, int $annee): ?ConsommationMensuelle;
    
    public function findCurrentByClient(int $clientId): ?ConsommationMensuelle;
    
    public function findByClient(int $clientId): array;
    
    public function save(ConsommationMensuelle $consommation): ConsommationMensuelle;
    
    public function updateOrCreate(int $clientId, int $mois, int $annee, float $montant, float $kwh): ConsommationMensuelle;
    
    public function getMonthlyStats(int $mois, int $annee): array;
}
