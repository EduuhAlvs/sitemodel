<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class PaymentController extends Controller {

    // 1. Exibe a tela de Planos e Preços
    public function plans() {
        $this->checkAuth(); // Garante que está logado
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // A. Busca preço do Slot (Vaga Extra)
        $stmtSlot = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
        $slotPrice = $stmtSlot->fetchColumn() ?: 49.90;

        // B. Busca Planos e Opções (VIP)
        $stmtTypes = $conn->query("SELECT * FROM plan_types ORDER BY level ASC");
        $types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

        $stmtOptions = $conn->query("SELECT * FROM plan_options ORDER BY days ASC");
        $options = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);

        // C. Monta a estrutura para a View
        $structuredPlans = [];
        foreach ($types as $type) {
            // Decodifica benefícios (JSON ou Texto)
            if (!empty($type['benefits_json'])) {
                $type['benefits'] = json_decode($type['benefits_json'], true);
            } elseif (!empty($type['benefits'])) {
                // Fallback para versões antigas
                $decoded = json_decode($type['benefits'], true);
                $type['benefits'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : explode("\n", $type['benefits']);
            } else {
                $type['benefits'] = [];
            }

            // Aninha opções de pagamento (dias/preço)
            $type['options'] = [];
            foreach ($options as $opt) {
                if ($opt['plan_type_id'] == $type['id']) {
                    $type['options'][] = $opt;
                }
            }

            if (!empty($type['options'])) {
                $structuredPlans[] = $type;
            }
        }
        
        // Passa tudo para a view (planos e preço do slot)
        $this->view('model/plans', [
            'plans' => $structuredPlans,
            'slotPrice' => $slotPrice
        ]);
    }

    // 2. Processa o Checkout (Cria Transação Pendente)
    public function checkout() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/planos');
            return;
        }

        $userId = $_SESSION['user_id'];
        $type = $_POST['type'] ?? 'vip'; // 'vip' ou 'slot'
        $db = Database::getInstance();
        $conn = $db->getConnection();

        try {
            // --- CENÁRIO 1: COMPRA DE SLOT (VAGA) ---
            if ($type === 'slot') {
                // Busca preço atual
                $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
                $amount = $stmt->fetchColumn() ?: 49.90;

                // Cria transação na tabela nova
                $sql = "INSERT INTO transactions (user_id, type, amount, status, created_at) VALUES (?, 'slot', ?, 'pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$userId, $amount]);
                $transId = $conn->lastInsertId();

                // Redireciona para sucesso simulado (Mude aqui para seu Gateway Real no futuro)
                $this->redirect('/payment/success?ref=' . $transId);
                return;
            }

            // --- CENÁRIO 2: COMPRA DE PLANO VIP ---
            if ($type === 'vip') {
                $planOptionId = $_POST['plan_option_id'] ?? null;
                $profileId = $_POST['profile_id'] ?? null;

                if (!$planOptionId || !$profileId) {
                    $this->redirect('/planos?error=missing_data');
                    return;
                }

                // Valida Opção
                $stmt = $conn->prepare("SELECT price FROM plan_options WHERE id = ?");
                $stmt->execute([$planOptionId]);
                $amount = $stmt->fetchColumn();

                if (!$amount) {
                    $this->redirect('/planos?error=invalid_option');
                    return;
                }

                // Cria transação vinculada ao perfil
                $sql = "INSERT INTO transactions (user_id, profile_id, type, reference_id, amount, status, created_at) 
                        VALUES (?, ?, 'vip', ?, ?, 'pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$userId, $profileId, $planOptionId, $amount]);
                $transId = $conn->lastInsertId();

                // Redireciona para sucesso simulado
                $this->redirect('/payment/success?ref=' . $transId);
                return;
            }

        } catch (\Exception $e) {
            error_log("Erro no checkout: " . $e->getMessage());
            $this->redirect('/planos?error=internal_error');
        }
    }

    // 3. Simulação de Sucesso (Mock)
    // Em produção, isso seria substituído pelo retorno do Gateway
    public function mockSuccess() {
        $this->checkAuth();
        $transId = $_GET['ref'] ?? null;

        if (!$transId) die("Referência inválida.");

        // Chama o método centralizado de aprovação
        if ($this->approveTransaction($transId)) {
            // Verifica o tipo para redirecionar corretamente
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("SELECT type, profile_id FROM transactions WHERE id = ?");
            $stmt->execute([$transId]);
            $trans = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($trans['type'] === 'slot') {
                $this->redirect('/perfil/criar?msg=slot_purchased');
            } else {
                $this->redirect('/painel/dashboard?profile_id=' . $trans['profile_id'] . '&msg=vip_success');
            }
        } else {
            die("Erro ao processar ativação.");
        }
    }

    // 4. LÓGICA CENTRAL DE APROVAÇÃO (Usado pelo Mock e pelo Webhook)
    public function approveTransaction($transId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Busca transação
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transId]);
        $trans = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trans || $trans['status'] === 'paid') return true; // Já processado

        try {
            $conn->beginTransaction();

            // A. Atualiza status da transação
            $stmt = $conn->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transId]);

            // B. Entrega o Produto
            if ($trans['type'] === 'slot') {
                // Aumenta slot do usuário
                $stmt = $conn->prepare("UPDATE users SET max_profile_slots = max_profile_slots + 1 WHERE id = ?");
                $stmt->execute([$trans['user_id']]);
            
            } elseif ($trans['type'] === 'vip') {
                // Busca detalhes do plano comprado
                $stmt = $conn->prepare("SELECT days, plan_type_id FROM plan_options WHERE id = ?");
                $stmt->execute([$trans['reference_id']]);
                $optionData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($optionData) {
                    $days = $optionData['days'];
                    $planTypeId = $optionData['plan_type_id'];

                    // Atualiza Perfil (Level e Score)
                    $stmt = $conn->prepare("UPDATE profiles SET current_plan_level = ?, total_investment = total_investment + ? WHERE id = ?");
                    // Nota: Aqui pegamos o level do plan_types. Como atalho, usarei planTypeId se for igual ao level,
                    // mas o ideal é buscar o 'level' da tabela plan_types.
                    $stmtLevel = $conn->prepare("SELECT level, name FROM plan_types WHERE id = ?");
                    $stmtLevel->execute([$planTypeId]);
                    $planTypeData = $stmtLevel->fetch(PDO::FETCH_ASSOC);

                    $stmt->execute([$planTypeData['level'], $trans['amount'], $trans['profile_id']]);

                    // Cria/Renova Assinatura na tabela subscriptions
                    // Verifica se já tem ativa
                    $stmtSub = $conn->prepare("SELECT id, expires_at FROM subscriptions WHERE profile_id = ? AND plan_id = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
                    // Note: sua tabela subscriptions usa 'plan_id' como FK para plan_types ou plan_options? 
                    // Baseado no seu SQL, parece ser plan_options (price_paid bate). Vamos usar plan_options (reference_id).
                    $stmtSub->execute([$trans['profile_id'], $trans['reference_id']]); 
                    $existing = $stmtSub->fetch(PDO::FETCH_ASSOC);

                    $newStart = date('Y-m-d H:i:s');
                    $newExpire = date('Y-m-d H:i:s', strtotime("+$days days"));

                    if ($existing) {
                        // Extende
                        $currentExp = strtotime($existing['expires_at']);
                        if ($currentExp > time()) {
                            $newExpire = date('Y-m-d H:i:s', strtotime("+$days days", $currentExp));
                        }
                    }

                    // Insere na tabela subscriptions (Histórico ativo)
                    $sqlSub = "INSERT INTO subscriptions (profile_id, plan_id, starts_at, expires_at, price_paid, payment_status, created_at) 
                               VALUES (?, ?, ?, ?, ?, 'paid', NOW())";
                    $stmt = $conn->prepare($sqlSub);
                    $stmt->execute([$trans['profile_id'], $trans['reference_id'], $newStart, $newExpire, $trans['amount']]);
                }
            }

            $conn->commit();
            return true;

        } catch (\Exception $e) {
            $conn->rollBack();
            error_log("Erro ao aprovar transação $transId: " . $e->getMessage());
            return false;
        }
    }
}