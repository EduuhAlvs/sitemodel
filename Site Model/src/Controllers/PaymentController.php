<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile;
use App\Models\Plan;
use App\Models\Database; // Apenas se precisar query direta
use App\Models\Subscription;

class PaymentController extends Controller {

    // No método plans():
public function plans() {
    if (!isset($_SESSION['user_id'])) $this->redirect('/login');

    $userId = $_SESSION['user_id'];
    $profile = Profile::getByUserId($userId);
    
    // Busca estruturado (Tipos -> Opções)
    $plans = Plan::getAllStructured(); 

    $this->view('model/plans', [
        'profile' => $profile,
        'plans' => $plans
    ]);
}

// No método checkout():
public function checkout() {
    header('Content-Type: application/json');
    // ... validação de login ...

    $input = json_decode(file_get_contents('php://input'), true);
    $optionId = $input['option_id'] ?? 0; // Mudou de plan_id para option_id
    
    $option = Plan::getOptionById($optionId);
    
    if (!$option) {
        echo json_encode(['success' => false, 'message' => 'Opção inválida']);
        return;
    }

    $userId = $_SESSION['user_id'];
    $profile = Profile::getByUserId($userId);

    $subId = Plan::createSubscription($profile['id'], $option['id'], $option['price']);

    // URL Simulada
    $paymentUrl = url("/payment/mock-success?sub_id={$subId}"); 

    echo json_encode(['success' => true, 'redirect_url' => $paymentUrl]);
}

    // Simula o Retorno de Sucesso do Pagamento
    public function mockSuccess() {
        $subId = $_GET['sub_id'] ?? 0;
        
        if ($subId) {
            // Tenta ativar
            $success = \App\Models\Subscription::activate($subId); // Use o namespace completo se der erro
            
            if ($success) {
                // Sucesso! Redireciona.
                $this->redirect('/perfil/editar?payment=success');
            } else {
                // Erro: Mostra na tela para debug
                echo "<h1>Erro na Ativação</h1>";
                echo "<p>Não foi possível ativar a assinatura #$subId.</p>";
                echo "<p>Motivos possíveis:</p><ul>";
                echo "<li>Assinatura não existe no banco.</li>";
                echo "<li>Assinatura já foi marcada como 'paid' antes.</li>";
                echo "<li>O ID do plano (plan_id) na tabela subscriptions não bate com a tabela plan_options.</li>";
                echo "</ul>";
                echo "<a href='/perfil/editar'>Voltar</a>";
            }
        } else {
            $this->redirect('/perfil/editar');
        }
    }
}