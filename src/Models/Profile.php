<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Profile
{
    public static function getById(int $id)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function isOwner(int $userId, int $profileId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT id FROM profiles WHERE id = :pid AND user_id = :uid");
        $stmt->execute(['pid' => $profileId, 'uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function update(int $profileId, array $data)
    {
        $db = Database::getInstance();
        if (empty($data)) {
            return false;
        }
        $setPart = [];
        foreach ($data as $key => $value) {
            $setPart[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE profiles SET " . implode(', ', $setPart) . " WHERE id = :id_pk";
        $data['id_pk'] = $profileId;
        $stmt = $db->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    public static function getBySlug(string $slug)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("
            SELECT p.*, u.status as user_status, c.name as city_name, co.name as country_name
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
        if (!$profile) {
            return null;
        }
        try {
            $db->getConnection()->prepare("UPDATE profiles SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => $profile['id']]);
        } catch (\Exception $e) {
            // Ignore error on view count update
        }
        return $profile;
    }

    // --- LISTAGEM COM HIERARQUIA ESTRITA ---
    public static function getListPublic(array $filters = [], int $limit = 24)
    {
        $db = Database::getInstance();
        $params = [];

        $sql = "SELECT p.id, p.display_name, p.slug, p.gender, p.birth_date, p.current_plan_level, p.profile_image,
                       c.name as city_name, co.name as country_name,
                       (SELECT file_path FROM profile_photos WHERE profile_id = p.id AND is_approved = 1 ORDER BY id ASC LIMIT 1) as cover_photo,

                       (SELECT pt.name
                        FROM subscriptions s2
                        JOIN plan_options po ON s2.plan_id = po.id
                        JOIN plan_types pt ON po.plan_type_id = pt.id
                        WHERE s2.profile_id = p.id
                        AND s2.payment_status = 'paid'
                        AND s2.expires_at > NOW()
                        ORDER BY pt.level DESC LIMIT 1) as plan_name

                FROM profiles p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1
                LEFT JOIN cities c ON pl.city_id = c.id
                LEFT JOIN countries co ON c.country_id = co.id
                WHERE p.status = 'active'
                AND u.status = 'active'
                AND u.role != 'admin'

                AND EXISTS (
                    SELECT 1 FROM subscriptions s
                    WHERE s.profile_id = p.id
                    AND s.payment_status = 'paid'
                    AND s.expires_at > NOW()
                )";

        if (!empty($filters['search'])) {
            $sql .= " AND (p.display_name LIKE :search OR p.bio LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND p.gender = :gender";
            $params['gender'] = $filters['gender'];
        }

        if (!empty($filters['city'])) {
            if (is_numeric($filters['city'])) {
                $sql .= " AND c.id = :city";
                $params['city'] = $filters['city'];
            } else {
                $sql .= " AND c.name LIKE :city";
                $params['city'] = '%' . $filters['city'] . '%';
            }
        }

        // ORDENAÇÃO ESTRITA: VIP (30) > Plus (20) > Premium (10)
        // Note: Using bindValue for LIMIT is safer but requires strict type.
        // For simplicity and compatibility with current logic, keeping concatenation for LIMIT since it's cast to int.

        $sql .= " ORDER BY
                  CASE
                    WHEN (SELECT pt.name FROM subscriptions s2 JOIN plan_options po ON s2.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s2.profile_id = p.id AND s2.payment_status = 'paid' AND s2.expires_at > NOW() ORDER BY pt.level DESC LIMIT 1) LIKE '%VIP%' THEN 30
                    WHEN (SELECT pt.name FROM subscriptions s2 JOIN plan_options po ON s2.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s2.profile_id = p.id AND s2.payment_status = 'paid' AND s2.expires_at > NOW() ORDER BY pt.level DESC LIMIT 1) LIKE '%Plus%' THEN 20
                    WHEN (SELECT pt.name FROM subscriptions s2 JOIN plan_options po ON s2.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s2.profile_id = p.id AND s2.payment_status = 'paid' AND s2.expires_at > NOW() ORDER BY pt.level DESC LIMIT 1) LIKE '%Premium%' THEN 10
                    ELSE 0
                  END DESC,
                  RAND() LIMIT " . (int)$limit;

        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
