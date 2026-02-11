<?php

// public/index.php

session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/helpers.php';


use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\PhotoController;
use App\Controllers\PublicProfileController;
use App\Controllers\PaymentController;
use App\Controllers\WebhookController;
use App\Controllers\AdminController;
use App\Controllers\FavoriteController;
use App\Controllers\MemberController;
use App\Controllers\ReviewsController;
use App\Controllers\LocationsController;

// --- Configuração de Erros (Desative em Produção) ---
// Idealmente, deve vir de variável de ambiente
$debug = (getenv('APP_ENV') === 'development' || ($_ENV['APP_ENV'] ?? '') === 'development');
ini_set('display_errors', $debug ? 1 : 0);
ini_set('display_startup_errors', $debug ? 1 : 0);
error_reporting(E_ALL);

try {
    // --- Inicialização do Roteador ---
    $router = new Router();

    // --- Definição das Rotas ---
    // Aqui é onde a mágica acontece. Você define qual URL chama qual Controller.

    // Rota da Página Inicial
    $router->get('/', [HomeController::class, 'index']);

    // --- NOVAS ROTAS DE AUTENTICAÇÃO ---
    // Login
    $router->get('/login', [AuthController::class, 'login']);
    $router->post('/login', [AuthController::class, 'loginAction']);

    // Registro
    $router->get('/register', [AuthController::class, 'register']);
    $router->post('/register', [AuthController::class, 'registerAction']);

    // Logout
    $router->get('/logout', [AuthController::class, 'logout']);

    // ROTAS DO PAINEL DA MODELO
    $router->get('/perfil/editar', [ProfileController::class, 'edit']);
    $router->post('/api/perfil/save', [ProfileController::class, 'save']);

    // ROTAS DE FOTOS (API UNIFICADA)
    // Aponta para o método 'upload' que vamos ajustar para aceitar múltiplos arquivos
    $router->post('/api/photos/upload', [PhotoController::class, 'upload']);
    $router->post('/api/photos/delete', [PhotoController::class, 'delete']);

    // API LOCAIS
    $router->get('/api/locations/search', [ProfileController::class, 'searchCity']);
    $router->post('/api/locations/manage', [ProfileController::class, 'manageLocation']);

    // API LÍNGUAS
    $router->post('/api/languages/manage', [ProfileController::class, 'manageLanguage']);

    // ROTA DE PERFIL PÚBLICO (Dinâmica)
    $router->get('/perfil/{slug}', [PublicProfileController::class, 'show']);

    // ROTAS DE PAGAMENTO
    // Exibe a tela de planos
    $router->get('/planos', [PaymentController::class, 'plans']);

    // Processa o formulário de contratação (IMPORTANTE)
    $router->post('/payment/checkout', [PaymentController::class, 'checkout']);

    // Tela de sucesso (Mock)
    $router->get('/payment/success', [PaymentController::class, 'mockSuccess']);

    // WEBHOOK (Método POST, pois a Unlimit envia dados)
    $router->post('/api/webhooks/unlimit', [WebhookController::class, 'handle']);

    // ROTA DO ADMIN
    $router->get('/admin', [AdminController::class, 'index']);
    // ROTAS DE MODERAÇÃO (Admin)
    $router->get('/admin/photos', [AdminController::class, 'photos']);
    $router->post('/api/admin/photos/approve', [AdminController::class, 'approvePhoto']);
    $router->post('/api/admin/photos/reject', [AdminController::class, 'rejectPhoto']);
    // GESTÃO DE USUÁRIOS (Admin)
    $router->get('/admin/users', [AdminController::class, 'users']);
    $router->post('/api/admin/users/ban', [AdminController::class, 'toggleBan']);


    // ROTAS DE FAVORITOS
    $router->post('/api/favorites/toggle', [FavoriteController::class, 'toggle']);
    $router->get('/meus-favoritos', [FavoriteController::class, 'index']);

    // MUDAR PARA ISSO (ARRAY):
    $router->post('/api/perfil/create', [ProfileController::class, 'createAPI']);
    $router->get('/perfil/criar', [ProfileController::class, 'createView']);

    // API para carregar cidades via AJAX (Adicione junto com as rotas GET)
    $router->get('/api/locations/cities', [LocationsController::class, 'getCities']);

    // API para verificar disponibilidade do slug
    $router->get('/api/perfil/check-slug', [ProfileController::class, 'checkSlug']);


    $router->get('/minha-conta', [MemberController::class, 'dashboard']);

    // Rotas de Avaliações
    $router->post('/api/reviews/create', [ReviewsController::class, 'create']);
    $router->post('/api/reviews/reply', [ReviewsController::class, 'reply']);
    $router->post('/api/reviews/delete', [ReviewsController::class, 'delete']);

    // --- Executa o Roteador ---
    $router->dispatch();
} catch (\Throwable $e) {
    // Log do erro real para o admin
    error_log($e->getMessage());

    // Exibe página de erro amigável para o usuário
    http_response_code(500);
    if ($debug) {
        echo "<h1>Erro Interno</h1><p>" . $e->getMessage() . "</p>";
    } else {
        echo "<h1>Ops!</h1><p>Ocorreu um erro inesperado. Tente novamente mais tarde.</p>";
    }
}
