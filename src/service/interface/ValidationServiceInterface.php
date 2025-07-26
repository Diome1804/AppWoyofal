<?php

namespace Src\Service\Interface;

interface ValidationServiceInterface
{
    /**
     * Valide les données d'une requête d'achat Woyofal
     */
    public function validateAchatRequest(array $requestData): array;
    
    /**
     * Valide un numéro de compteur
     */
    public function validateNumeroCompteur(string $numero): array;
    
    /**
     * Valide un montant d'achat
     */
    public function validateMontant(float $montant): array;
    
    /**
     * Valide les données d'un client
     */
    public function validateClientData(array $clientData): array;
    
    /**
     * Valide les données d'un compteur
     */
    public function validateCompteurData(array $compteurData): array;
    
    /**
     * Vérifie si une chaîne est un JSON valide
     */
    public function isValidJson(string $json): bool;
    
    /**
     * Nettoie et valide les données d'entrée
     */
    public function sanitizeInput(array $data): array;
    
    /**
     * Valide un format de date
     */
    public function validateDate(string $date, string $format = 'Y-m-d'): bool;
    
    /**
     * Valide une adresse email
     */
    public function validateEmail(string $email): bool;
    
    /**
     * Valide un numéro de téléphone sénégalais
     */
    public function validateSenegalPhone(string $phone): bool;
}
