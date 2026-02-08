<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Photo;
use App\Models\Profile;

class PhotoController extends Controller {

    public function upload() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }

        // CORREÇÃO: Recebe o ID do perfil alvo via POST
        $targetProfileId = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;

        // Valida se o usuário é dono desse perfil específico
        if (!Profile::isOwner($_SESSION['user_id'], $targetProfileId)) {
            echo json_encode(['success' => false, 'message' => 'Perfil inválido.']);
            return;
        }

        if (empty($_FILES['photos'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada']);
            return;
        }

        $uploadedCount = 0;
        $db = Database::getInstance();

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $newName = uniqid('p' . $targetProfileId . '_') . '.' . $ext;
                    $targetDir = __DIR__ . '/../../public/uploads/photos/';
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    
                    if (move_uploaded_file($tmpName, $targetDir . $newName)) {
                        $filePath = 'uploads/photos/' . $newName;
                        // Salva usando o ID correto
                        $stmt = $db->getConnection()->prepare("INSERT INTO profile_photos (profile_id, file_path, is_approved, created_at) VALUES (?, ?, 0, NOW())");
                        $stmt->execute([$targetProfileId, $filePath]);
                        $uploadedCount++;
                    }
                }
            }
        }

        if ($uploadedCount > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar arquivos']);
        }
    }

    public function delete() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $photoId = $input['id'] ?? 0;
        $profileId = $input['profile_id'] ?? 0;

        if (!Profile::isOwner($_SESSION['user_id'], $profileId)) {
            echo json_encode(['success' => false]); return;
        }

        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("SELECT file_path FROM profile_photos WHERE id = ? AND profile_id = ?");
        $stmt->execute([$photoId, $profileId]);
        $photo = $stmt->fetch();

        if ($photo) {
            $path = __DIR__ . '/../../public/' . $photo['file_path'];
            if (file_exists($path)) unlink($path);
            $db->getConnection()->prepare("DELETE FROM profile_photos WHERE id = ?")->execute([$photoId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Foto não encontrada']);
        }
    }
}