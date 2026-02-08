<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Controllers\PaymentController; // Reusa a lógica de aprovação

class WebhookController extends Controller {

    public function handle() {
        // 1. Recebe JSON
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$data) {
            http_response_code(400); 
            exit;
        }

        // 2. Verifica Status (Exemplo genérico, adapte para Unlimit/Stripe)
        $status = $data['payment_data']['status'] ?? $data['status'] ?? '';
        
        // ID da nossa transação que enviamos para o gateway
        // Geralmente enviamos o ID da tabela 'transactions' como 'merchant_order_id' ou 'reference'
        $transactionId = $data['merchant_order']['id'] ?? $data['tracking_id'] ?? $data['reference_id'] ?? null;

        if (($status === 'COMPLETED' || $status === 'PAID') && $transactionId) {
            
            // Instancia o PaymentController para usar a lógica centralizada
            $paymentCtrl = new PaymentController();
            
            // Tenta aprovar
            if ($paymentCtrl->approveTransaction($transactionId)) {
                http_response_code(200);
                echo "Webhook Processed: Transaction $transactionId approved.";
                exit;
            }
        }

        // Retorna 200 mesmo se falhar logica interna para gateway não reenviar
        http_response_code(200);
        exit;
    }
}