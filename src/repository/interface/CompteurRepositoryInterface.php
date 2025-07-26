<?php

namespace Src\Repository\Interface;

use Src\Entity\Compteur;

interface CompteurRepositoryInterface
{
    public function findById(int $id): ?Compteur;
    
    public function findByNumero(string $numero): ?Compteur;
    
    public function findByClientId(int $clientId): array;
    
    public function findActifs(): array;
    
    public function save(Compteur $compteur): Compteur;
    
    public function delete(int $id): bool;
    
    public function activate(int $id): bool;
    
    public function deactivate(int $id): bool;
    
    public function existsByNumero(string $numero): bool;
    
    public function findCompteurWithClient(string $numero): ?array;
}
