<?php

namespace Src\Controller;

use Src\Service\Interface\AchatWoyofalServiceInterface;
use Src\Service\Interface\ValidationServiceInterface;
use Src\Service\Interface\LoggerServiceInterface;

class WoyofalController
{
    public function __construct(
        private readonly AchatWoyofalServiceInterface $achatService,
        private readonly ValidationServiceInterface $validationService,
        private readonly LoggerServiceInterface $loggerService
    ) {}

    /**
     * Méthode pour rendre une réponse JSON
     */
    private function renderJson(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
                         
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index(): void
    {
        $this->renderJson([
            'message' => 'API Woyofal - Système de prépaiement électricité Senelec',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /api/woyofal/achat' => 'Effectuer un achat de crédit Woyofal',
                'POST /api/woyofal/simulate' => 'Simuler un achat sans le persister',
                'GET /api/woyofal/status' => 'Statut de l\'API'
            ]
        ]);
    }

    /**
     * Endpoint principal pour effectuer un achat Woyofal
     * POST /api/woyofal/achat
     */
    public function achat(): void
    {
        try {
            // 1. Récupération et validation des données JSON
            $requestData = $this->getJsonInput();
            if (!$requestData) {
                $this->renderJson([
                    'data' => null,
                    'statut' => 'error',
                    'code' => 400,
                    'message' => 'Données JSON invalides ou manquantes'
                ], 400);
                return;
            }

            // 2. Nettoyage des données d'entrée
            $requestData = $this->validationService->sanitizeInput($requestData);

            // 3. Extraction des informations de la requête
            $requestInfo = $this->loggerService->extractRequestInfo();

            // 4. Traitement de l'achat via le service
            $result = $this->achatService->processAchat(
                $requestData['compteur'] ?? '',
                (float)($requestData['montant'] ?? 0),
                $requestInfo
            );

            // 5. Retour de la réponse
            $this->renderJson($result, $result['code']);

        } catch (\Exception $e) {
            $this->loggerService->logError(
                'server_error',
                'Erreur non gérée dans le contrôleur: ' . $e->getMessage(),
                null,
                null,
                $this->loggerService->extractRequestInfo()
            );

            $this->renderJson([
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Endpoint pour simuler un achat sans le persister
     * POST /api/woyofal/simulate
     */
    public function simulate(): void
    {
        try {
            $requestData = $this->getJsonInput();
            if (!$requestData) {
                $this->renderJson([
                    'data' => null,
                    'statut' => 'error',
                    'code' => 400,
                    'message' => 'Données JSON invalides ou manquantes'
                ], 400);
                return;
            }

            $requestData = $this->validationService->sanitizeInput($requestData);

            $result = $this->achatService->simulateAchat(
                $requestData['compteur'] ?? '',
                (float)($requestData['montant'] ?? 0)
            );

            $this->renderJson($result, $result['code']);

        } catch (\Exception $e) {
            $this->renderJson([
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la simulation'
            ], 500);
        }
    }

    /**
     * Endpoint pour vérifier le statut de l'API
     * GET /api/woyofal/status
     */
    public function status(): void
    {
        try {
            $this->renderJson([
                'data' => [
                    'api_status' => 'operational',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0',
                    'environment' => $_ENV['APP_ENV'] ?? 'production'
                ],
                'statut' => 'success',
                'code' => 200,
                'message' => 'API opérationnelle'
            ]);

        } catch (\Exception $e) {
            $this->renderJson([
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur de statut'
            ], 500);
        }
    }

    /**
     * Endpoint pour obtenir les informations des tranches tarifaires
     * GET /api/woyofal/tranches
     */
    public function tranches(): void
    {
        try {
            // Pour cet endpoint, on aurait besoin du TrancheCalculatorService
            // En attendant, on retourne les tranches fixes
            $tranches = [
                [
                    'nom' => 'Tranche 1 - Social',
                    'description' => 'De 0 à 100 kWh',
                    'prix' => '75 FCFA/kWh',
                    'ordre' => 1
                ],
                [
                    'nom' => 'Tranche 2 - Normal',
                    'description' => 'De 100.01 à 300 kWh',
                    'prix' => '125 FCFA/kWh',
                    'ordre' => 2
                ],
                [
                    'nom' => 'Tranche 3 - Elevé',
                    'description' => 'À partir de 300.01 kWh',
                    'prix' => '175 FCFA/kWh',
                    'ordre' => 3
                ]
            ];

            $this->renderJson([
                'data' => $tranches,
                'statut' => 'success',
                'code' => 200,
                'message' => 'Tranches tarifaires récupérées'
            ]);

        } catch (\Exception $e) {
            $this->renderJson([
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la récupération des tranches'
            ], 500);
        }
    }

    /**
     * Récupère et décode les données JSON de la requête
     */
    private function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }

        if (!$this->validationService->isValidJson($input)) {
            return null;
        }

        $data = json_decode($input, true);
        
        return is_array($data) ? $data : null;
    }

    /**
     * Valide les headers de la requête
     */
    private function validateRequestHeaders(): array
    {
        $errors = [];

        // Vérifier Content-Type pour POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (!str_contains($contentType, 'application/json')) {
                $errors[] = 'Content-Type doit être application/json';
            }
        }

        return $errors;
    }

    /**
     * Gère les erreurs de méthode HTTP non supportée
     */
    public function methodNotAllowed(): void
    {
        $this->renderJson([
            'data' => null,
            'statut' => 'error',
            'code' => 405,
            'message' => 'Méthode HTTP non autorisée'
        ], 405);
    }

    /**
     * Gère les erreurs 404
     */
    public function notFound(): void
    {
        $this->renderJson([
            'data' => null,
            'statut' => 'error',
            'code' => 404,
            'message' => 'Endpoint non trouvé'
        ], 404);
    }

    /**
     * Endpoint pour les tests de connectivité
     * GET /api/woyofal/ping
     */
    public function ping(): void
    {
        $this->renderJson([
            'data' => [
                'message' => 'pong',
                'timestamp' => microtime(true),
                'server_time' => date('Y-m-d H:i:s')
            ],
            'statut' => 'success',
            'code' => 200,
            'message' => 'API accessible'
        ]);
    }

    /**
     * Endpoint pour les options CORS
     * OPTIONS /*
     */
    public function options(): void
    {
        // Les headers CORS sont déjà définis dans AbstractController
        http_response_code(200);
        exit;
    }
}
