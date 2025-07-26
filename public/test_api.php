<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once "../vendor/autoload.php";
    require_once "../app/config/bootstrap.php";
    
    // Test direct sans router
    $database = \App\Core\Database::getInstance();
    
    // Repositories
    $logAchatRepo = new \Src\Repository\LogAchatRepository($database);
    $compteurRepo = new \Src\Repository\CompteurRepository($database);
    $trancheRepo = new \Src\Repository\TrancheToarifaireRepository($database);
    $consommationRepo = new \Src\Repository\ConsommationMensuelleRepository($database);
    $achatRepo = new \Src\Repository\AchatWoyofalRepository($database);
    
    // Services
    $validationService = new \Src\Service\ValidationService();
    $loggerService = new \Src\Service\LoggerService($logAchatRepo);
    $trancheCalculatorService = new \Src\Service\TrancheCalculatorService(
        $trancheRepo,
        $consommationRepo
    );
    $achatService = new \Src\Service\AchatWoyofalService(
        $compteurRepo,
        $achatRepo,
        $consommationRepo,
        $trancheCalculatorService,
        $loggerService
    );
    
    $controller = new \Src\Controller\WoyofalController(
        $achatService,
        $validationService,
        $loggerService
    );
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->achat();
    } else {
        echo json_encode([
            'message' => 'API Test Direct - Woyofal',
            'status' => 'OK',
            'method' => $_SERVER['REQUEST_METHOD'],
            'endpoint' => '/test_api.php'
        ]);
    }
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'data' => null,
        'statut' => 'error',
        'code' => 500,
        'message' => 'Erreur: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
