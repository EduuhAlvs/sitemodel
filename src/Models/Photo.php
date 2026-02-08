<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Photo {
    public static function getAllByProfile(int $profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profile_photos WHERE profile_id = :pid ORDER BY created_at DESC");
        $stmt->execute(['pid' => $profileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function add(int $profileId, string $filePath) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("INSERT INTO profile_photos (profile_id, file_path, is_approved) VALUES (:pid, :path, 0)");
        $stmt->execute(['pid' => $profileId, 'path' => $filePath]);
        return $db->getConnection()->lastInsertId();
    }

    public static function delete(int $photoId, int $profileId) {
        $db = Database::getInstance();
        // Primeiro pegamos o caminho para deletar do disco
        $stmt = $db->getConnection()->prepare("SELECT file_path FROM profile_photos WHERE id = :id AND profile_id = :pid");
        $stmt->execute(['id' => $photoId, 'pid' => $profileId]);
        $photo = $stmt->fetch();

        if ($photo) {
            // Deleta do banco
            $delParams = ['id' => $photoId]; // Correção para evitar erro de parâmetros
            $db->getConnection()->prepare("DELETE FROM profile_photos WHERE id = :id")->execute($delParams);
            return $photo['file_path']; // Retorna o caminho para o controller apagar o arquivo
        }
        return false;
    }

    // NOVO MÉTODO: Busca fotos aprovadas para exibir no perfil público
    public static function getByProfileId($profileId) {
        $db = Database::getInstance();
        // Traz apenas fotos aprovadas (is_approved = 1) ou todas se você não estiver moderando ainda
        // Sugiro começar trazendo tudo para testar, depois coloque 'WHERE is_approved = 1'
        $stmt = $db->getConnection()->prepare("
            SELECT * FROM profile_photos 
            WHERE profile_id = :pid 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['pid' => $profileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}