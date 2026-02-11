<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        // Normaliza a rota, mas mantém o formato {param}
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // --- CORREÇÃO PARA SUBDIRETÓRIOS ---
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        // ------------------------------------

        // 1. Tenta Match Exato (Rápido)
        if (isset($this->routes[$method][$uri])) {
            $this->executeHandler($this->routes[$method][$uri]);
            return;
        }

        // 2. Tenta Match Dinâmico (Regex para {slug})
        foreach ($this->routes[$method] as $route => $handler) {
            // Transforma /perfil/{slug} em Regex: /perfil/([^/]+)
            if (strpos($route, '{') !== false) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
                $pattern = "#^" . $pattern . "$#";

                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Remove o match completo, deixa só os params
                    $this->executeHandler($handler, $matches);
                    return;
                }
            }
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/../../views/partials/header.php';
        echo "<div class='text-center py-20'><h1 class='text-4xl font-bold text-gray-800'>404</h1><p>Página não encontrada.</p></div>";
        require __DIR__ . '/../../views/partials/footer.php';
    }

    private function executeHandler(array $handler, array $params = []): void
    {
        $controllerClass = $handler[0];
        $action = $handler[1];
        $controller = new $controllerClass();

        // Chama a função passando os parâmetros (ex: o slug)
        call_user_func_array([$controller, $action], $params);
    }
}
