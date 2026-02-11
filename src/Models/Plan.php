<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Plan
{
    // Busca todos os Tipos com suas Opções aninhadas
    public static function getAllStructured()
    {
        $db = Database::getInstance();

        // 1. Busca Tipos
        $stmtTypes = $db->query("SELECT * FROM plan_types ORDER BY level ASC");
        $types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

        // 2. Para cada tipo, busca as opções
        foreach ($types as &$type) {
            $stmtOptions = $db->getConnection()->prepare("SELECT * FROM plan_options WHERE plan_type_id = :pid ORDER BY days ASC");
            $stmtOptions->execute(['pid' => $type['id']]);
            $type['options'] = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);

            // Decodifica JSON de beneficios
            $type['benefits'] = json_decode($type['benefits_json'] ?? '[]', true);
        }

        return $types;
    }

    // Busca uma opção específica pelo ID (para o checkout)
    public static function getOptionById(int $optionId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("
            SELECT po.*, pt.name as plan_name, pt.level
            FROM plan_options po
            JOIN plan_types pt ON po.plan_type_id = pt.id
            WHERE po.id = :id
        ");
        $stmt->execute(['id' => $optionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cria Assinatura (Mantive igual, mas agora recebe option_id)
    public static function createSubscription(int $profileId, int $optionId, float $price)
    {
        $db = Database::getInstance();

        // Busca quantos dias tem essa opção
        $stmtOpt = $db->getConnection()->prepare("SELECT days FROM plan_options WHERE id = :id");
        $stmtOpt->execute(['id' => $optionId]);
        $opt = $stmtOpt->fetch();
        $days = $opt['days'];

        $sql = "INSERT INTO subscriptions (profile_id, plan_id, starts_at, expires_at, price_paid, payment_status, created_at)
                VALUES (:pid, :optId, NOW(), DATE_ADD(NOW(), INTERVAL :days DAY), :price, 'pending', NOW())";

        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'pid' => $profileId,
            'optId' => $optionId, // Agora salvamos o ID da opção específica
            'days' => $days,
            'price' => $price
        ]);

        return $conn->lastInsertId();
    }
}
