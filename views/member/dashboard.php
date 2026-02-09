<?php
// views/member/dashboard.php

// Garante que o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// Se for modelo, redireciona para o painel de modelo
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'model') {
    header('Location: ' . url('/perfil/editar'));
    exit;
}

$user = $user ?? []; 
// Garante que display_name tenha um valor, usando a sessão como fallback
$displayName = $user['display_name'] ?? $_SESSION['user_name'] ?? 'Membro';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - TOP Model</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'], display: ['Outfit', 'sans-serif'] },
                    colors: { primary: '#db2777', secondary: '#4f46e5' }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #f8fafc; 
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px); 
            background-size: 32px 32px; 
        }
        .blob {
            position: absolute; filter: blur(80px); z-index: -1; opacity: 0.5;
            animation: float 10s infinite ease-in-out;
        }
        @keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(10px, -20px); } }
        .glass-nav { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body class="text-slate-600 antialiased selection:bg-pink-500 selection:text-white flex flex-col min-h-screen relative overflow-x-hidden">

    <div class="blob bg-pink-300 w-96 h-96 rounded-full top-[-100px] left-[-100px] mix-blend-multiply"></div>
    <div class="blob bg-purple-300 w-96 h-96 rounded-full bottom-[-100px] right-[-100px] mix-blend-multiply animation-delay-2000"></div>

    <nav class="fixed top-0 w-full z-50 glass-nav h-16 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 group">
                <div class="bg-slate-900 text-white w-8 h-8 rounded-lg flex items-center justify-center font-display font-bold text-lg group-hover:bg-pink-600 transition">T</div>
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
            </a>
            
            <div class="hidden md:flex items-center gap-6">
                <a href="<?= url('/') ?>" class="text-sm font-semibold text-slate-900 hover:text-pink-600 transition">Home</a>
                <a href="<?= url('/') ?>?gender=woman" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Mulheres</a>
                <a href="<?= url('/') ?>?gender=trans" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Trans</a>
                <a href="<?= url('/') ?>?gender=couple" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Casais</a>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-500 uppercase mr-2 hidden sm:inline">Olá, <?= htmlspecialchars($displayName) ?></span>
                <a href="<?= url('/logout') ?>" class="text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 rounded-xl transition shadow-lg shadow-slate-200">Sair</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow pt-28 pb-20 px-4">
        <div class="max-w-4xl mx-auto">
            
            <div class="flex flex-col md:flex-row gap-8">
                
                <aside class="md:w-1/3">
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-6 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-br from-pink-50 to-purple-50"></div>
                        <div class="relative">
                            <div class="w-24 h-24 bg-slate-200 rounded-full mx-auto border-4 border-white shadow-md flex items-center justify-center text-3xl text-slate-400">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="font-display font-bold text-xl text-slate-900 mt-4"><?= htmlspecialchars($displayName) ?></h2>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wide mt-1">Conta de Membro</p>
                            
                            <div class="mt-6 border-t border-slate-100 pt-4 space-y-3">
                                <div class="text-sm text-slate-600 flex justify-between">
                                    <span>Cadastrado em:</span>
                                    <span class="font-bold"><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '--' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-slate-900 rounded-3xl p-6 text-white text-center shadow-xl relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-r from-pink-600 to-purple-600 opacity-20 group-hover:opacity-30 transition"></div>
                        <i class="fas fa-camera text-3xl mb-3 text-pink-400"></i>
                        <h3 class="font-display font-bold text-lg mb-2">Quer anunciar?</h3>
                        <p class="text-xs text-slate-300 mb-4 leading-relaxed">Torne-se uma modelo verificada e comece a divulgar seu perfil hoje mesmo.</p>
                        <a href="#" class="inline-block w-full py-3 bg-white text-slate-900 font-bold text-xs rounded-xl hover:bg-pink-50 transition">Em Breve</a>
                    </div>
                </aside>

                <div class="md:w-2/3">
                    <div class="bg-white rounded-3xl shadow-lg border border-slate-100 p-8">
                        <h3 class="font-display font-bold text-xl text-slate-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-cog text-pink-500"></i> Meus Dados
                        </h3>

                        <form class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nome Completo</label>
                                <input type="text" value="<?= htmlspecialchars($displayName) ?>" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:border-pink-500 outline-none transition" disabled title="Contate o suporte para alterar">
                                <p class="text-[10px] text-slate-400 mt-1 ml-1">Para alterar seu nome, entre em contato com o suporte.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">E-mail</label>
                                <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:border-pink-500 outline-none transition" disabled>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Data de Nascimento</label>
                                <input type="date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:border-pink-500 outline-none transition" disabled>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <h4 class="font-bold text-slate-800 mb-4 text-sm">Alterar Senha</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nova Senha</label>
                                        <input type="password" placeholder="******" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Confirmar</label>
                                        <input type="password" placeholder="******" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="button" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 text-sm">
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="font-display font-bold text-lg text-slate-900">TOP<span class="text-pink-600">Model</span></p>
            <p class="text-slate-400 text-xs mt-2">© <?= date('Y') ?> Todos os direitos reservados.</p>
        </div>
    </footer>

</body>
</html>