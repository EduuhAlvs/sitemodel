<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class AuthController extends Controller {

    // ==================================================================
    // 1. TELAS (GET)
    // ==================================================================

    public function login() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectBasedOnRole($_SESSION['user_id']);
            return;
        }
        $this->view('auth/login');
    }

    public function register() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectBasedOnRole($_SESSION['user_id']);
            return;
        }
        $this->view('auth/register');
    }

    // ==================================================================
    // 2. AÇÕES (POST)
    // ==================================================================

    public function loginAction() {
        // Limpa buffer para garantir JSON limpo
        ob_clean(); 
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? $_POST['email'] ?? '';
            $password = $input['password'] ?? $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Preencha e-mail e senha.']);
                return;
            }

            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // CORREÇÃO AQUI: Usamos $user['password_hash'] em vez de $user['password']
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // Sucesso!
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                $redirectUrl = $this->getRedirectUrl($user);

                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
            } else {
                echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos.']);
            }

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }

    public function registerAction() {
        ob_clean();
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? $_POST['email'] ?? '';
            $password = $input['password'] ?? $_POST['password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? $_POST['confirm_password'] ?? '';
            
            // Captura o tipo de conta (client ou model)
            $accountType = $input['account_type'] ?? 'client'; 

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('E-mail inválido.');
            }
            if (strlen($password) < 6) {
                throw new \Exception('A senha deve ter no mínimo 6 caracteres.');
            }
            if ($password !== $confirmPassword) {
                throw new \Exception('As senhas não conferem.');
            }

            $db = Database::getInstance();
            
            // Verifica duplicidade
            $check = $db->getConnection()->prepare("SELECT id FROM users WHERE email = :email");
            $check->execute(['email' => $email]);
            if ($check->rowCount() > 0) {
                throw new \Exception('Este e-mail já está cadastrado.');
            }

            // Cria usuário
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash, role, status, created_at) VALUES (:email, :pass, 'user', 'active', NOW())";
            $stmt = $db->getConnection()->prepare($sql);
            
            if ($stmt->execute(['email' => $email, 'pass' => $hash])) {
                
                // --- LÓGICA DE REDIRECIONAMENTO COM INTENÇÃO ---
                // Se escolheu 'model', mandamos para o login com ?intent=model
                // Isso pode ser usado depois para abrir direto a página de criar perfil
                $redirectParams = ($accountType === 'model') ? '?registered=1&intent=model' : '?registered=1';
                
                echo json_encode(['success' => true, 'redirect' => url('/login' . $redirectParams)]);
            } else {
                throw new \Exception('Erro ao salvar no banco de dados.');
            }

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ==================================================================
    // 3. UTILITÁRIOS
    // ==================================================================

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header('Location: ' . url('/login'));
        exit;
    }

    private function getRedirectUrl($user) {
        if ($user['role'] === 'admin') {
            return url('/admin');
        }

        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT id FROM profiles WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            return url('/perfil/editar');
        }
        
        return url('/');
    }

    private function redirectBasedOnRole($userId) {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $url = $this->getRedirectUrl($user);
            header("Location: $url");
            exit;
        } else {
            $this->logout();
        }
    }
}