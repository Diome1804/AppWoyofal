<?php

namespace Src\Service;

use Src\Entity\LogAchat;
use Src\Repository\Interface\LogAchatRepositoryInterface;
use Src\Service\Interface\LoggerServiceInterface;

class LoggerService implements LoggerServiceInterface
{
    public function __construct(
        private readonly LogAchatRepositoryInterface $logRepository
    ) {}

    public function logSuccess(
        string $numeroCompteur,
        float $montant,
        array $responseData,
        array $requestInfo = [],
        ?int $executionTimeMs = null
    ): LogAchat {
        $log = LogAchat::createSuccess(
            $numeroCompteur,
            $montant,
            $responseData,
            $requestInfo['ip_address'] ?? null,
            $requestInfo['user_agent'] ?? null,
            $executionTimeMs
        );

        return $this->logRepository->save($log);
    }

    public function logError(
        string $statut,
        string $errorMessage,
        ?string $numeroCompteur = null,
        ?float $montant = null,
        array $requestInfo = [],
        ?int $executionTimeMs = null
    ): LogAchat {
        $log = LogAchat::createError(
            $statut,
            $errorMessage,
            $numeroCompteur,
            $montant,
            $requestInfo['ip_address'] ?? null,
            $requestInfo['user_agent'] ?? null,
            $executionTimeMs
        );

        return $this->logRepository->save($log);
    }

    public function logValidationError(
        string $errorMessage,
        array $requestData = [],
        array $requestInfo = []
    ): LogAchat {
        $log = new LogAchat(
            statut: 'validation_error',
            numeroCompteur: $requestData['compteur'] ?? null,
            montant: isset($requestData['montant']) ? (float)$requestData['montant'] : null,
            ipAddress: $requestInfo['ip_address'] ?? null,
            userAgent: $requestInfo['user_agent'] ?? null,
            method: $requestInfo['method'] ?? 'POST',
            endpoint: $requestInfo['endpoint'] ?? '/api/woyofal/achat',
            requestData: $requestData,
            errorMessage: $errorMessage
        );

        return $this->logRepository->save($log);
    }

    public function logCompteurNotFound(
        string $numeroCompteur,
        array $requestInfo = []
    ): LogAchat {
        $log = new LogAchat(
            statut: 'compteur_not_found',
            numeroCompteur: $numeroCompteur,
            ipAddress: $requestInfo['ip_address'] ?? null,
            userAgent: $requestInfo['user_agent'] ?? null,
            method: $requestInfo['method'] ?? 'POST',
            endpoint: $requestInfo['endpoint'] ?? '/api/woyofal/achat',
            requestData: ['compteur' => $numeroCompteur],
            errorMessage: 'Numéro de compteur non trouvé'
        );

        return $this->logRepository->save($log);
    }

    public function extractRequestInfo(): array
    {
        return [
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'timestamp' => new \DateTime()
        ];
    }

