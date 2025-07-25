<?php
namespace App\Core\Abstract;
use App\Core\Database;

use \PDO;

abstract class AbstractRepository extends Database{

    protected PDO $pdo;

    private  static Database|null $instance = null;
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    protected function __construct(){
        $this->pdo = parent::getInstance()->getConnection();
    }

    abstract public function selectAll();

}