<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Profile {
    
    // CORREÇÃO: Método adicionado para evitar "Call to undefined method"
    public static function getById(int $id) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByUserId(int $userId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Novo método para verificar propriedade (Usado no Controller para segurança)
    public static function isOwner(int $userId, int $profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT id FROM profiles WHERE id = :pid AND user_id = :uid");
        $stmt->execute(['pid' => $profileId, 'uid' => $userId]);
        return $stmt->fetchColumn() ? true : false;
    }

    public static function createDraft(int $userId, string $email) {
        $db = Database::getInstance();
        $slug = preg_replace('/[^a-z0-9]/', '', strtolower(explode('@', $email)[0])) . '-' . uniqid();
        $sql = "INSERT INTO profiles (user_id, slug, display_name, phone, gender, birth_date, status) 
                VALUES (:uid, :slug, 'Modelo Nova', '', 'woman', '2000-01-01', 'active')";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute(['uid' => $userId, 'slug' => $slug]);
        return $db->getConnection()->lastInsertId();
    }

    public static function update(int $profileId, array $data) {
        $db = Database::getInstance();
        if (empty($data)) return false;
        $setPart = [];
        foreach ($data as $key => $value) { $setPart[] = "{$key} = :{$key}"; }
        $sql = "UPDATE profiles SET " . implode(', ', $setPart) . " WHERE id = :id_pk";
        $data['id_pk'] = $profileId;
        $stmt = $db->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    public static function getListPublic(int $limit = 20, array $filters = []) {
        $db = Database::getInstance();
        $sql = "SELECT p.*, (SELECT file_path FROM profile_photos WHERE profile_id = p.id ORDER BY id ASC LIMIT 1) as cover_photo, c.name as city_name, c.country_id, u.status as user_status FROM profiles p JOIN users u ON p.user_id = u.id LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1 LEFT JOIN cities c ON pl.city_id = c.id WHERE u.status = 'active' AND p.status = 'active'";
        $params = [];
        if (!empty($filters['gender'])) { $sql .= " AND p.gender = :gender"; $params['gender'] = $filters['gender']; }
        if (!empty($filters['city'])) { if (is_numeric($filters['city'])) { $sql .= " AND c.id = :city"; } else { $sql .= " AND c.name LIKE :city"; } $params['city'] = $filters['city']; }
        if (!empty($filters['search'])) { $sql .= " AND (p.display_name LIKE :search OR p.bio LIKE :search)"; $params['search'] = '%' . $filters['search'] . '%'; }
        $sql .= " ORDER BY p.ranking_score DESC, RAND() LIMIT " . (int)$limit;
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca Perfil Completo pelo Slug (Página Pública)
    public static function getBySlug(string $slug) {
        $db = Database::getInstance();
        
        // CORREÇÃO: Removido 'c.state' pois a coluna não existe na tabela cities
        $stmt = $db->getConnection()->prepare("
            SELECT p.*, 
                   u.status as user_status, 
                   c.name as city_name, 
                   co.name as country_name
            FROM profiles p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1 
            LEFT JOIN cities c ON pl.city_id = c.id 
            LEFT JOIN countries co ON c.country_id = co.id
            WHERE p.slug = :slug AND u.status = 'active' 
            LIMIT 1
        ");
        
        $stmt->execute(['slug' => $slug]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) return null;

        try { 
            $db->getConnection()->prepare("UPDATE profiles SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => $profile['id']]); 
        } catch (\Exception $e) {}

        return $profile;
    }
}