    public function getStatsForPeriod(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        try {
            $logs = $this->logRepository->findByPeriod($dateDebut, $dateFin);
            
            $stats = [
                'periode' => [
                    'debut' => $dateDebut->format('Y-m-d H:i:s'),
                    'fin' => $dateFin->format('Y-m-d H:i:s')
                ],
                'total_requests' => count($logs),
                'success_count' => 0,
                'error_count' => 0,
                'statuts' => [],
                'execution_times' => [],
                'unique_compteurs' => [],
                'unique_ips' => [],
                'total_montant' => 0.0
            ];

            foreach ($logs as $log) {
                // Compteurs par statut
                $statut = $log->getStatut();
                $stats['statuts'][$statut] = ($stats['statuts'][$statut] ?? 0) + 1;
                
                if ($log->isSuccess()) {
                    $stats['success_count']++;
                    if ($log->getMontant()) {
                        $stats['total_montant'] += $log->getMontant();
                    }
                } else {
                    $stats['error_count']++;
                }

                // Temps d'exécution
                if ($log->getExecutionTimeMs()) {
                    $stats['execution_times'][] = $log->getExecutionTimeMs();
                }

                // Compteurs uniques
                if ($log->getNumeroCompteur()) {
                    $stats['unique_compteurs'][$log->getNumeroCompteur()] = true;
                }

                // IPs uniques
                if ($log->getIpAddress()) {
                    $stats['unique_ips'][$log->getIpAddress()] = true;
                }
            }

            // Calculs finaux
            $stats['success_rate'] = $stats['total_requests'] > 0 ? 
                round(($stats['success_count'] / $stats['total_requests']) * 100, 2) : 0;
            
            $stats['unique_compteurs_count'] = count($stats['unique_compteurs']);
            $stats['unique_ips_count'] = count($stats['unique_ips']);
            
            if (!empty($stats['execution_times'])) {
                $stats['avg_execution_time_ms'] = round(array_sum($stats['execution_times']) / count($stats['execution_times']), 2);
                $stats['max_execution_time_ms'] = max($stats['execution_times']);
                $stats['min_execution_time_ms'] = min($stats['execution_times']);
            } else {
                $stats['avg_execution_time_ms'] = 0;
                $stats['max_execution_time_ms'] = 0;
                $stats['min_execution_time_ms'] = 0;
            }

            // Nettoyer les tableaux internes
            unset($stats['execution_times'], $stats['unique_compteurs'], $stats['unique_ips']);

            return $stats;

        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques : " . $e->getMessage()
            );
        }
    }

    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        return $this->logRepository->cleanupOldLogs($daysToKeep);
    }

    /**
     * Log d'une opération système (scheduler, maintenance, etc.)
     */
    public function logSystemOperation(
        string $operation,
        array $result,
        array $context = [],
        ?int $executionTimeMs = null
    ): LogAchat {
        $log = new LogAchat(
            statut: $result['success'] ? 'success' : 'error',
            numeroCompteur: $operation,
            ipAddress: 'SYSTEM',
            userAgent: 'AppWoyofal/Scheduler',
            method: 'SYSTEM',
            endpoint: '/system/' . strtolower($operation),
            requestData: $context,
            responseData: $result,
            errorMessage: $result['success'] ? null : ($result['error'] ?? 'Erreur système'),
            executionTimeMs: $executionTimeMs
        );

        return $this->logRepository->save($log);
    }

    /**
     * Obtient un résumé des erreurs récentes
     */
    public function getRecentErrorsSummary(int $hours = 24): array
    {
        $dateDebut = new \DateTime();
        $dateDebut->modify("-{$hours} hours");
        $dateFin = new \DateTime();

        $errors = $this->logRepository->findByPeriod($dateDebut, $dateFin);
        $errorLogs = array_filter($errors, fn($log) => !$log->isSuccess());

        $summary = [
            'period_hours' => $hours,
            'total_errors' => count($errorLogs),
            'error_types' => [],
            'most_common_errors' => [],
            'affected_compteurs' => []
        ];

        $errorMessages = [];
        foreach ($errorLogs as $log) {
            $statut = $log->getStatut();
            $summary['error_types'][$statut] = ($summary['error_types'][$statut] ?? 0) + 1;

            if ($log->getErrorMessage()) {
                $errorMessages[$log->getErrorMessage()] = ($errorMessages[$log->getErrorMessage()] ?? 0) + 1;
            }

            if ($log->getNumeroCompteur() && $log->getNumeroCompteur() !== 'SYSTEM') {
                $summary['affected_compteurs'][$log->getNumeroCompteur()] = true;
            }
        }

        // Top 5 des messages d'erreur
        arsort($errorMessages);
        $summary['most_common_errors'] = array_slice($errorMessages, 0, 5, true);
        $summary['affected_compteurs_count'] = count($summary['affected_compteurs']);
        unset($summary['affected_compteurs']);

        return $summary;
    }

    /**
     * Obtient l'adresse IP réelle du client
     */
    private function getClientIp(): ?string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Détermine si une IP est suspecte (trop de requêtes)
     */
    public function isIpSuspicious(string $ipAddress, int $maxRequestsPerHour = 100): bool
    {
        $oneHourAgo = new \DateTime();
        $oneHourAgo->modify('-1 hour');
        $now = new \DateTime();

        $logs = $this->logRepository->findByPeriod($oneHourAgo, $now);
        $requestsFromIp = array_filter($logs, fn($log) => $log->getIpAddress() === $ipAddress);

        return count($requestsFromIp) > $maxRequestsPerHour;
    }
}
