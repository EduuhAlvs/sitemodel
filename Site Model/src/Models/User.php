<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    // Busca usuário pelo e-mail
    public static function findByEmail(string $email) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cria um novo usuário
    public static function create(string $email, string $password, string $type = 'member') {
        $db = Database::getInstance();
        
        // Hash seguro da senha (Argon2ID é o padrão moderno do PHP 8)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, password_hash, user_type, status, created_at) 
                VALUES (:email, :hash, :type, 'active', NOW())";
        
        $stmt = $db->getConnection()->prepare($sql);
        
        try {
            $stmt->execute([
                'email' => $email,
                'hash' => $hash,
                'type' => $type
            ]);
            return $db->getConnection()->lastInsertId();
        } catch (\PDOException $e) {
            // Se der erro de duplicidade (código 23000), retorna false
            if ($e->getCode() == '23000') {
                return false;
            }
            throw $e;
        }
    }
}