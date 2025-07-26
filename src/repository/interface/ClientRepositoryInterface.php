<?php

namespace Src\Repository\Interface;

use Src\Entity\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;
    
    public function findByEmail(string $email): ?Client;
    
    public function findByTelephone(string $telephone): ?Client;
    
    public function findAll(): array;
    
    public function save(Client $client): Client;
    
    public function delete(int $id): bool;
    
    public function searchByName(string $searchTerm): array;
    
    public function getClientsStats(): array;
}
