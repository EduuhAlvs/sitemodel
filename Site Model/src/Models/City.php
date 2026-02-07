<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class City {
    // Busca todas as cidades para preencher o dropdown da busca
    public static function getAll() {
        $db = Database::getInstance();
        // Ordena por nome para facilitar a busca visual
        $stmt = $db->getConnection()->query("SELECT * FROM cities ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}