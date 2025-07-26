<?php

namespace Src\Service;

use Src\Entity\AchatWoyofal;
use Src\Repository\Interface\CompteurRepositoryInterface;
use Src\Repository\Interface\AchatWoyofalRepositoryInterface;
use Src\Repository\Interface\ConsommationMensuelleRepositoryInterface;
use Src\Service\Interface\AchatWoyofalServiceInterface;
use Src\Service\Interface\TrancheCalculatorServiceInterface;
use Src\Service\Interface\LoggerServiceInterface;

class AchatWoyofalService implements AchatWoyofalServiceInterface
{
    public function __construct(
        private readonly CompteurRepositoryInterface $compteurRepository,
        private readonly AchatWoyofalRepositoryInterface $achatRepository,
        private readonly ConsommationMensuelleRepositoryInterface $consommationRepository,
        private readonly TrancheCalculatorServiceInterface $trancheCalculatorService,
        private readonly LoggerServiceInterface $loggerService
    ) {}

    public function processAchat(string $numeroCompteur, float $montant, array $requestInfo = []): array
    {
        $startTime = microtime(true);
        
        try {
            // 1. Validation des données
            $validationResult = $this->validateAchatData($numeroCompteur, $montant);
            if (!$validationResult['success']) {
                $this->loggerService->logValidationError(
                    $validationResult['message'],
                    ['compteur' => $numeroCompteur, 'montant' => $montant],
                    $requestInfo
                );
                return $this->formatErrorResponse($validationResult['message'], 400);
            }

            // 2. Vérification du compteur
            $compteurData = $this->validateCompteur($numeroCompteur);
            if (!$compteurData['success']) {
                $this->loggerService->logCompteurNotFound($numeroCompteur, $requestInfo);
                return $this->formatErrorResponse($compteurData['message'], 404);
            }

            $compteur = $compteurData['compteur'];
            $clientInfo = $compteurData['client'];

            // 3. Calcul des tranches
            $calculResult = $this->trancheCalculatorService->calculateTrancheForClient(
                $clientInfo['id'], 
                $montant
            );

            if (!$calculResult['success']) {
                $this->loggerService->logError(
                    'server_error',
                    $calculResult['error'],
                    $numeroCompteur,
                    $montant,
                    $requestInfo
                );
                return $this->formatErrorResponse('Erreur de calcul des tranches', 500);
            }
            
            // Vérifier que la tranche finale est définie
            if (!isset($calculResult['tranche']) || $calculResult['tranche'] === null) {
                $this->loggerService->logError(
                    'server_error',
                    'Aucune tranche applicable trouvée pour le montant ' . $montant,
                    $numeroCompteur,
                    $montant,
                    $requestInfo
                );
                return $this->formatErrorResponse('Aucune tranche applicable pour ce montant', 500);
            }

            // 4. Création de l'achat
            $achat = $this->createAchat(
                $numeroCompteur,
                $clientInfo['id'],
                $montant,
                $calculResult['kwh_achetes'],
                $calculResult['prix_unitaire'],
                $calculResult['tranche']->getId(),
                $requestInfo
            );

            // 5. Mise à jour de la consommation mensuelle
            $periode = \Src\Entity\ConsommationMensuelle::getCurrentPeriod();
            $this->consommationRepository->updateOrCreate(
                $clientInfo['id'],
                $periode['mois'],
                $periode['annee'],
                $montant,
                $calculResult['kwh_achetes']
            );

            // 6. Formatage de la réponse
            $response = $this->formatSuccessResponse($achat, $clientInfo, $calculResult['tranche']);

            // 7. Log de succès
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $this->loggerService->logSuccess(
                $numeroCompteur,
                $montant,
                $response,
                $requestInfo,
                $executionTime
            );

            return $response;

        } catch (\Exception $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            $this->loggerService->logError(
                'server_error',
                $e->getMessage(),
                $numeroCompteur,
                $montant,
                $requestInfo,
                $executionTime
            );

            return $this->formatErrorResponse(
                'Erreur interne du serveur', 
                500
            );
        }
    }

    public function validateAchatData(string $numeroCompteur, float $montant): array
    {
        $errors = [];

        // Validation du numéro de compteur
        if (empty($numeroCompteur)) {
            $errors[] = 'Le numéro de compteur est obligatoire';
        } elseif (!preg_match('/^[0-9]{8,12}$/', $numeroCompteur)) {
            $errors[] = 'Le numéro de compteur doit contenir entre 8 et 12 chiffres';
        }

        // Validation du montant
        if ($montant <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        } elseif ($montant < 500) {
            $errors[] = 'Le montant minimum est de 500 FCFA';
        } elseif ($montant > 1000000) {
            $errors[] = 'Le montant maximum est de 1 000 000 FCFA';
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Validation réussie' : implode('. ', $errors)
        ];
    }

