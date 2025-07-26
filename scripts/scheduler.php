<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/bootstrap.php';

use App\Core\App;

try {
    echo "=== Exécution du Scheduler AppWoyofal ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Initialiser le scheduler via votre système d'injection
    // Note: En production, vous configureriez cela dans services.yml
    $database = new \App\Core\Database();
    
    $consommationRepo = new \Src\Repository\ConsommationMensuelleRepository($database);
    $logRepo = new \Src\Repository\LogAchatRepository($database);
    $loggerService = new \Src\Service\LoggerService($logRepo);
    
    $scheduler = new \Src\Service\SchedulerService(
        $consommationRepo,
        $logRepo,
        $loggerService
    );
    
    // Exécuter toutes les tâches planifiées
    $results = $scheduler->runScheduledTasks();
    
    echo "Résultats de l'exécution :\n";
    echo "- Tâches exécutées: " . count($results['tasks_executed']) . "\n";
    echo "- Tâches ignorées: " . count($results['tasks_skipped']) . "\n";
    echo "- Erreurs: " . count($results['errors']) . "\n\n";
    
    // Afficher les détails
    if (!empty($results['tasks_executed'])) {
        echo "=== Tâches exécutées ===\n";
        foreach ($results['tasks_executed'] as $task) {
            echo "- {$task['task']}: " . 
                 ($task['result']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        }
        echo "\n";
    }
    
    if (!empty($results['tasks_skipped'])) {
        echo "=== Tâches ignorées ===\n";
        foreach ($results['tasks_skipped'] as $task) {
            echo "- {$task['task']}: {$task['reason']} (prochaine: {$task['next_run']})\n";
        }
        echo "\n";
    }
    
    // Afficher le statut du système
    echo "=== Statut du système ===\n";
    $status = $scheduler->getLastExecutionStatus();
    echo "- Dernier reset mensuel: {$status['last_monthly_reset']}\n";
    echo "- Prochain reset mensuel: {$status['next_monthly_reset']}\n";
    echo "- Dernier nettoyage: {$status['last_daily_cleanup']}\n";
    echo "- Prochain nettoyage: {$status['next_daily_cleanup']}\n";
    echo "- Statut: {$status['system_status']}\n\n";
    
    echo "✅ Scheduler exécuté avec succès!\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de l'exécution du scheduler : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}
