<?php



// Configuration des routes API Woyofal
// Respecte les principes REST et le cahier des charges

$routes = [
    // Route principale de l'API
    'GET:/' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'index'
    ],

    // === ENDPOINTS PRINCIPAUX ===
    
    // Achat de crédit Woyofal (endpoint principal)
    'POST:/api/woyofal/achat' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'achat'
    ],

    // Simulation d'achat (sans persistance)
    'POST:/api/woyofal/simulate' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'simulate'
    ],

    // === ENDPOINTS UTILITAIRES ===
    
    // Statut de l'API
    'GET:/api/woyofal/status' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'status'
    ],

    // Informations sur les tranches tarifaires
    'GET:/api/woyofal/tranches' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'tranches'
    ],

    // Test de connectivité
    'GET:/api/woyofal/ping' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'ping'
    ],

    // === GESTION CORS ===
    
    // Options pour CORS
    'OPTIONS:/api/woyofal/achat' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'options'
    ],

    'OPTIONS:/api/woyofal/simulate' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'options'
    ],

    // === GESTION D'ERREURS ===
    
    // Fallback pour méthodes non autorisées
    'PUT:/api/woyofal/achat' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'methodNotAllowed'
    ],

    'DELETE:/api/woyofal/achat' => [
        'controller' => 'Src\Controller\WoyofalController',
        'method' => 'methodNotAllowed'
    ]
];

// Routes avec paramètres dynamiques (si nécessaire dans le futur)
// Exemple: 'GET:/api/woyofal/achat/{reference}' pour consulter un achat

