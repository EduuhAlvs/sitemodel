<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Location
{
    // Busca cidades pelo nome (Autocomplete)
    public static function search(string $term)
    {
        $db = Database::getInstance();
        $term = "%$term%";
        // Busca limitando a 10 resultados para ser rápido
        $stmt = $db->getConnection()->prepare("
            SELECT cities.id, cities.name, countries.code as country
            FROM cities
            JOIN countries ON cities.country_id = countries.id
            WHERE cities.name LIKE :term
            LIMIT 10
        ");
        $stmt->execute(['term' => $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pega as cidades que a modelo já selecionou
    public static function getByProfile(int $profileId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("
            SELECT c.id, c.name, pl.is_base_city
            FROM profile_locations pl
            JOIN cities c ON pl.city_id = c.id
            WHERE pl.profile_id = :pid
        ");
        $stmt->execute(['pid' => $profileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adiciona cidade ao perfil
    public static function add(int $profileId, int $cityId)
    {
        $db = Database::getInstance();
        // IGNORE para não dar erro se tentar adicionar a mesma cidade 2x
        $stmt = $db->getConnection()->prepare("INSERT IGNORE INTO profile_locations (profile_id, city_id) VALUES (:pid, :cid)");
        return $stmt->execute(['pid' => $profileId, 'cid' => $cityId]);
    }

    // Remove cidade
    public static function remove(int $profileId, int $cityId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("DELETE FROM profile_locations WHERE profile_id = :pid AND city_id = :cid");
        return $stmt->execute(['pid' => $profileId, 'cid' => $cityId]);
    }

    // Define cidade base (Reseta as outras e marca a nova)
    public static function setBase(int $profileId, int $cityId)
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $conn->beginTransaction();
        try {
            // Desmarca todas
            $stmt1 = $conn->prepare("UPDATE profile_locations SET is_base_city = 0 WHERE profile_id = :pid");
            $stmt1->execute(['pid' => $profileId]);

            // Marca a escolhida
            $stmt2 = $conn->prepare("UPDATE profile_locations SET is_base_city = 1 WHERE profile_id = :pid AND city_id = :cid");
            $stmt2->execute(['pid' => $profileId, 'cid' => $cityId]);

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
}
