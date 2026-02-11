<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use Exception;
use Throwable;

class ReviewsController extends Controller
{
    // CRIAR AVALIAÇÃO (Apenas Membros)
    public function create()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        try {
            // 1. Verifica se está logado
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Você precisa estar logado.');
            }

            // 2. Verifica se é Membro (Modelos não podem se auto-avaliar ou avaliar outras)
            // Nota: Ajuste conforme como você salvou na sessão ('member' ou 'user')
            // Se no seu banco members são 'user', ajuste aqui.
            $role = $_SESSION['user_role'] ?? '';
            if ($role === 'model') {
                throw new Exception('Modelos não podem enviar avaliações.');
            }

            // 3. Pega os dados
            $input = json_decode(file_get_contents('php://input'), true);
            $profileId = $input['profile_id'] ?? null;
            $rating = (int) ($input['rating'] ?? 5);
            $comment = trim($input['comment'] ?? '');

            // Nome do avaliador (vem da sessão que arrumamos no AuthController)
            $reviewerName = $_SESSION['user_name'] ?? 'Membro Anônimo';

            if (!$profileId || empty($comment)) {
                throw new Exception('Escreva um comentário.');
            }

            $db = Database::getInstance();

            // 4. Salva no Banco
            // Atenção: Certifique-se que sua tabela profile_reviews tem a coluna reviewer_id
            $sql = "INSERT INTO profile_reviews (profile_id, reviewer_id, reviewer_name, rating, comment, created_at)
                    VALUES (:pid, :uid, :name, :rating, :comment, NOW())";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([
                'pid' => $profileId,
                'uid' => $_SESSION['user_id'],
                'name' => $reviewerName,
                'rating' => $rating,
                'comment' => $comment
            ]);

            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // RESPONDER AVALIAÇÃO (Apenas a Modelo dona do perfil)
    public function reply()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Login necessário.');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $reviewId = $input['review_id'] ?? null;
            $replyText = trim($input['reply'] ?? '');
            $profileId = $input['profile_id'] ?? null;

            if (!$reviewId || empty($replyText)) {
                throw new Exception('Resposta vazia.');
            }

            $db = Database::getInstance();

            // Verifica se o perfil pertence ao usuário logado antes de salvar
            // (Segurança básica para ninguém responder pelo outro)
            $check = $db->getConnection()->prepare("SELECT id FROM profiles WHERE id = ? AND user_id = ?");
            $check->execute([$profileId, $_SESSION['user_id']]);
            if ($check->rowCount() === 0) {
                throw new Exception('Você não tem permissão para responder neste perfil.');
            }

            $stmt = $db->getConnection()->prepare("UPDATE profile_reviews SET reply = :reply WHERE id = :rid");
            $stmt->execute(['reply' => $replyText, 'rid' => $reviewId]);

            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // APAGAR AVALIAÇÃO (Modelo ou Admin)
    public function delete()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Acesso negado.');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $reviewId = $input['review_id'] ?? null;
            $profileId = $input['profile_id'] ?? null;

            $db = Database::getInstance();

            // Verifica permissão (se é dono do perfil)
            $check = $db->getConnection()->prepare("SELECT id FROM profiles WHERE id = ? AND user_id = ?");
            $check->execute([$profileId, $_SESSION['user_id']]);

            // Se não for dono e não for admin, bloqueia
            if ($check->rowCount() === 0 && ($_SESSION['user_role'] ?? '') !== 'admin') {
                throw new Exception('Sem permissão.');
            }

            $stmt = $db->getConnection()->prepare("DELETE FROM profile_reviews WHERE id = :rid");
            $stmt->execute(['rid' => $reviewId]);

            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