    public function validateCompteur(string $numeroCompteur): array
    {
        try {
            $compteurData = $this->compteurRepository->findCompteurWithClient($numeroCompteur);

            if (!$compteurData) {
                return [
                    'success' => false,
                    'message' => 'Le numéro de compteur n\'a pas été trouvé'
                ];
            }

            $compteur = $compteurData['compteur'];
            if (!$compteur->isActif()) {
                return [
                    'success' => false,
                    'message' => 'Le compteur est désactivé'
                ];
            }

            return [
                'success' => true,
                'compteur' => $compteur,
                'client' => $compteurData['client']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du compteur'
            ];
        }
    }

    public function createAchat(
        string $numeroCompteur,
        int $clientId,
        float $montant,
        float $kwhAchetes,
        float $prixUnitaire,
        int $trancheId,
        array $requestInfo = []
    ): AchatWoyofal {
        $achat = new AchatWoyofal(
            reference: $this->generateReference(),
            codeRecharge: $this->generateCodeRecharge(),
            numeroCompteur: $numeroCompteur,
            clientId: $clientId,
            montant: $montant,
            kwhAchetes: $kwhAchetes,
            prixUnitaire: $prixUnitaire,
            trancheId: $trancheId,
            statut: 'success',
            dateAchat: new \DateTime(),
            ipAddress: $requestInfo['ip_address'] ?? null,
            userAgent: $requestInfo['user_agent'] ?? null
        );

        return $this->achatRepository->save($achat);
    }

    public function generateReference(): string
    {
        return $this->achatRepository->generateReference();
    }

    public function generateCodeRecharge(): string
    {
        return $this->achatRepository->generateCodeRecharge();
    }

    public function formatSuccessResponse(AchatWoyofal $achat, array $clientInfo, $tranche): array
    {
        return [
            'data' => [
                'compteur' => $achat->getNumeroCompteur(),
                'reference' => $achat->getReference(),
                'code' => $achat->getCodeRecharge(),
                'date' => $achat->getDateAchatFormatted(),
                'tranche' => $tranche->getNom(),
                'prix' => $achat->getPrixUnitaireFormatted(),
                'nbreKwt' => $achat->getKwhFormatted(),
                'client' => $clientInfo['prenom'] . ' ' . $clientInfo['nom']
            ],
            'statut' => 'success',
            'code' => 200,
            'message' => 'Achat effectué avec succès'
        ];
    }

    public function formatErrorResponse(string $message, int $code = 400): array
    {
        return [
            'data' => null,
            'statut' => 'error',
            'code' => $code,
            'message' => $message
        ];
    }

    /**
     * Méthode utilitaire pour simuler un achat sans le persister
     */
    public function simulateAchat(string $numeroCompteur, float $montant): array
    {
        // Validation
        $validationResult = $this->validateAchatData($numeroCompteur, $montant);
        if (!$validationResult['success']) {
            return $this->formatErrorResponse($validationResult['message'], 400);
        }

        // Vérification compteur
        $compteurData = $this->validateCompteur($numeroCompteur);
        if (!$compteurData['success']) {
            return $this->formatErrorResponse($compteurData['message'], 404);
        }

        // Simulation du calcul
        $calculResult = $this->trancheCalculatorService->simulateAchat(
            $compteurData['client']['id'],
            $montant
        );

        if (!$calculResult['success']) {
            return $this->formatErrorResponse('Erreur de simulation', 500);
        }

        return [
            'data' => [
                'compteur' => $numeroCompteur,
                'montant_simule' => number_format($montant, 0, ',', ' ') . ' FCFA',
                'kwh_estimes' => number_format($calculResult['kwh_achetes'], 2, ',', ' ') . ' kWh',
                'prix_unitaire' => number_format($calculResult['prix_unitaire'], 0, ',', ' ') . ' FCFA/kWh',
                'tranche_appliquee' => $calculResult['tranche']->getNom(),
                'client' => $compteurData['client']['prenom'] . ' ' . $compteurData['client']['nom']
            ],
            'statut' => 'simulation',
            'code' => 200,
            'message' => 'Simulation d\'achat réalisée'
        ];
    }
}
