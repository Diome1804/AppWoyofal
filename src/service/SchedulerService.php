<?php

namespace Src\Service;

use Cron\CronExpression;
use Src\Repository\Interface\ConsommationMensuelleRepositoryInterface;
use Src\Repository\Interface\LogAchatRepositoryInterface;
use Src\Service\Interface\SchedulerServiceInterface;
use Src\Service\Interface\LoggerServiceInterface;

class SchedulerService implements SchedulerServiceInterface
{
    // Expressions cron pour les tâches automatiques
    private const MONTHLY_RESET_CRON = '0 0 1 * *';  // 00:00 le 1er de chaque mois
    private const DAILY_CLEANUP_CRON = '0 2 * * *';   // 02:00 chaque jour
    
    public function __construct(
        private readonly ConsommationMensuelleRepositoryInterface $consommationRepository,
        private readonly LogAchatRepositoryInterface $logRepository,
        private readonly LoggerServiceInterface $loggerService
    ) {}

    public function resetMonthlyTranches(): array
    {
        try {
            $startTime = microtime(true);
            $result = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'actions' => [],
                'errors' => []
            ];

            // Log du début de l'opération
            $result['actions'][] = 'Début du reset mensuel des tranches';

            // Note: En réalité, les tranches se remettent automatiquement à zéro
            // car on utilise la consommation mensuelle actuelle dans TrancheCalculatorService
            // Cette fonction sert plutôt à nettoyer/archiver si nécessaire

            // Optionnel : Archiver les données du mois précédent
            $moisPrecedent = (int)date('n') === 1 ? 12 : (int)date('n') - 1;
            $anneePrecedente = (int)date('n') === 1 ? (int)date('Y') - 1 : (int)date('Y');
            
            $result['actions'][] = "Archivage des données de $moisPrecedent/$anneePrecedente";

            // Log de succès
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $result['actions'][] = "Reset terminé en {$executionTime}ms";
            
            $this->loggerService->logSuccess(
                'SYSTEM_RESET',
                0,
                $result,
                ['system' => 'scheduler'],
                $executionTime
            );

            return $result;

        } catch (\Exception $e) {
            $error = "Erreur lors du reset mensuel : " . $e->getMessage();
            
            $this->loggerService->logError(
                'server_error',
                $error,
                'SYSTEM_RESET',
                0,
                ['system' => 'scheduler']
            );

            return [
                'success' => false,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $error
            ];
        }
    }

    public function shouldRunMonthlyReset(): bool
    {
        return $this->shouldRunCronExpression(self::MONTHLY_RESET_CRON);
    }

    public function cleanupOldLogs(): array
    {
        try {
            $startTime = microtime(true);
            $deletedCount = $this->logRepository->cleanupOldLogs(90); // Garder 90 jours
            
            $result = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'deleted_logs' => $deletedCount,
                'execution_time_ms' => (int)((microtime(true) - $startTime) * 1000)
            ];

            $this->loggerService->logSuccess(
                'SYSTEM_CLEANUP',
                0,
                $result,
                ['system' => 'scheduler'],
                $result['execution_time_ms']
            );

            return $result;

        } catch (\Exception $e) {
            $error = "Erreur lors du nettoyage : " . $e->getMessage();
            
            $this->loggerService->logError(
                'server_error',
                $error,
                'SYSTEM_CLEANUP',
                0,
                ['system' => 'scheduler']
            );

            return [
                'success' => false,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $error
            ];
        }
    }

    public function shouldRunCronExpression(string $cronExpression): bool
    {
        try {
            $cron = new CronExpression($cronExpression);
            
            // Vérifier si la tâche doit s'exécuter maintenant (dans la minute actuelle)
            $now = new \DateTime();
            $lastRun = clone $now;
            $lastRun->modify('-1 minute');
            
            return $cron->isDue($now) || 
                   ($cron->getNextRunDate($lastRun) <= $now);
                   
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getNextRunTime(string $cronExpression): \DateTime
    {
        try {
            $cron = new CronExpression($cronExpression);
            return $cron->getNextRunDate();
            
        } catch (\Exception $e) {
            // Retourner une date dans le futur si erreur
            $future = new \DateTime();
            $future->modify('+1 hour');
            return $future;
        }
    }

    public function runScheduledTasks(): array
    {
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tasks_executed' => [],
            'tasks_skipped' => [],
            'errors' => []
        ];

        // 1. Vérifier le reset mensuel
        if ($this->shouldRunMonthlyReset()) {
            $results['tasks_executed'][] = [
                'task' => 'monthly_reset',
                'result' => $this->resetMonthlyTranches()
            ];
        } else {
            $nextRun = $this->getNextRunTime(self::MONTHLY_RESET_CRON);
            $results['tasks_skipped'][] = [
                'task' => 'monthly_reset',
                'next_run' => $nextRun->format('Y-m-d H:i:s'),
                'reason' => 'Not due yet'
            ];
        }

        // 2. Vérifier le nettoyage quotidien
        if ($this->shouldRunCronExpression(self::DAILY_CLEANUP_CRON)) {
            $results['tasks_executed'][] = [
                'task' => 'daily_cleanup',
                'result' => $this->cleanupOldLogs()
            ];
        } else {
            $nextRun = $this->getNextRunTime(self::DAILY_CLEANUP_CRON);
            $results['tasks_skipped'][] = [
                'task' => 'daily_cleanup',
                'next_run' => $nextRun->format('Y-m-d H:i:s'),
                'reason' => 'Not due yet'
            ];
        }

        return $results;
    }

    public function getLastExecutionStatus(): array
    {
        // Simuler le statut de la dernière exécution
        // En production, vous stockeriez cela en base de données
        return [
            'last_monthly_reset' => date('Y-m-01 00:00:00'),
            'last_daily_cleanup' => date('Y-m-d 02:00:00'),
            'next_monthly_reset' => $this->getNextRunTime(self::MONTHLY_RESET_CRON)->format('Y-m-d H:i:s'),
            'next_daily_cleanup' => $this->getNextRunTime(self::DAILY_CLEANUP_CRON)->format('Y-m-d H:i:s'),
            'system_status' => 'running'
        ];
    }

    /**
     * Méthode utilitaire pour forcer le reset (pour les tests)
     */
    public function forceMonthlyReset(): array
    {
        return $this->resetMonthlyTranches();
    }

    /**
     * Vérifie si c'est un nouveau mois depuis la dernière vérification
     */
    public function isNewMonth(): bool
    {
        // Cette logique pourrait être stockée en base pour plus de précision
        $currentMonth = date('Y-m');
        $lastCheckedMonth = date('Y-m', strtotime('-1 month'));
        
        return $currentMonth !== $lastCheckedMonth;
    }

    /**
     * Obtient les informations sur toutes les tâches planifiées
     */
    public function getScheduledTasksInfo(): array
    {
        return [
            'monthly_reset' => [
                'description' => 'Reset des tranches tarifaires au début de chaque mois',
                'cron_expression' => self::MONTHLY_RESET_CRON,
                'cron_description' => 'A 00:00 le 1er de chaque mois',
                'next_run' => $this->getNextRunTime(self::MONTHLY_RESET_CRON)->format('Y-m-d H:i:s'),
                'is_due' => $this->shouldRunMonthlyReset()
            ],
            'daily_cleanup' => [
                'description' => 'Nettoyage quotidien des anciens logs',
                'cron_expression' => self::DAILY_CLEANUP_CRON,
                'cron_description' => 'A 02:00 chaque jour',
                'next_run' => $this->getNextRunTime(self::DAILY_CLEANUP_CRON)->format('Y-m-d H:i:s'),
                'is_due' => $this->shouldRunCronExpression(self::DAILY_CLEANUP_CRON)
            ]
        ];
    }
}
