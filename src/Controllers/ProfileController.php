<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile;
use App\Models\Photo; 
use App\Models\Location;
use App\Models\Language;

class ProfileController extends Controller {

    // Carrega a página de edição (Dashboard)
    public function edit() {
        // 1. Segurança: Só logado pode acessar
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // 2. Busca o perfil
        $userId = $_SESSION['user_id'];
        $profile = Profile::getByUserId($userId);

        // 3. Se não existir perfil ainda, cria um rascunho automático
        if (!$profile) {
            Profile::createDraft($userId, $_SESSION['email']);
            $profile = Profile::getByUserId($userId);
        }

        // 4. Busca as fotos do perfil 
        // Se não buscar, a variável $photos não existe e dá o erro.
        $photos = Photo::getAllByProfile($profile['id']);

        // Funçao de carregar cidades
        $locations = Location::getByProfile($profile['id']);

        // Função carregar línguas faladas
        $languages = Language::getByProfile($profile['id']);

        // 5. Renderiza a view passando os dados
        $this->view('model/dashboard', [
            'profile' => $profile, 
            'photos' => $photos,
            'locations' => $locations,
            'languages' => $languages,
        ]);
    }

    // API De Locais 
    public function searchCity() {
        $term = $_GET['q'] ?? '';
        if (strlen($term) < 3) {
            echo json_encode([]);
            return;
        }
        $results = Location::search($term);
        echo json_encode($results);
    }

    public function manageLocation() {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? ''; // 'add', 'remove', 'set_base'
        $cityId = $input['city_id'] ?? 0;
        
        if (!isset($_SESSION['user_id']) || !$cityId) exit;
        
        $profile = Profile::getByUserId($_SESSION['user_id']);
        
        $success = false;
        if ($action === 'add') {
            $success = Location::add($profile['id'], $cityId);
        } elseif ($action === 'remove') {
            $success = Location::remove($profile['id'], $cityId);
        } elseif ($action === 'set_base') {
            $success = Location::setBase($profile['id'], $cityId);
        }

        echo json_encode(['success' => $success]);
    }

    // --- NOVO MÉTODO API ---
public function manageLanguage() {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? ''; 
    
    if (!isset($_SESSION['user_id'])) exit;
    $profile = Profile::getByUserId($_SESSION['user_id']);

    $success = false;

    if ($action === 'add') {
        $lang = $input['language'] ?? '';
        $level = $input['level'] ?? 'medium';
        if ($lang) {
            $success = Language::add($profile['id'], $lang, $level);
        }
    } elseif ($action === 'remove') {
        $id = $input['id'] ?? 0;
        if ($id) {
            $success = Language::remove($id, $profile['id']);
        }
    }

    echo json_encode(['success' => $success]);
}


    // Endpoint API para salvar cada card (Recebe JSON via AJAX)
    public function save() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }

        // Lê o JSON enviado pelo Javascript
        $input = json_decode(file_get_contents('php://input'), true);
        $section = $input['section'] ?? '';
        $data = $input['data'] ?? [];

        $userId = $_SESSION['user_id'];
        $profile = Profile::getByUserId($userId);

        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'Perfil não encontrado']);
            return;
        }

        try {
            // Filtra os dados permitidos para cada seção (Segurança)
            $updateData = $this->filterDataBySection($section, $data);
            
            if (!empty($updateData)) {
                Profile::update($profile['id'], $updateData);
                echo json_encode(['success' => true, 'message' => 'Dados salvos com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhum dado válido enviado.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
    }

    // Lista de campos permitidos por seção
    private function filterDataBySection(string $section, array $data): array {
        $allowed = [];
        
        switch ($section) {
            case 'bio':
                $allowed = ['display_name', 'gender', 'orientation', 'birth_date', 'ethnicity', 'nationality'];
                break;
            case 'appearance':
                $allowed = ['hair_color', 'eye_color', 'height_cm', 'weight_kg', 'bust', 'waist', 'hips', 'cup_size', 'shaving', 'silicone', 'tattoos'];
                break;
            case 'about':
                $allowed = ['bio', 'smoker', 'drinker'];
                break;
            case 'contact':
                $allowed = ['phone', 'whatsapp_enabled', 'viber_enabled', 'contact_preference'];
                break;
            case 'schedule':
                $allowed = ['is_24_7', 'show_as_night', 'working_hours'];
                break;
        }

        return array_filter($data, function($key) use ($allowed) {
            return in_array($key, $allowed);
        }, ARRAY_FILTER_USE_KEY);
    }


    /**
     * API: Processa a criação de um novo perfil (POST /api/perfil/create)
     */
    public function createAPI() {
        header('Content-Type: application/json');

        // 1. Verificação de Auth
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 2. Receber dados JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        $displayName = trim($data['display_name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $cityId = intval($data['city_id'] ?? 0);
        $gender = $data['gender'] ?? 'woman';

        // 3. Validações Básicas
        if (empty($displayName) || empty($slug) || empty($cityId)) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            return;
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        try {
            // 4. VERIFICAÇÃO DE SLOTS (Segurança Crítica)
            // Conta quantos perfis a usuária já tem
            $stmt = $conn->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $currentCount = $stmt->fetchColumn();

            // Busca o limite da usuária
            $stmt = $conn->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $maxSlots = $stmt->fetchColumn() ?: 1;

            if ($currentCount >= $maxSlots) {
                echo json_encode(['success' => false, 'message' => 'Você atingiu o limite de perfis da sua conta. Adquira mais vagas.']);
                return;
            }

            // 5. Verifica se o SLUG já existe (deve ser único globalmente)
            $stmt = $conn->prepare("SELECT id FROM profiles WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este link de perfil já está em uso. Escolha outro.']);
                return;
            }

            // 6. Busca Nome da Cidade (para salvar cache na tabela profiles se necessário)
            $stmt = $conn->prepare("SELECT name FROM locations WHERE id = ?");
            $stmt->execute([$cityId]);
            $cityName = $stmt->fetchColumn() ?: '';

            // 7. CRIA O PERFIL
            // Inicia transação para garantir que tudo salva ou nada salva
            $conn->beginTransaction();

            $sql = "INSERT INTO profiles (user_id, display_name, slug, gender, city, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $displayName, $slug, $gender, $cityName]);
            
            $newProfileId = $conn->lastInsertId();

            // 8. VINCULA A CIDADE (Tabela profile_locations)
            // Define como base_city = 1
            $sqlLoc = "INSERT INTO profile_locations (profile_id, city_id, is_base_city) VALUES (?, ?, 1)";
            $stmtLoc = $conn->prepare($sqlLoc);
            $stmtLoc->execute([$newProfileId, $cityId]);

            $conn->commit();

            echo json_encode(['success' => true, 'profile_id' => $newProfileId]);

        } catch (\Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            // Log do erro real no servidor, mas mensagem genérica pro usuário
            error_log("Erro ao criar perfil: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao criar perfil. Tente novamente.']);
        }
    }

    public function createView() {
    // Verifica login
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }
    
    // Carrega a view que criamos no passo anterior
    require __DIR__ . '/../../views/model/create_profile.php';
}

}