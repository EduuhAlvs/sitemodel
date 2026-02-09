<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class MemberController extends Controller {
    
    public function dashboard() {
        // 1. Verifica Login
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('/login'));
            exit;
        }

        // 2. Se for modelo, redireciona (Segurança de Hierarquia)
        if (($_SESSION['user_role'] ?? '') === 'model') {
            header('Location: ' . url('/perfil/editar'));
            exit;
        }

        // 3. Busca dados unificados (User + Profile)
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("
            SELECT u.*, p.display_name, p.birth_date
            FROM users u
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Renderiza a view passando os dados completos
        $this->view('member/dashboard', ['user' => $user]);
    }
}