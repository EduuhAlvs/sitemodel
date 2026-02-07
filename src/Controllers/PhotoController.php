<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database; // Necessário para listar fotos
use PDO; // Necessário para o fetch
use App\Models\Photo;
use App\Models\Profile;

class PhotoController extends Controller {

    // ==================================================================
    // 1. VISUALIZAÇÃO DA GALERIA (NOVO)
    // ==================================================================
    public function index() {
        // Verifica login
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // Usa o método que você já tem para pegar o perfil
        $profile = Profile::getByUserId($userId);

        if (!$profile) {
            $this->redirect('/perfil/criar');
            return;
        }

        // Busca todas as fotos do perfil (Usando SQL direto para garantir a lista completa)
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT * FROM profile_photos WHERE profile_id = :pid ORDER BY created_at DESC");
        $stmt->execute(['pid' => $profile['id']]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Renderiza a view que criamos (views/member/photos.php)
        $this->view('member/photos', [
            'photos' => $photos,
            'profile_id' => $profile['id']
        ]);
    }

    // ==================================================================
    // 2. UPLOAD MÚLTIPLO (GALERIA) (NOVO)
    // ==================================================================
    public function uploadGallery() {
        // Verifica sessão e método
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $profile = Profile::getByUserId($userId);

        if (!$profile || !isset($_FILES['photos'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma foto enviada.']);
            return;
        }

        $db = Database::getInstance();
        $files = $_FILES['photos'];
        $uploadedCount = 0;
        $errors = [];

        // O PHP organiza o array de múltiplos arquivos de forma transposta
        // Precisamos iterar pelo contador
        $count = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            
            // 1. Validação de Erro
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Erro no arquivo " . $files['name'][$i];
                continue;
            }

            // 2. Validação de Tipo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['tmp_name'][$i]);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedMimes)) {
                $errors[] = "Formato inválido: " . $files['name'][$i];
                continue;
            }

            // 3. Validação de Tamanho (Max 5MB)
            if ($files['size'][$i] > 5 * 1024 * 1024) {
                $errors[] = "Muito grande: " . $files['name'][$i];
                continue;
            }

            // 4. Processamento
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $newFileName = uniqid('img_', true) . '.' . $ext;
            
            // Caminhos
            $dbPath = 'uploads/photos/' . $newFileName;
            $uploadDir = __DIR__ . '/../../public/uploads/photos/';

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $newFileName)) {
                // Salva no Banco (Sempre como pendente de aprovação: is_approved = 0)
                $stmt = $db->getConnection()->prepare("INSERT INTO profile_photos (profile_id, file_path, is_approved, created_at) VALUES (:pid, :path, 0, NOW())");
                $stmt->execute([
                    'pid' => $profile['id'],
                    'path' => $dbPath
                ]);
                $uploadedCount++;
            } else {
                $errors[] = "Falha ao salvar: " . $files['name'][$i];
            }
        }

        echo json_encode([
            'success' => $uploadedCount > 0,
            'count' => $uploadedCount,
            'errors' => $errors
        ]);
    }

    // ==================================================================
    // 3. UPLOAD UNITÁRIO (MANTIDO DO SEU CÓDIGO ORIGINAL)
    // ==================================================================
    public function upload() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit;
        }

        $userId = $_SESSION['user_id'];
        $profile = Profile::getByUserId($userId);

        if (!$profile || !isset($_FILES['photo'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada.']);
            return;
        }

        $file = $_FILES['photo'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro no upload. Código: ' . $file['error']]);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mimeType, $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo inválido. Apenas JPG, PNG ou WEBP.']);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'A imagem deve ter no máximo 5MB.']);
            return;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('img_', true) . '.' . $extension;
        
        $dbPath = 'uploads/photos/' . $newFileName;
        $uploadDir = __DIR__ . '/../../public/uploads/photos/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
            $photoId = Photo::add($profile['id'], $dbPath);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Upload realizado!',
                'photo' => [
                    'id' => $photoId,
                    'url' => url('/' . $dbPath)
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao mover arquivo para pasta de destino.']);
        }
    }

    // ==================================================================
    // 4. DELETAR (MANTIDO E AJUSTADO)
    // ==================================================================
    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $photoId = $input['id'] ?? 0;
        
        if (!$photoId || !isset($_SESSION['user_id'])) exit;

        $profile = Profile::getByUserId($_SESSION['user_id']);
        
        // Tenta deletar e recebe o caminho do arquivo
        $filePath = Photo::delete($photoId, $profile['id']);
        
        if ($filePath) {
            $fullPath = __DIR__ . '/../../public/' . $filePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar.']);
        }
    }
}