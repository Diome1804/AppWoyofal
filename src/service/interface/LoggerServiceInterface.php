<?php

namespace Src\Service\Interface;

use Src\Entity\LogAchat;

interface LoggerServiceInterface
{
    /**
     * Log un achat réussi
     */
    public function logSuccess(
        string $numeroCompteur,
        float $montant,
        array $responseData,
        array $requestInfo = [],
        ?int $executionTimeMs = null
    ): LogAchat;
    
    /**
     * Log une erreur d'achat
     */
    public function logError(
        string $statut,
        string $errorMessage,
        ?string $numeroCompteur = null,
        ?float $montant = null,
        array $requestInfo = [],
        ?int $executionTimeMs = null
    ): LogAchat;
    
    /**
     * Log une erreur de validation
     */
    public function logValidationError(
        string $errorMessage,
        array $requestData = [],
        array $requestInfo = []
    ): LogAchat;
    
    /**
     * Log une erreur de compteur non trouvé
     */
    public function logCompteurNotFound(
        string $numeroCompteur,
        array $requestInfo = []
    ): LogAchat;
    
    /**
     * Extrait les informations de la requête HTTP
     */
    public function extractRequestInfo(): array;
    
    /**
     * Obtient les statistiques de logs pour une période
     */
    public function getStatsForPeriod(\DateTime $dateDebut, \DateTime $dateFin): array;
    
    /**
     * Nettoie les anciens logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int;
}
