<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Profile {
    // Busca o perfil pelo ID do Usuário (Logado)
    public static function getByUserId(int $userId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cria um perfil vazio (Rascunho) assim que a modelo entra na área dela
    public static function createDraft(int $userId, string $email) {
        $db = Database::getInstance();
        
        // Gera um slug temporário baseado no email (depois ela muda)
        $slug = preg_replace('/[^a-z0-9]/', '', strtolower(explode('@', $email)[0])) . '-' . uniqid();
        
        $sql = "INSERT INTO profiles (user_id, slug, display_name, phone, gender, birth_date) 
                VALUES (:uid, :slug, 'Modelo Nova', '', 'woman', '2000-01-01')";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute(['uid' => $userId, 'slug' => $slug]);
        return $db->getConnection()->lastInsertId();
    }

    // Método Genérico para Atualizar Campos
    // Ex: Profile::update(1, ['hair_color' => 'Loira', 'eye_color' => 'Azul'])
    public static function update(int $profileId, array $data) {
        $db = Database::getInstance();
        
        // Monta a query dinamicamente baseada nos campos recebidos
        $setPart = [];
        foreach ($data as $key => $value) {
            $setPart[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE profiles SET " . implode(', ', $setPart) . " WHERE id = :id";
        
        $data['id'] = $profileId;
        
        $stmt = $db->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    
    // Busca perfis para a Home (Público)
    public static function getListPublic(int $limit = 20, array $filters = []) {
        $db = Database::getInstance();
        
        // CORREÇÃO: Removida a linha 'c.state as city_state' que causava o erro
        $sql = "SELECT p.*, 
                (SELECT file_path FROM profile_photos WHERE profile_id = p.id LIMIT 1) as cover_photo,
                c.name as city_name,
                u.status
                FROM profiles p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1
                LEFT JOIN cities c ON pl.city_id = c.id
                WHERE u.status = 'active' 
                AND u.role = 'user' 
        ";

        $params = [];

        // 1. Filtro por Gênero
        if (!empty($filters['gender'])) {
            $sql .= " AND p.gender = :gender";
            $params['gender'] = $filters['gender'];
        }
        
        // 2. Filtro por Cidade
        if (!empty($filters['city'])) {
            $sql .= " AND c.slug = :city";
            $params['city'] = $filters['city'];
        }

        // 3. Busca por Texto
        if (!empty($filters['search'])) {
            $sql .= " AND (p.display_name LIKE :search OR p.bio LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY p.ranking_score DESC, RAND() LIMIT " . (int)$limit;

        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca Perfil Completo pelo Slug (Público)
    public static function getBySlug(string $slug) {
        $db = Database::getInstance();
        
        // 1. Dados Principais
        $stmt = $db->getConnection()->prepare("
            SELECT p.*, u.status, c.name as city_name 
            FROM profiles p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1
            LEFT JOIN cities c ON pl.city_id = c.id
            WHERE p.slug = :slug AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute(['slug' => $slug]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) return null;

        // 2. Incrementa Visualizações (Contador)
        $db->getConnection()->prepare("UPDATE profiles SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => $profile['id']]);

        return $profile;
    }
}

