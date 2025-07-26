<?php

namespace Src\Service\Interface;

interface SchedulerServiceInterface
{
    /**
     * Reset automatique des tranches au début de chaque mois
     */
    public function resetMonthlyTranches(): array;
    
    /**
     * Vérifie si c'est le moment d'exécuter le reset mensuel
     */
    public function shouldRunMonthlyReset(): bool;
    
    /**
     * Nettoie les anciens logs selon la planification
     */
    public function cleanupOldLogs(): array;
    
    /**
     * Vérifie si une expression cron doit être exécutée maintenant
     */
    public function shouldRunCronExpression(string $cronExpression): bool;
    
    /**
     * Obtient la prochaine exécution d'une tâche cron
     */
    public function getNextRunTime(string $cronExpression): \DateTime;
    
    /**
     * Exécute toutes les tâches planifiées nécessaires
     */
    public function runScheduledTasks(): array;
    
    /**
     * Obtient le statut de la dernière exécution des tâches
     */
    public function getLastExecutionStatus(): array;
}
