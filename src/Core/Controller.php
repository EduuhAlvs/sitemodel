<?php
namespace App\Core;

abstract class Controller {
    
    protected function view(string $viewPath, array $data = []): void {
        extract($data);
        $file = __DIR__ . "/../../views/{$viewPath}.php";
        if (file_exists($file)) {
            require $file;
        } else {
            echo "Erro: View {$viewPath} não encontrada.";
        }
    }

    // --- MUDANÇA AQUI ---
    protected function redirect(string $path): void {
        // Se o caminho não começar com http (for interno), usa o helper url()
        if (!preg_match('/^https?:\/\//', $path)) {
            $path = url($path);
        }
        
        header("Location: {$path}");
        exit;
    }
}