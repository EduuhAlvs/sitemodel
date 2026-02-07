<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subscription;

class WebhookController extends Controller {

    // URL que você vai cadastrar no painel da Unlimit:
    // https://seusite.com/api/webhooks/unlimit
    public function handle() {
        // 1. Recebe o JSON enviado pela Unlimit
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        // Log para debug (opcional, bom para ver o que chega)
        // file_put_contents(__DIR__ . '/../../webhook_log.txt', $payload . PHP_EOL, FILE_APPEND);

        if (!$data) {
            http_response_code(400); // Bad Request
            exit;
        }

        // 2. Verifica o status do pagamento
        // A Unlimit geralmente manda algo como 'status': 'COMPLETED' ou 'authorized'
        // VERIFIQUE A DOCUMENTAÇÃO DA UNLIMIT PARA O NOME EXATO DO CAMPO
        // Vou assumir que o status de sucesso é 'COMPLETED'
        $status = $data['payment_data']['status'] ?? $data['status'] ?? '';

        if ($status === 'COMPLETED' || $status === 'PAID') {
            
            // 3. Pega o ID da assinatura
            // Quando criamos o pagamento, enviamos o ID da assinatura como 'merchant_order_id' ou 'tracking_id'
            $subscriptionId = $data['merchant_order']['id'] ?? $data['tracking_id'] ?? null;

            if ($subscriptionId) {
                // 4. ATIVA O VIP
                $success = Subscription::activate((int)$subscriptionId);
                
                if ($success) {
                    http_response_code(200); // OK, recebido e processado
                    echo "Webhook processed: VIP Activated.";
                    exit;
                }
            }
        }

        // Se não for sucesso ou não tiver ID, apenas retorna 200 para a Unlimit não ficar tentando reenviar
        http_response_code(200);
        exit;
    }
}