<?php

namespace Src\Repository\Interface;

use Src\Entity\LogAchat;

interface LogAchatRepositoryInterface
{
    public function save(LogAchat $log): LogAchat;
    
    public function findByCompteur(string $numeroCompteur, int $limit = 50): array;
    
    public function findByStatut(string $statut, int $limit = 100): array;
    
    public function findByPeriod(\DateTime $dateDebut, \DateTime $dateFin): array;
    
    public function getErrorLogs(int $limit = 100): array;
    
    public function getStatsDaily(\DateTime $date): array;
    
    public function cleanupOldLogs(int $daysToKeep = 90): int;
}
