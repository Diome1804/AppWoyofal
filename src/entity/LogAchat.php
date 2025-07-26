<?php

namespace Src\Entity;

use App\Core\Abstract\AbstractEntity;

class LogAchat extends AbstractEntity
{
    private ?int $id;
    private ?string $numeroCompteur;
    private ?float $montant;
    private string $statut;
    private ?string $ipAddress;
    private ?string $userAgent;
    private string $method;
    private ?string $endpoint;
    private ?array $requestData;
    private ?array $responseData;
    private ?string $errorMessage;
    private ?int $executionTimeMs;
    private \DateTime $timestamp;

    public function __construct(
        string $statut,
        ?string $numeroCompteur = null,
        ?float $montant = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $method = 'POST',
        ?string $endpoint = null,
        ?array $requestData = null,
        ?array $responseData = null,
        ?string $errorMessage = null,
        ?int $executionTimeMs = null,
        ?\DateTime $timestamp = null,
        ?int $id = null
    ) {
        $this->validateStatut($statut);
        
        $this->id = $id;
        $this->numeroCompteur = $numeroCompteur;
        $this->montant = $montant;
        $this->statut = $statut;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->requestData = $requestData;
        $this->responseData = $responseData;
        $this->errorMessage = $errorMessage;
        $this->executionTimeMs = $executionTimeMs;
        $this->timestamp = $timestamp ?? new \DateTime();
    }

    public static function toObject(array $data): static
    {
        return new self(
            statut: $data['statut'],
            numeroCompteur: $data['numero_compteur'] ?? null,
            montant: isset($data['montant']) ? (float)$data['montant'] : null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            method: $data['method'] ?? 'POST',
            endpoint: $data['endpoint'] ?? null,
            requestData: isset($data['request_data']) ? json_decode($data['request_data'], true) : null,
            responseData: isset($data['response_data']) ? json_decode($data['response_data'], true) : null,
            errorMessage: $data['error_message'] ?? null,
            executionTimeMs: isset($data['execution_time_ms']) ? (int)$data['execution_time_ms'] : null,
            timestamp: isset($data['timestamp']) ? new \DateTime($data['timestamp']) : null,
            id: $data['id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_compteur' => $this->numeroCompteur,
            'montant' => $this->montant,
            'statut' => $this->statut,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'method' => $this->method,
            'endpoint' => $this->endpoint,
            'request_data' => $this->requestData ? json_encode($this->requestData) : null,
            'response_data' => $this->responseData ? json_encode($this->responseData) : null,
            'error_message' => $this->errorMessage,
            'execution_time_ms' => $this->executionTimeMs,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s')
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCompteur(): ?string
    {
        return $this->numeroCompteur;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getRequestData(): ?array
    {
        return $this->requestData;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getExecutionTimeMs(): ?int
    {
        return $this->executionTimeMs;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    // Business methods
    public function isSuccess(): bool
    {
        return $this->statut === 'success';
    }

    public function isError(): bool
    {
        return $this->statut === 'error' || str_contains($this->statut, 'error');
    }

    public function getTimestampFormatted(): string
    {
        return $this->timestamp->format('d/m/Y H:i:s');
    }

    public function getExecutionTimeFormatted(): string
    {
        if ($this->executionTimeMs === null) {
            return 'N/A';
        }
        
        if ($this->executionTimeMs < 1000) {
            return $this->executionTimeMs . ' ms';
        }
        
        return round($this->executionTimeMs / 1000, 2) . ' s';
    }

    public function getMontantFormatted(): string
    {
        if ($this->montant === null) {
            return 'N/A';
        }
        
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public function getUserInfo(): array
    {
        return [
            'ip' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => $this->getTimestampFormatted()
        ];
    }

    public static function createSuccess(
        string $numeroCompteur,
        float $montant,
        array $responseData,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $executionTimeMs = null
    ): self {
        return new self(
            statut: 'success',
            numeroCompteur: $numeroCompteur,
            montant: $montant,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            endpoint: '/api/woyofal/achat',
            requestData: ['compteur' => $numeroCompteur, 'montant' => $montant],
            responseData: $responseData,
            executionTimeMs: $executionTimeMs
        );
    }

    public static function createError(
        string $statut,
        string $errorMessage,
        ?string $numeroCompteur = null,
        ?float $montant = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $executionTimeMs = null
    ): self {
        return new self(
            statut: $statut,
            numeroCompteur: $numeroCompteur,
            montant: $montant,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            endpoint: '/api/woyofal/achat',
            requestData: $numeroCompteur ? ['compteur' => $numeroCompteur, 'montant' => $montant] : null,
            errorMessage: $errorMessage,
            executionTimeMs: $executionTimeMs
        );
    }

    // Validation privée
    private function validateStatut(string $statut): void
    {
        $statutsValides = [
            'success', 'error', 'validation_error', 
            'insufficient_funds', 'compteur_not_found', 'server_error'
        ];
        
        if (!in_array($statut, $statutsValides)) {
            throw new \InvalidArgumentException('Statut de log invalide');
        }
    }
}
