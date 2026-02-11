<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Profile;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Language;
use App\Models\Subscription;
use PDO;
use Exception;

class ProfileController extends Controller
{
    public function edit()
    {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        $db = Database::getInstance();

        // 1. Busca TODOS os perfis do usuário
        $stmt = $db->getConnection()->prepare("SELECT id, display_name, slug, status, profile_image FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $allProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allProfiles)) {
            $this->redirect('/perfil/criar');
            exit;
        }

        // 2. Define perfil atual (da URL ou o primeiro)
        $requestedProfileId = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : $allProfiles[0]['id'];

        // Verifica se o perfil solicitado pertence ao usuário
        $currentProfile = null;
        foreach ($allProfiles as $p) {
            if ($p['id'] == $requestedProfileId) {
                // Carrega dados completos
                $currentProfile = Profile::getById($requestedProfileId);
                break;
            }
        }

        // Se não achou (ou tentou acessar perfil de outro), volta pro primeiro
        if (!$currentProfile) {
            $currentProfile = Profile::getById($allProfiles[0]['id']);
        }

        // 3. Carrega dados ESPECÍFICOS do perfil selecionado
        $photos = Photo::getAllByProfile($currentProfile['id']);
        $locations = Location::getByProfile($currentProfile['id']);
        $languages = Language::getByProfile($currentProfile['id']);

        // 4. Carrega Planos Ativos
        $subsData = Subscription::getConsolidatedList($currentProfile['id']);
        $activePlans = $subsData['plans'] ?? [];

        $this->view('model/dashboard', [
            'profile' => $currentProfile,
            'myProfiles' => $allProfiles,
            'photos' => $photos,
            'locations' => $locations,
            'languages' => $languages,
            'activePlans' => $activePlans
        ]);
    }

    // --- SALVAR DADOS ---
    public function save()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $section = $input['section'] ?? '';
        $data = $input['data'] ?? [];
        $targetProfileId = $data['profile_id'] ?? 0;

        // VERIFICAÇÃO CORRETA: O perfil pertence ao usuário?
        if (!Profile::isOwner($_SESSION['user_id'], $targetProfileId)) {
            echo json_encode(['success' => false, 'message' => 'Perfil inválido ou permissão negada.']);
            return;
        }

        try {
            $updateData = $this->filterDataBySection($section, $data);
            if (!empty($updateData)) {
                Profile::update($targetProfileId, $updateData);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nada a salvar']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // API Upload de Fotos e outros métodos
    public function createAPI()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Sessão expirada.');
            }

            $userId = $_SESSION['user_id'];
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $displayName = trim($input['display_name'] ?? '');
            $slug = trim($input['slug'] ?? '');
            $cityId = intval($input['city_id'] ?? 0);
            $gender = $input['gender'] ?? 'woman';

            if (empty($displayName) || empty($slug) || empty($cityId)) {
                throw new Exception('Preencha todos os campos.');
            }

            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Verifica Slots
            $stmt = $conn->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= $this->getUserMaxSlots($userId)) {
                throw new Exception('Limite atingido.');
            }

            // Verifica Slug
            $stmt = $conn->prepare("SELECT id FROM profiles WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                throw new Exception('Link indisponível.');
            }

            $conn->beginTransaction();
            $sql = "INSERT INTO profiles (user_id, display_name, slug, gender, phone, birth_date, status) VALUES (:uid, :name, :slug, :gender, '', '2000-01-01', 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['uid' => $userId, 'name' => $displayName, 'slug' => $slug, 'gender' => $gender]);
            $newProfileId = $conn->lastInsertId();

            $stmtLoc = $conn->prepare("INSERT INTO profile_locations (profile_id, city_id, is_base_city) VALUES (:pid, :cid, 1)");
            $stmtLoc->execute(['pid' => $newProfileId, 'cid' => $cityId]);
            $conn->commit();

            echo json_encode(['success' => true, 'profile_id' => $newProfileId]);
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function checkSlug()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        $slug = $_GET['slug'] ?? '';
        if (strlen($slug) < 3) {
            echo json_encode(['available' => false]);
            exit;
        }
        $forbidden = ['admin', 'painel', 'login', 'api', 'dashboard', 'perfil'];
        if (in_array($slug, $forbidden)) {
            echo json_encode(['available' => false]);
            exit;
        }
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT count(*) FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        echo json_encode(['available' => ($stmt->fetchColumn() == 0)]);
        exit;
    }

    private function filterDataBySection($section, $data)
    {
        $allowed = [];
        switch ($section) {
            case 'bio':
                $allowed = ['display_name', 'gender', 'orientation', 'birth_date', 'ethnicity', 'nationality'];
                break;
            case 'appearance':
                $allowed = [
                    'hair_color', 'eye_color', 'height_cm', 'weight_kg',
                    'bust_cm', 'waist_cm', 'hips_cm',
                    'cup_size', 'shaving', 'silicone', 'tattoos'
                ];
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
            case 'service_details':
                $allowed = ['incall_available', 'outcall_available', 'service_details'];
                if (isset($data['details'])) {
                    $data['service_details'] = json_encode($data['details']);
                }
                break;
        }
        return array_intersect_key($data, array_flip($allowed));
    }

    private function getUserMaxSlots($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 1;
    }

    public function createView()
    {
        require __DIR__ . '/../../views/model/create_profile.php';
    }

    public function searchCity()
    {
        echo json_encode(Location::search($_GET['q'] ?? ''));
    }

    public function manageLocation()
    {
        header('Content-Type: application/json');
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Login necessário');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Dados inválidos');
            }

            if (Profile::isOwner($_SESSION['user_id'], $input['profile_id'])) {
                if ($input['action'] == 'add') {
                    Location::add($input['profile_id'], $input['city_id']);
                }
                if ($input['action'] == 'remove') {
                    Location::remove($input['profile_id'], $input['city_id']);
                }
                if ($input['action'] == 'set_base') {
                    Location::setBase($input['profile_id'], $input['city_id']);
                }
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Permissão negada');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function manageLanguage()
    {
        header('Content-Type: application/json');
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Login necessário');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Dados inválidos');
            }

            if (Profile::isOwner($_SESSION['user_id'], $input['profile_id'])) {
                if ($input['action'] == 'add') {
                    Language::add($input['profile_id'], $input['language'], $input['level']);
                }
                if ($input['action'] == 'remove') {
                    Language::remove($input['id'], $input['profile_id']);
                }
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Permissão negada');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
