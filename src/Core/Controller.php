<?php

namespace App\Core;

abstract class Controller
{
    // Carrega as Views
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        $file = __DIR__ . "/../../views/{$viewPath}.php";
        if (file_exists($file)) {
            require $file;
        } else {
            echo "Erro: View {$viewPath} não encontrada.";
        }
    }

    // Redirecionamento Inteligente
    protected function redirect(string $path): void
    {
        // Se o caminho não começar com http (for interno), usa o helper url()
        if (!preg_match('/^https?:\/\//', $path)) {
            // Verifica se a função url() existe (helpers carregados)
            if (function_exists('url')) {
                $path = url($path);
            }
        }

        header("Location: {$path}");
        exit;
    }

    // --- NOVO MÉTODO (A Correção) ---
    // Verifica se o usuário está logado. Se não, manda pro login.
    protected function checkAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }
    }
}
