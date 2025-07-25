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
            $user = getenv('DB_USER');
            $password = getenv('DB_PASSWORD');
            $dsn = getenv('dsn');
           
            $this->connection = new PDO(
             $dsn,
              $user,
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