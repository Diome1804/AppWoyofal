<?php

namespace Src\Service\Interface;

use Src\Entity\AchatWoyofal;

interface AchatWoyofalServiceInterface
{
    /**
     * Traite un achat Woyofal complet
     */
    public function processAchat(string $numeroCompteur, float $montant, array $requestInfo = []): array;
    
    /**
     * Valide les données d'achat
     */
    public function validateAchatData(string $numeroCompteur, float $montant): array;
    
    /**
     * Vérifie l'existence et l'état du compteur
     */
    public function validateCompteur(string $numeroCompteur): array;
    
    /**
     * Crée une transaction d'achat
     */
    public function createAchat(
        string $numeroCompteur,
        int $clientId,
        float $montant,
        float $kwhAchetes,
        float $prixUnitaire,
        int $trancheId,
        array $requestInfo = []
    ): AchatWoyofal;
    
    /**
     * Génère une référence unique pour l'achat
     */
    public function generateReference(): string;
    
    /**
     * Génère un code de recharge unique
     */
    public function generateCodeRecharge(): string;
    
    /**
     * Formate la réponse de succès selon le cahier des charges
     */
    public function formatSuccessResponse(AchatWoyofal $achat, array $clientInfo, array $trancheInfo): array;
    
    /**
     * Formate la réponse d'erreur selon le cahier des charges
     */
    public function formatErrorResponse(string $message, int $code = 400): array;
    
    /**
     * Simule un achat sans le persister
     */
    public function simulateAchat(string $numeroCompteur, float $montant): array;
}
