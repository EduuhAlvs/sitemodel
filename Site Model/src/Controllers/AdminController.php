<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile; // Vamos precisar criar métodos de contagem depois
use App\Core\Database;
use PDO;

class AdminController extends Controller {

    // Método construtor para bloquear acesso não-admin
    // (Como seu framework é simples, vamos fazer a checagem em cada método por segurança)
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Verifica no banco se é admin mesmo (não confie só na sessão antiga)
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'admin') {
            // Se tentar entrar e não for admin, manda pra home
            $this->redirect('/');
            exit;
        }
    }

    public function index() {
        $this->checkAuth();
        $db = Database::getInstance();

        // 1. Estatísticas Reais
        // Total de Usuários
        $totalUsers = $db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
        
        // Total de Modelos (Perfis)
        $totalModels = $db->query("SELECT COUNT(*) as c FROM profiles")->fetch()['c'];
        
        // Faturamento Total (Soma das assinaturas pagas)
        $revenue = $db->query("SELECT SUM(price_paid) as s FROM subscriptions WHERE payment_status = 'paid'")->fetch()['s'] ?? 0;

        // Fotos Pendentes de Aprovação
        $pendingPhotos = $db->query("SELECT COUNT(*) as c FROM profile_photos WHERE is_approved = 0")->fetch()['c'];

        // Últimos Usuários Cadastrados
        $latestUsers = $db->query("SELECT id, email, created_at, role FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/dashboard', [
            'totalUsers' => $totalUsers,
            'totalModels' => $totalModels,
            'revenue' => $revenue,
            'pendingPhotos' => $pendingPhotos,
            'latestUsers' => $latestUsers
        ]);
    }

    // Lista fotos pendentes (is_approved = 0)
    public function photos() {
        $this->checkAuth();
        $db = Database::getInstance();

        // Busca fotos pendentes junto com o nome da modelo
        $stmt = $db->getConnection()->query("
            SELECT pp.*, p.display_name, u.email 
            FROM profile_photos pp
            JOIN profiles p ON pp.profile_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE pp.is_approved = 0
            ORDER BY pp.created_at ASC
        ");
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/photos', [
            'photos' => $photos
        ]);
    }

    // Ação: Aprovar Foto
    public function approvePhoto() {
        $this->checkAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        if ($id) {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("UPDATE profile_photos SET is_approved = 1 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // Ação: Rejeitar (Excluir) Foto
    public function rejectPhoto() {
        $this->checkAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        if ($id) {
            $db = Database::getInstance();
            
            // 1. Pega o caminho do arquivo para deletar do disco
            $stmt = $db->getConnection()->prepare("SELECT file_path FROM profile_photos WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $photo = $stmt->fetch();

            if ($photo) {
                // Tenta apagar o arquivo físico
                $filepath = __DIR__ . '/../../public/' . $photo['file_path'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }

                // 2. Apaga do banco de dados
                $db->getConnection()->prepare("DELETE FROM profile_photos WHERE id = :id")->execute(['id' => $id]);
                echo json_encode(['success' => true]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // --- GESTÃO DE USUÁRIOS ---

    // Lista todos os usuários
    public function users() {
        $this->checkAuth();
        $db = Database::getInstance();

        // Busca usuários e conta quantos perfis/fotos eles têm
        // Também formata a data para ficar bonita
        $sql = "SELECT u.*, 
                (SELECT COUNT(*) FROM profiles WHERE user_id = u.id) as has_profile,
                (SELECT display_name FROM profiles WHERE user_id = u.id LIMIT 1) as model_name
                FROM users u 
                ORDER BY u.created_at DESC";
        
        $users = $db->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/users', [
            'users' => $users
        ]);
    }

    // Ação: Banir / Desbanir
    public function toggleBan() {
        $this->checkAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        if ($id) {
            $db = Database::getInstance();
            
            // Verifica status atual
            $curr = $db->getConnection()->query("SELECT status FROM users WHERE id = $id")->fetch();
            $newStatus = ($curr['status'] === 'banned') ? 'active' : 'banned';

            // Atualiza
            $stmt = $db->getConnection()->prepare("UPDATE users SET status = :st WHERE id = :id");
            $stmt->execute(['st' => $newStatus, 'id' => $id]);

            echo json_encode(['success' => true, 'new_status' => $newStatus]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

}