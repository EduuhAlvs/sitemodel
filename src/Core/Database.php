<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Em produção, nunca mostre o erro real na tela. Logue em arquivo.
            error_log("DB Connection Error: " . $e->getMessage());
            throw new Exception("Erro crítico de conexão com o banco de dados.");
        }
    }

    // Padrão Singleton: Garante uma única conexão
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    // Método auxiliar para queries seguras (Prepared Statements)
    public static function query($sql, $params = [])
    {
        $stmt = self::getInstance()->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
