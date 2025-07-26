<?php

namespace App\Core\Abstract;

use app\core\Singleton;

abstract class AbstractController extends Singleton{
 

 abstract public function index();
           
    public function renderJson($data, $httpCode = 200)
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
    

}