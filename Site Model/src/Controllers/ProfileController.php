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
}