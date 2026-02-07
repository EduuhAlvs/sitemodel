<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Subscription {
    
    public static function activate(int $subscriptionId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // 1. Busca a assinatura e valida
        $stmtSub = $conn->prepare("SELECT * FROM subscriptions WHERE id = :id");
        $stmtSub->execute(['id' => $subscriptionId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        if (!$sub || $sub['payment_status'] === 'paid') {
            return false; 
        }

        // 2. Busca detalhes do plano (Dias e Nível)
        $stmtPlan = $conn->prepare("
            SELECT po.days, pt.level 
            FROM plan_options po
            JOIN plan_types pt ON po.plan_type_id = pt.id
            WHERE po.id = :optId
        ");
        $stmtPlan->execute(['optId' => $sub['plan_id']]);
        $planDetails = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if (!$planDetails) {
            return false;
        }

        try {
            $conn->beginTransaction();

            // 3. Atualiza status para PAGO
            // Agora vai funcionar porque criamos a coluna updated_at
            $updateSub = $conn->prepare("
                UPDATE subscriptions 
                SET payment_status = 'paid', 
                    starts_at = NOW(), 
                    expires_at = DATE_ADD(NOW(), INTERVAL :days DAY),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateSub->execute([
                'days' => $planDetails['days'], 
                'id' => $subscriptionId
            ]);

            // 4. Atualiza o PERFIL (Sobe o Score e Nível)
            $newScore = (int)$planDetails['level'] * 1000;
            
            $updateProfile = $conn->prepare("
                UPDATE profiles 
                SET ranking_score = :score,
                    current_plan_level = :level
                WHERE id = :pid
            ");
            $updateProfile->execute([
                'score' => $newScore,
                'level' => $planDetails['level'],
                'pid'   => $sub['profile_id']
            ]);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            // Em produção, registre o erro em log: error_log($e->getMessage());
            return false;
        }
    }
    
    // Busca a assinatura ativa (Mantive igual)
    public static function getActiveByProfile(int $profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("
            SELECT s.*, pt.name as plan_name, pt.level, pt.color_hex 
            FROM subscriptions s
            JOIN plan_options po ON s.plan_id = po.id
            JOIN plan_types pt ON po.plan_type_id = pt.id
            WHERE s.profile_id = :pid 
              AND s.payment_status = 'paid' 
              AND s.expires_at > NOW()
            ORDER BY s.expires_at DESC
            LIMIT 1
        ");
        $stmt->execute(['pid' => $profileId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}