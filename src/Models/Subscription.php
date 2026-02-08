<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use DateTime;

class Subscription {

    // Mantivemos o método activate igual (pois ele já grava corretamente no banco)
    public static function activate($profileId, $planOptionId, $transactionId = null, $pricePaid = 0) {
        $db = Database::getInstance();
        
        $stmt = $db->getConnection()->prepare("
            SELECT po.days, po.price, pt.level, pt.id as plan_type_id
            FROM plan_options po
            JOIN plan_types pt ON po.plan_type_id = pt.id
            WHERE po.id = :id
        ");
        $stmt->execute(['id' => $planOptionId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) return false;

        $daysToAdd = (int)$plan['days'];
        $newLevel = (int)$plan['level'];

        // Lógica de Soma e Prioridade (Mantida)
        $stmt = $db->getConnection()->prepare("SELECT id, expires_at FROM subscriptions WHERE profile_id = :pid AND plan_id IN (SELECT id FROM plan_options WHERE plan_type_id = :ptid) AND expires_at > NOW() AND payment_status = 'paid' ORDER BY expires_at DESC LIMIT 1");
        $stmt->execute(['pid' => $profileId, 'ptid' => $plan['plan_type_id']]);
        $currentSameLevel = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->getConnection()->prepare("SELECT MAX(expires_at) as max_expiry FROM subscriptions s JOIN plan_options po ON s.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s.profile_id = :pid AND s.payment_status = 'paid' AND s.expires_at > NOW() AND pt.level > :newLevel");
        $stmt->execute(['pid' => $profileId, 'newLevel' => $newLevel]);
        $higherLevelExpiry = $stmt->fetch(PDO::FETCH_COLUMN);

        $startDate = date('Y-m-d H:i:s');
        if ($currentSameLevel) $startDate = $currentSameLevel['expires_at'];
        elseif ($higherLevelExpiry) $startDate = $higherLevelExpiry;
        
        $expiresAt = date('Y-m-d H:i:s', strtotime("$startDate + $daysToAdd days"));

        if (!$higherLevelExpiry) { 
            $stmt = $db->getConnection()->prepare("SELECT s.id, s.expires_at FROM subscriptions s JOIN plan_options po ON s.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s.profile_id = :pid AND s.payment_status = 'paid' AND s.expires_at > NOW() AND pt.level < :newLevel");
            $stmt->execute(['pid' => $profileId, 'newLevel' => $newLevel]);
            $lowerPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lowerPlans as $lower) {
                $newLowerExpiry = date('Y-m-d H:i:s', strtotime($lower['expires_at'] . " + $daysToAdd days"));
                $db->getConnection()->prepare("UPDATE subscriptions SET expires_at = :exp WHERE id = :id")->execute(['exp' => $newLowerExpiry, 'id' => $lower['id']]);
            }
        }

        $sql = "INSERT INTO subscriptions (profile_id, plan_id, starts_at, expires_at, price_paid, payment_status, transaction_id) VALUES (:pid, :opt, :start, :end, :price, 'paid', :tid)";
        $db->getConnection()->prepare($sql)->execute(['pid' => $profileId, 'opt' => $planOptionId, 'start' => $startDate, 'end' => $expiresAt, 'price' => $pricePaid ?: $plan['price'], 'tid' => $transactionId]);

        self::updateProfileLevel($profileId);
        return true;
    }

    public static function updateProfileLevel($profileId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT pt.level FROM subscriptions s JOIN plan_options po ON s.plan_id = po.id JOIN plan_types pt ON po.plan_type_id = pt.id WHERE s.profile_id = :pid AND s.payment_status = 'paid' AND s.expires_at > NOW() ORDER BY pt.level DESC, s.expires_at DESC LIMIT 1");
        $stmt->execute(['pid' => $profileId]);
        $bestPlan = $stmt->fetch(PDO::FETCH_ASSOC);
        $newLevel = $bestPlan ? $bestPlan['level'] : 0;
        $db->getConnection()->prepare("UPDATE profiles SET current_plan_level = :lvl WHERE id = :pid")->execute(['lvl' => $newLevel, 'pid' => $profileId]);
    }
    
    // --- NOVO MÉTODO DE CONSOLIDAÇÃO (Resolve a visualização) ---
    public static function getConsolidatedList($profileId) {
        $db = Database::getInstance();
        
        // 1. Pega todas as assinaturas brutas
        $stmt = $db->getConnection()->prepare("
            SELECT s.*, pt.name as plan_name, pt.color_hex, pt.level, pt.id as type_id
            FROM subscriptions s
            JOIN plan_options po ON s.plan_id = po.id
            JOIN plan_types pt ON po.plan_type_id = pt.id
            WHERE s.profile_id = :pid 
            AND s.payment_status = 'paid' 
            AND s.expires_at > NOW()
            ORDER BY pt.level DESC, s.expires_at ASC
        ");
        $stmt->execute(['pid' => $profileId]);
        $rawSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $consolidated = [];
        $maxGlobalExpiry = new DateTime(); // Para o total geral

        foreach ($rawSubs as $sub) {
            $typeId = $sub['type_id'];
            $expiry = new DateTime($sub['expires_at']);

            // Atualiza a data máxima global (Total Geral)
            if ($expiry > $maxGlobalExpiry) {
                $maxGlobalExpiry = $expiry;
            }

            // Se já temos esse tipo de plano na lista, apenas atualizamos a data final dele
            if (isset($consolidated[$typeId])) {
                // Se a expiração dessa linha for maior que a que já guardamos, atualiza
                $currentStoredExpiry = new DateTime($consolidated[$typeId]['expires_at']);
                if ($expiry > $currentStoredExpiry) {
                    $consolidated[$typeId]['expires_at'] = $sub['expires_at'];
                }
                // Somamos o preço pago para histórico (opcional)
                $consolidated[$typeId]['total_paid'] += $sub['price_paid'];
            } else {
                // Se não temos, cria a entrada
                $consolidated[$typeId] = [
                    'plan_name' => $sub['plan_name'],
                    'color_hex' => $sub['color_hex'],
                    'level'     => $sub['level'],
                    'expires_at'=> $sub['expires_at'],
                    'total_paid'=> $sub['price_paid']
                ];
            }
        }

        // Calcula dias restantes para cada grupo e formata
        $now = new DateTime();
        foreach ($consolidated as $key => $data) {
            $exp = new DateTime($data['expires_at']);
            
            // Lógica de Dias Exatos: Se a diferença for 29 dias e 1 hora, conta como 30 dias (ceil)
            // Usamos diff absoluto e verificamos se está no futuro
            if ($exp > $now) {
                $diff = $now->diff($exp);
                $days = $diff->days;
                if ($diff->h > 0 || $diff->i > 0) {
                    $days++; // Arredonda pra cima se tiver horas sobrando
                }
                $consolidated[$key]['days_left'] = $days;
            } else {
                $consolidated[$key]['days_left'] = 0;
            }
        }

        // Calcula total geral de dias (Data Final Absoluta - Agora)
        $totalDays = 0;
        if ($maxGlobalExpiry > $now) {
            $diffTotal = $now->diff($maxGlobalExpiry);
            $totalDays = $diffTotal->days;
            if ($diffTotal->h > 0) $totalDays++;
        }

        // Reordena por nível (VIP primeiro)
        usort($consolidated, function($a, $b) {
            return $b['level'] <=> $a['level'];
        });

        return [
            'plans' => $consolidated,
            'total_days' => $totalDays
        ];
    }
}