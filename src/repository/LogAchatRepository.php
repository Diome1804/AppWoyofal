<?php

namespace Src\Repository;

use App\Core\Database;
use Src\Entity\LogAchat;
use Src\Repository\Interface\LogAchatRepositoryInterface;
use PDO;
use PDOException;

class LogAchatRepository implements LogAchatRepositoryInterface
{
    public function __construct(
        private readonly Database $database
    ) {}

    private function getPdo(): PDO
    {
        return $this->database->getConnection();
    }

    public function save(LogAchat $log): LogAchat
    {
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO logs_achats (
                    numero_compteur, montant, statut, ip_address, user_agent,
                    method, endpoint, request_data, response_data, error_message,
                    execution_time_ms, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING *
            ");
            
            $stmt->execute([
                $log->getNumeroCompteur(),
                $log->getMontant(),
                $log->getStatut(),
                $log->getIpAddress(),
                $log->getUserAgent(),
                $log->getMethod(),
                $log->getEndpoint(),
                $log->getRequestData() ? json_encode($log->getRequestData()) : null,
                $log->getResponseData() ? json_encode($log->getResponseData()) : null,
                $log->getErrorMessage(),
                $log->getExecutionTimeMs(),
                $log->getTimestamp()->format('Y-m-d H:i:s')
            ]);
            
            $result = $stmt->fetch();
            return LogAchat::toObject($result);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la sauvegarde du log : " . $e->getMessage()
            );
        }
    }

    public function findByCompteur(string $numeroCompteur, int $limit = 50): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM logs_achats 
                WHERE numero_compteur = ? 
                ORDER BY timestamp DESC
                LIMIT ?
            ");
            
            $stmt->execute([$numeroCompteur, $limit]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => LogAchat::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de logs par compteur : " . $e->getMessage()
            );
        }
    }

    public function findByStatut(string $statut, int $limit = 100): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM logs_achats 
                WHERE statut = ? 
                ORDER BY timestamp DESC
                LIMIT ?
            ");
            
            $stmt->execute([$statut, $limit]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => LogAchat::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de logs par statut : " . $e->getMessage()
            );
        }
    }

    public function findByPeriod(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM logs_achats 
                WHERE timestamp BETWEEN ? AND ?
                ORDER BY timestamp DESC
                LIMIT 1000
            ");
            
            $stmt->execute([
                $dateDebut->format('Y-m-d H:i:s'),
                $dateFin->format('Y-m-d H:i:s')
            ]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => LogAchat::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche de logs par période : " . $e->getMessage()
            );
        }
    }

    public function getErrorLogs(int $limit = 100): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM logs_achats 
                WHERE statut != 'success'
                ORDER BY timestamp DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll();
            
            return array_map(fn($row) => LogAchat::toObject($row), $results);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche des logs d'erreur : " . $e->getMessage()
            );
        }
    }

    public function getStatsDaily(\DateTime $date): array
    {
        try {
            $debut = clone $date;
            $debut->setTime(0, 0, 0);
            $fin = clone $date;
            $fin->setTime(23, 59, 59);

            $stmt = $this->getPdo()->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(*) FILTER (WHERE statut = 'success') as success_count,
                    COUNT(*) FILTER (WHERE statut = 'error') as error_count,
                    COUNT(*) FILTER (WHERE statut = 'validation_error') as validation_error_count,
                    COUNT(*) FILTER (WHERE statut = 'compteur_not_found') as compteur_not_found_count,
                    COUNT(*) FILTER (WHERE statut = 'server_error') as server_error_count,
                    ROUND(AVG(execution_time_ms), 2) as avg_execution_time_ms,
                    MAX(execution_time_ms) as max_execution_time_ms,
                    MIN(execution_time_ms) as min_execution_time_ms,
                    SUM(montant) FILTER (WHERE statut = 'success') as total_montant_success,
                    COUNT(DISTINCT numero_compteur) as unique_compteurs,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM logs_achats 
                WHERE timestamp BETWEEN ? AND ?
            ");
            
            $stmt->execute([
                $debut->format('Y-m-d H:i:s'),
                $fin->format('Y-m-d H:i:s')
            ]);
            
            $result = $stmt->fetch();
            
            return [
                'date' => $date->format('Y-m-d'),
                'total_requests' => (int)$result['total_requests'],
                'success_count' => (int)$result['success_count'],
                'error_count' => (int)$result['error_count'],
                'validation_error_count' => (int)$result['validation_error_count'],
                'compteur_not_found_count' => (int)$result['compteur_not_found_count'],
                'server_error_count' => (int)$result['server_error_count'],
                'success_rate' => $result['total_requests'] > 0 ? 
                    round(($result['success_count'] / $result['total_requests']) * 100, 2) : 0,
                'avg_execution_time_ms' => (float)$result['avg_execution_time_ms'],
                'max_execution_time_ms' => (int)$result['max_execution_time_ms'],
                'min_execution_time_ms' => (int)$result['min_execution_time_ms'],
                'total_montant_success' => (float)$result['total_montant_success'],
                'unique_compteurs' => (int)$result['unique_compteurs'],
                'unique_ips' => (int)$result['unique_ips']
            ];
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques quotidiennes : " . $e->getMessage()
            );
        }
    }

    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = new \DateTime();
            $cutoffDate->modify("-{$daysToKeep} days");
            
            $stmt = $this->getPdo()->prepare("
                DELETE FROM logs_achats 
                WHERE timestamp < ?
            ");
            
            $stmt->execute([$cutoffDate->format('Y-m-d H:i:s')]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du nettoyage des logs : " . $e->getMessage()
            );
        }
    }

    public function getHourlyStats(\DateTime $date): array
    {
        try {
            $debut = clone $date;
            $debut->setTime(0, 0, 0);
            $fin = clone $date;
            $fin->setTime(23, 59, 59);

            $stmt = $this->getPdo()->prepare("
                SELECT 
                    EXTRACT(HOUR FROM timestamp) as heure,
                    COUNT(*) as total_requests,
                    COUNT(*) FILTER (WHERE statut = 'success') as success_count,
                    COUNT(*) FILTER (WHERE statut != 'success') as error_count,
                    ROUND(AVG(execution_time_ms), 2) as avg_execution_time_ms
                FROM logs_achats 
                WHERE timestamp BETWEEN ? AND ?
                GROUP BY EXTRACT(HOUR FROM timestamp)
                ORDER BY heure
            ");
            
            $stmt->execute([
                $debut->format('Y-m-d H:i:s'),
                $fin->format('Y-m-d H:i:s')
            ]);
            
            $results = $stmt->fetchAll();
            
            // Remplir les heures manquantes avec des 0
            $hourlyStats = [];
            for ($i = 0; $i < 24; $i++) {
                $hourlyStats[$i] = [
                    'heure' => $i,
                    'total_requests' => 0,
                    'success_count' => 0,
                    'error_count' => 0,
                    'avg_execution_time_ms' => 0
                ];
            }
            
            foreach ($results as $row) {
                $heure = (int)$row['heure'];
                $hourlyStats[$heure] = [
                    'heure' => $heure,
                    'total_requests' => (int)$row['total_requests'],
                    'success_count' => (int)$row['success_count'],
                    'error_count' => (int)$row['error_count'],
                    'avg_execution_time_ms' => (float)$row['avg_execution_time_ms']
                ];
            }
            
            return array_values($hourlyStats);
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors du calcul des statistiques horaires : " . $e->getMessage()
            );
        }
    }

    public function getTopErrorMessages(int $limit = 10): array
    {
        try {
            $stmt = $this->getPdo()->prepare("
                SELECT 
                    error_message,
                    COUNT(*) as occurrence_count,
                    MAX(timestamp) as last_occurrence
                FROM logs_achats 
                WHERE error_message IS NOT NULL 
                AND timestamp >= CURRENT_DATE - INTERVAL '7 days'
                GROUP BY error_message
                ORDER BY occurrence_count DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Erreur lors de la recherche des messages d'erreur : " . $e->getMessage()
            );
        }
    }
}
