<?php

namespace App\Core;


use \PDO;
use \PDOException;

class Database{
    
    private $connection;
    private  static Database|null $instance = null;


    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

      protected function __construct() {
        
        try {
            $host = DB_HOST;
            $port = DB_PORT;
            $database = DB_DATABASE;
            $username = DB_USERNAME;
            $password = DB_PASSWORD;
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$database";
           
            $this->connection = new PDO(
             $dsn,
              $username,
              $password,
              [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
              ]
              );

             
        }catch(PDOException $e){
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

   
    public function getConnection():PDO{
        return $this->connection;
    }


    
}