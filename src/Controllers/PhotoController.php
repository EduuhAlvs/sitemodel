<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Photo;
use App\Models\Profile;

class PhotoController extends Controller {

    public function upload() {
        // Limpeza de buffer para evitar erros de HTML quebrando o JSON
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        if (ob_get_length()) ob_clean(); 
        
        header('Content-Type: application/json');

        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Acesso não autorizado.');
            }

            $userId = $_SESSION['user_id'];
            $profile = Profile::getByUserId($userId);

            if (!$profile) {
                throw new \Exception('Perfil não encontrado.');
            }

            // --- DETECÇÃO INTELIGENTE DE ARQUIVOS ---
            $files = [];
            
            // CASO 1: Múltiplos arquivos (photos[]) - O novo padrão
            if (isset($_FILES['photos'])) {
                $raw = $_FILES['photos'];
                // O PHP inverte a estrutura de arrays em upload múltiplo
                if (is_array($raw['name'])) {
                    $count = count($raw['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if (empty($raw['name'][$i])) continue;
                        $files[] = [
                            'name'     => $raw['name'][$i],
                            'type'     => $raw['type'][$i],
                            'tmp_name' => $raw['tmp_name'][$i],
                            'error'    => $raw['error'][$i],
                            'size'     => $raw['size'][$i]
                        ];
                    }
                }
            } 
            // CASO 2: Arquivo único (photo) - Legado
            elseif (isset($_FILES['photo'])) {
                $files[] = $_FILES['photo'];
            }
            
            if (empty($files)) {
                throw new \Exception('Nenhuma imagem recebida.');
            }

            // Processamento
            $uploadDir = __DIR__ . '/../../public/uploads/photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $uploadedCount = 0;
            $errors = [];

            foreach ($files as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Erro envio: " . $file['name'];
                    continue;
                }

                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                
                if (!in_array($mimeType, $allowedMimes)) {
                    $errors[] = "Formato inválido: " . $file['name'];
                    continue;
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = "Muito grande (>5MB): " . $file['name'];
                    continue;
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFileName = uniqid('img_', true) . '.' . $ext;
                $dbPath = 'uploads/photos/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                    try {
                        Photo::add($profile['id'], $dbPath);
                        $uploadedCount++;
                    } catch (\Throwable $e) {
                        unlink($uploadDir . $newFileName); // Apaga se der erro no banco
                    }
                } else {
                    $errors[] = "Falha ao salvar: " . $file['name'];
                }
            }

            if ($uploadedCount > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => "$uploadedCount foto(s) enviada(s) com sucesso!"
                ]);
            } else {
                throw new \Exception(empty($errors) ? 'Erro desconhecido.' : implode(', ', $errors));
            }

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete() {
        ini_set('display_errors', 0);
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $photoId = $input['id'] ?? 0;
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!$photoId || !isset($_SESSION['user_id'])) exit;

        $profile = Profile::getByUserId($_SESSION['user_id']);
        
        $filePath = Photo::delete($photoId, $profile['id']);
        
        if ($filePath) {
            $fullPath = __DIR__ . '/../../public/' . $filePath;
            if (file_exists($fullPath)) unlink($fullPath);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar.']);
        }
    }
}