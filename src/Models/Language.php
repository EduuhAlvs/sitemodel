<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Language {
    // Lista todas as línguas do perfil
    public static function getByProfile(int $profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profile_languages WHERE profile_id = :pid");
        $stmt->execute(['pid' => $profileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adiciona uma língua
    public static function add(int $profileId, string $language, string $level) {
        $db = Database::getInstance();
        
        // Verifica se já existe pra não duplicar
        $check = $db->getConnection()->prepare("SELECT id FROM profile_languages WHERE profile_id = :pid AND language = :lang");
        $check->execute(['pid' => $profileId, 'lang' => $language]);
        
        if ($check->rowCount() > 0) {
            return false; // Já tem essa língua
        }

        $stmt = $db->getConnection()->prepare("INSERT INTO profile_languages (profile_id, language, level) VALUES (:pid, :lang, :lvl)");
        return $stmt->execute(['pid' => $profileId, 'lang' => $language, 'lvl' => $level]);
    }

    // Remove
    public static function remove(int $id, int $profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("DELETE FROM profile_languages WHERE id = :id AND profile_id = :pid");
        return $stmt->execute(['id' => $id, 'pid' => $profileId]);
    }
}