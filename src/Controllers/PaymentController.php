<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class PaymentController extends Controller {

    // 1. Exibe a tela de Planos e Preços
    public function plans() {
        $this->checkAuth();
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Busca preço do Slot
        $stmtSlot = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
        $slotPrice = $stmtSlot->fetchColumn() ?: 49.90;

        // Busca Planos e Opções
        $stmtTypes = $conn->query("SELECT * FROM plan_types ORDER BY level ASC");
        $types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

        $stmtOptions = $conn->query("SELECT * FROM plan_options ORDER BY days ASC");
        $options = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);

        $structuredPlans = [];
        foreach ($types as $type) {
            if (!empty($type['benefits_json'])) {
                $type['benefits'] = json_decode($type['benefits_json'], true);
            } elseif (!empty($type['benefits'])) {
                $decoded = json_decode($type['benefits'], true);
                $type['benefits'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : explode("\n", $type['benefits']);
            } else {
                $type['benefits'] = [];
            }

            $type['options'] = [];
            foreach ($options as $opt) {
                if ($opt['plan_type_id'] == $type['id']) {
                    $type['options'][] = $opt;
                }
            }
            if (!empty($type['options'])) $structuredPlans[] = $type;
        }
        
        $this->view('model/plans', ['plans' => $structuredPlans, 'slotPrice' => $slotPrice]);
    }

    // 2. Processa o Checkout
    public function checkout() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/planos'); return; }

        $userId = $_SESSION['user_id'];
        $type = $_POST['type'] ?? 'vip';
        $db = Database::getInstance();
        $conn = $db->getConnection();

        try {
            if ($type === 'slot') {
                $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
                $amount = $stmt->fetchColumn() ?: 49.90;
                $sql = "INSERT INTO transactions (user_id, type, amount, status, created_at) VALUES (?, 'slot', ?, 'pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$userId, $amount]);
                $this->redirect('/payment/success?ref=' . $conn->lastInsertId());
                return;
            }

            if ($type === 'vip') {
                $planOptionId = $_POST['plan_option_id'] ?? null;
                $profileId = $_POST['profile_id'] ?? null;

                if (!$planOptionId || !$profileId) { $this->redirect('/planos?error=missing_data'); return; }

                $stmt = $conn->prepare("SELECT price FROM plan_options WHERE id = ?");
                $stmt->execute([$planOptionId]);
                $amount = $stmt->fetchColumn();

                if (!$amount) { $this->redirect('/planos?error=invalid_option'); return; }

                $sql = "INSERT INTO transactions (user_id, profile_id, type, reference_id, amount, status, created_at) VALUES (?, ?, 'vip', ?, ?, 'pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$userId, $profileId, $planOptionId, $amount]);
                $this->redirect('/payment/success?ref=' . $conn->lastInsertId());
                return;
            }
        } catch (\Exception $e) {
            $this->redirect('/planos?error=internal_error');
        }
    }

    // 3. Simulação de Sucesso (CORRIGIDO REDIRECIONAMENTO E DADOS)
    public function mockSuccess() {
        $this->checkAuth();
        $transId = $_GET['ref'] ?? null;

        if (!$transId) die("Referência inválida.");

        if ($this->approveTransaction($transId)) {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Busca dados da transação para saber para onde redirecionar
            $stmt = $conn->prepare("SELECT type, profile_id, reference_id FROM transactions WHERE id = ?");
            $stmt->execute([$transId]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($trans['type'] === 'slot') {
                $this->redirect('/perfil/criar?msg=slot_purchased');
            } else {
                // --- CORREÇÃO AQUI ---
                // 1. Busca detalhes do plano para mostrar no feedback
                $stmtPlan = $conn->prepare("SELECT pt.name as plan_name, po.days FROM plan_options po JOIN plan_types pt ON po.plan_type_id = pt.id WHERE po.id = ?");
                $stmtPlan->execute([$trans['reference_id']]);
                $planDetails = $stmtPlan->fetch(PDO::FETCH_ASSOC);
                
                $planName = urlencode($planDetails['plan_name'] ?? 'VIP');
                $days = $planDetails['days'] ?? 30;

                // 2. Redireciona para /perfil/editar com os dados
                $this->redirect('/perfil/editar?profile_id=' . $trans['profile_id'] . '&msg=vip_success&plan_name=' . $planName . '&days=' . $days);
            }
        } else {
            die("Erro ao processar ativação.");
        }
    }

    // 4. Lógica Central de Aprovação
    public function approveTransaction($transId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transId]);
        $trans = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trans || $trans['status'] === 'paid') return true;

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transId]);

            if ($trans['type'] === 'slot') {
                $stmt = $conn->prepare("UPDATE users SET max_profile_slots = max_profile_slots + 1 WHERE id = ?");
                $stmt->execute([$trans['user_id']]);
            
            } elseif ($trans['type'] === 'vip') {
                // Usa seu Model Subscription original para ativar
                \App\Models\Subscription::activate($trans['profile_id'], $trans['reference_id'], 'MOCK_'.$transId, $trans['amount']);
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }
}