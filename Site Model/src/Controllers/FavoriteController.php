<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class FavoriteController extends Controller {

    // API: Alternar Favorito (Like/Dislike)
    public function toggle() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Login necessário']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $profileId = $input['profile_id'] ?? 0;
        $userId = $_SESSION['user_id'];

        if (!$profileId) return;

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Verifica se já existe
        $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = :uid AND profile_id = :pid");
        $check->execute(['uid' => $userId, 'pid' => $profileId]);
        
        if ($check->rowCount() > 0) {
            // Se existe, REMOVE (Deslike)
            $conn->prepare("DELETE FROM favorites WHERE user_id = :uid AND profile_id = :pid")->execute(['uid' => $userId, 'pid' => $profileId]);
            $isFavorited = false;
        } else {
            // Se não existe, ADICIONA (Like)
            $conn->prepare("INSERT INTO favorites (user_id, profile_id) VALUES (:uid, :pid)")->execute(['uid' => $userId, 'pid' => $profileId]);
            $isFavorited = true;
        }

        echo json_encode(['success' => true, 'is_favorited' => $isFavorited]);
    }

    // Página: Meus Favoritos (Dashboard do Cliente)
    public function index() {
        if (!isset($_SESSION['user_id'])) $this->redirect('/login');

        $userId = $_SESSION['user_id'];
        $db = Database::getInstance();

        // Busca os perfis que o usuário favoritou
        $stmt = $db->getConnection()->prepare("
            SELECT p.*, 
            (SELECT file_path FROM profile_photos WHERE profile_id = p.id LIMIT 1) as cover_photo,
            c.name as city_name
            FROM favorites f
            JOIN profiles p ON f.profile_id = p.id
            LEFT JOIN profile_locations pl ON p.id = pl.profile_id AND pl.is_base_city = 1
            LEFT JOIN cities c ON pl.city_id = c.id
            WHERE f.user_id = :uid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('member/favorites', [
            'favorites' => $favorites
        ]);
    }
}