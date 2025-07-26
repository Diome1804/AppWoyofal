<?php

namespace Src\Service\Interface;

use Src\Entity\TrancheToarifaire;
use Src\Entity\ConsommationMensuelle;

interface TrancheCalculatorServiceInterface
{
    /**
     * Calcule la tranche tarifaire applicable pour un client selon sa consommation mensuelle
     */
    public function calculateTrancheForClient(int $clientId, float $montant): array;
    
    /**
     * Détermine la tranche selon la consommation actuelle
     */
    public function determineTrancheForConsommation(float $consommationKwh): ?TrancheToarifaire;
    
    /**
     * Calcule le nombre de kWh obtenus pour un montant donné
     */
    public function calculateKwhForAmount(float $montant, int $clientId): array;
    
    /**
     * Obtient ou crée la consommation mensuelle actuelle d'un client
     */
    public function getCurrentConsommation(int $clientId): ConsommationMensuelle;
    
    /**
     * Vérifie si c'est un nouveau mois (reset des tranches)
     */
    public function isNewMonth(ConsommationMensuelle $consommation): bool;
    
    /**
     * Reset la consommation mensuelle pour un nouveau mois
     */
    public function resetMonthlyConsommation(int $clientId): ConsommationMensuelle;
    
    /**
     * Simule un calcul d'achat sans le persister
     */
    public function simulateAchat(int $clientId, float $montant): array;
}
