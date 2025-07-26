<?php

namespace App\Core;

use App\Core\Singleton;
use App\Core\App;
use App\Core\Database;
require_once "../app/config/middlewares.php";

class Router extends Singleton
{

    public static function resolve()
    {
        global $routes;
        require_once "../routes/route.web.php";
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $currentUri = trim(parse_url($requestUri, PHP_URL_PATH), '/');

        $routeKey = $requestMethod . ':/' . $currentUri;
        
        if (isset($routes[$routeKey])) {
            self::executeRoute($routes[$routeKey]);
            return;
        }
        
        foreach ($routes as $pattern => $route) {
            if (self::matchRoute($pattern, $routeKey)) {
                $params = self::extractParams($pattern, $routeKey);
                self::executeRoute($route, $params);
                return;
            }
        }
        
        self::sendJsonResponse(['error' => 'Route not found'], 404);
    }

    private static function matchRoute($pattern, $uri)
    {
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $uri);
    }

    private static function extractParams($pattern, $uri)
    {
        $params = [];
        
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); 
            
            foreach ($paramNames[1] as $index => $paramName) {
                if (isset($matches[$index])) {
                    $params[$paramName] = $matches[$index];
                }
            }
        }
        
        return $params;
    }

    private static function executeRoute($route, $params = [])
    {
        try {
            if (isset($route['middlewares'])) {
                foreach ($route['middlewares'] as $middleware) {
                    if (!self::executeMiddleware($middleware)) {
                        return;
                    }
                }
            }

            $controllerClass = $route['controller'];
            $method = $route['method'];

            if (!class_exists($controllerClass)) {
                self::sendJsonResponse(['error' => 'Controller not found'], 500);
                return;
            }

            // Utiliser App::getDependency pour instancier le contrôleur avec ses dépendances
            $controllerName = basename(str_replace('\\', '/', $controllerClass));
            $controllerKey = lcfirst($controllerName);
            
            try {
                $controller = App::getDependency($controllerKey);
            } catch (\Exception $e) {
                // Fallback : instanciation manuelle pour développement
                $controller = self::createControllerWithDependencies($controllerClass);
            }
            
            if (!method_exists($controller, $method)) {
                self::sendJsonResponse(['error' => 'Method not found'], 500);
                return;
            }

            if (!empty($params)) {
                $controller->$method($params);
            } else {
                $controller->$method();
            }

        } catch (\Exception $e) {
            self::sendJsonResponse(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }

    private static function executeMiddleware($middlewareName)
    {
        global $middlewares;
        
        if (isset($middlewares[$middlewareName])) {
            $middlewareFunction = $middlewares[$middlewareName];
            $result = $middlewareFunction();
            
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }

    private static function sendJsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Crée un contrôleur avec ses dépendances (fallback pour développement)
     */
    private static function createControllerWithDependencies(string $controllerClass)
    {
        // Pour WoyofalController, créer manuellement les dépendances
        if ($controllerClass === 'Src\Controller\WoyofalController') {
            $database = Database::getInstance();
            
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
            
            return new \Src\Controller\WoyofalController(
                $achatService,
                $validationService,
                $loggerService
            );
        }
        
        // Fallback générique
        return new $controllerClass();
    }
}