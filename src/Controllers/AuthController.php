<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use Throwable;
use Exception;

class AuthController extends Controller
{
    // ==================================================================
    // 1. TELAS (GET)
    // ==================================================================

    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectBasedOnRole($_SESSION['user_id']);
            return;
        }
        $this->view('auth/login');
    }

    public function register()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectBasedOnRole($_SESSION['user_id']);
            return;
        }
        $this->view('auth/register');
    }

    // ==================================================================
    // 2. AÇÕES (POST)
    // ==================================================================

    public function loginAction()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
                return;
            }

            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    echo json_encode(['success' => false, 'message' => 'Conta inativa.']);
                    return;
                }

                $_SESSION['user_id'] = $user['id'];
                // Usa user_type (model/member) como role principal
                $_SESSION['user_role'] = $user['user_type'];

                // Busca nome no perfil para exibir
                $stmtName = $db->getConnection()->prepare("SELECT display_name FROM profiles WHERE user_id = ? LIMIT 1");
                $stmtName->execute([$user['id']]);
                $profileName = $stmtName->fetchColumn();

                $_SESSION['user_name'] = $profileName ? $profileName : 'Membro';

                $redirect = $this->getRedirectUrl($user);
                echo json_encode(['success' => true, 'redirect' => $redirect]);
            } else {
                echo json_encode(['success' => false, 'message' => 'E-mail ou senha incorretos.']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    public function registerAction()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $confirm = $input['confirm_password'] ?? '';
            $type = $input['account_type'] ?? 'member';
            $birthDate = $input['birth_date'] ?? '';

            // --- VALIDAÇÕES ---
            if (empty($name) || empty($email) || empty($password) || empty($birthDate)) {
                throw new Exception('Preencha todos os campos obrigatórios.');
            }

            if ($password !== $confirm) {
                throw new Exception('As senhas não conferem.');
            }

            if (strlen($password) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres.');
            }

            // Validação de Idade
            $diff = date_diff(date_create($birthDate), date_create('today'));
            if ($diff->y < 18) {
                throw new Exception('É estritamente proibido o cadastro de menores de 18 anos.');
            }

            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Verifica E-mail
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Este e-mail já está cadastrado.');
            }

            // --- INÍCIO DA TRANSAÇÃO ---
            $conn->beginTransaction();

            try {
                // 1. Inserir Usuário
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $sqlUser = "INSERT INTO users (email, password_hash, role, user_type, status) VALUES (:email, :hash, 'user', :type, 'active')";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->execute(['email' => $email, 'hash' => $hash, 'type' => $type]);

                $userId = $conn->lastInsertId();

                // 2. Inserir Perfil Básico
                $slug = $this->generateSlug($name) . '-' . uniqid();

                $sqlProfile = "INSERT INTO profiles (user_id, display_name, slug, birth_date, status) VALUES (:uid, :name, :slug, :bdate, 'active')";
                $stmtProfile = $conn->prepare($sqlProfile);
                $stmtProfile->execute([
                    'uid' => $userId,
                    'name' => $name,
                    'slug' => $slug,
                    'bdate' => $birthDate
                ]);

                $conn->commit();

                // Login automático
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = $type;
                $_SESSION['user_name'] = $name;

                // Redirecionamento
                if ($type === 'model') {
                    $redirect = url('/perfil/editar');
                } else {
                    $redirect = url('/minha-conta');
                }

                echo json_encode(['success' => true, 'redirect' => $redirect]);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================================================================
    // 3. MÉTODOS AUXILIARES
    // ==================================================================

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: ' . url('/login'));
        exit;
    }

    private function getRedirectUrl($user)
    {
        if ($user['role'] === 'admin') {
            return url('/admin');
        }
        if ($user['user_type'] === 'model') {
            return url('/perfil/editar');
        }
        return url('/minha-conta');
    }

    private function redirectBasedOnRole($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $url = $this->getRedirectUrl($user);
            header("Location: $url");
            exit;
        }
    }

    private function generateSlug($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'user' : $text;
    }
}
