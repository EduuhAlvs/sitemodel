<?php
// views/pages/home.php

use App\Models\Profile;
use App\Core\Database;

// 1. CARREGAMENTO DE DADOS (Fallback)
if (!isset($profiles)) {
    $db = Database::getInstance();
    
    // VIPs
    $stmt = $db->getConnection()->query("SELECT * FROM profiles WHERE status = 'active' AND current_plan_level > 0 ORDER BY RAND() LIMIT 4");
    $vipProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recentes
    $stmt = $db->getConnection()->query("SELECT * FROM profiles WHERE status = 'active' ORDER BY id DESC LIMIT 12");
    $recentProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cidades
    $stmt = $db->getConnection()->query("SELECT DISTINCT name FROM locations ORDER BY name ASC LIMIT 10");
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $vipProfiles = array_filter($profiles, function($p) { return ($p['current_plan_level'] ?? 0) > 0; });
    $recentProfiles = $profiles;
    $cities = $cities ?? [];
}

// Helper de Imagem (CORRIGIDO E BLINDADO)
function getImg($profileData) {
    // Verifica se a chave existe e não está vazia
    $path = $profileData['profile_image'] ?? null;
    $name = $profileData['display_name'] ?? 'Modelo';
    
    if (!empty($path)) {
        return url('/' . $path);
    }
    // Fallback para avatar gerado
    return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=db2777&color=fff&size=512';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP Model - Acompanhantes de Luxo</title>
    
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
        body { background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 30px 30px; }
        .nav-glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
        .card-model { transition: all 0.3s ease; }
        .card-model:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15); }
        .badge-vip { background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); color: #B45309; border: 1px solid #FCD34D; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="text-slate-600 antialiased">

    <nav class="fixed top-0 w-full z-50 nav-glass h-16">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 group">
                <span class="font-display font-black text-2xl tracking-tighter text-slate-900 group-hover:opacity-80 transition">
                    TOP<span class="text-pink-600">Model</span>
                </span>
            </a>

            <div class="hidden md:flex items-center gap-6">
                <a href="#" class="text-sm font-semibold text-slate-500 hover:text-pink-600 transition">Cidades</a>
                <div class="h-4 w-px bg-slate-200"></div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= url('/painel/dashboard') ?>" class="bg-slate-900 text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wide hover:bg-slate-800 transition shadow-lg shadow-slate-900/20">
                        Meu Painel
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="text-sm font-bold text-slate-700 hover:text-pink-600">Entrar</a>
                    <a href="<?= url('/registro') ?>" class="bg-pink-600 text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-wide hover:bg-pink-700 transition shadow-lg shadow-pink-600/30">
                        Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-12 px-4 relative overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-gradient-to-b from-pink-50 via-white to-transparent -z-10"></div>
        
        <div class="max-w-4xl mx-auto text-center">
            <span class="inline-block py-1 px-3 rounded-full bg-pink-50 border border-pink-100 text-pink-600 text-[10px] font-bold uppercase tracking-widest mb-4">
                Acompanhantes Premium
            </span>
            <h1 class="font-display font-black text-4xl md:text-6xl text-slate-900 mb-6 leading-tight">
                Encontre sua companhia <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600">perfeita hoje.</span>
            </h1>
            
            <form action="<?= url('/') ?>" method="GET" class="relative max-w-lg mx-auto">
                <input type="text" name="q" placeholder="Busque por nome ou cidade..." 
                    class="w-full pl-12 pr-4 py-4 rounded-2xl border border-slate-200 shadow-xl shadow-slate-200/40 focus:outline-none focus:ring-2 focus:ring-pink-500/50 text-slate-700 placeholder:text-slate-400 font-medium transition">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <button type="submit" class="absolute right-2 top-2 bottom-2 bg-slate-900 text-white px-6 rounded-xl font-bold text-sm hover:bg-slate-800 transition">
                    Buscar
                </button>
            </form>

            <?php if(!empty($cities)): ?>
            <div class="mt-8 flex justify-center gap-2 overflow-x-auto no-scrollbar py-2">
                <?php foreach($cities as $city): 
                    $cityName = is_array($city) ? ($city['name'] ?? reset($city) ?? '') : $city;
                    if (empty($cityName) || !is_string($cityName)) continue;
                ?>
                    <a href="?city=<?= urlencode($cityName) ?>" class="whitespace-nowrap px-4 py-1.5 rounded-full bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:border-pink-500 hover:text-pink-600 transition shadow-sm">
                        <?= htmlspecialchars($cityName) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if(!empty($vipProfiles)): ?>
    <section class="max-w-7xl mx-auto px-4 mb-16">
        <div class="flex items-center gap-3 mb-6">
            <i class="fas fa-crown text-yellow-500 text-xl"></i>
            <h2 class="font-display font-bold text-2xl text-slate-900">Destaques VIP</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach($vipProfiles as $vip): ?>
                <a href="<?= url('/perfil/' . ($vip['slug'] ?? '')) ?>" class="card-model group relative aspect-[3/4] rounded-2xl overflow-hidden bg-slate-100">
                    <img src="<?= getImg($vip) ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-90"></div>
                    
                    <div class="absolute top-3 left-3">
                        <span class="badge-vip px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide shadow-sm">VIP</span>
                    </div>

                    <div class="absolute bottom-0 left-0 w-full p-4 text-white">
                        <h3 class="font-display font-bold text-lg leading-tight mb-1"><?= htmlspecialchars($vip['display_name'] ?? 'Modelo') ?></h3>
                        <p class="text-xs font-medium text-slate-300 flex items-center gap-1">
                            <i class="fas fa-map-marker-alt text-pink-500"></i> <?= htmlspecialchars($vip['city'] ?? 'Brasil') ?>
                        </p>
                    </div>
                    <div class="absolute inset-0 border-2 border-yellow-500/50 rounded-2xl pointer-events-none"></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="max-w-7xl mx-auto px-4 mb-20">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-display font-bold text-2xl text-slate-900">Novidades</h2>
        </div>

        <?php if(empty($recentProfiles)): ?>
            <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200">
                <i class="far fa-sad-tear text-4xl text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-medium">Nenhum perfil encontrado.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($recentProfiles as $profile): 
                    $isVip = ($profile['current_plan_level'] ?? 0) > 0;
                ?>
                    <a href="<?= url('/perfil/' . ($profile['slug'] ?? '')) ?>" class="card-model block bg-white rounded-2xl border border-slate-100 overflow-hidden hover:border-pink-200">
                        <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                            <img src="<?= getImg($profile) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                            
                            <?php if($isVip): ?>
                                <span class="absolute top-2 right-2 badge-vip px-2 py-0.5 rounded text-[9px] font-bold uppercase shadow-sm">Destaque</span>
                            <?php endif; ?>
                            
                            <div class="absolute bottom-2 left-2">
                                <span class="bg-white/90 backdrop-blur text-slate-900 px-2 py-0.5 rounded text-[10px] font-bold uppercase shadow-sm flex items-center gap-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Online
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-display font-bold text-base text-slate-900 truncate"><?= htmlspecialchars($profile['display_name'] ?? 'Modelo') ?></h3>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-500 flex items-center gap-1 truncate max-w-[70%]">
                                    <i class="fas fa-map-marker-alt text-slate-300"></i> <?= htmlspecialchars($profile['city'] ?? 'Brasil') ?>
                                </span>
                                <?php if(!empty($profile['birth_date'])): 
                                    $age = (new DateTime($profile['birth_date']))->diff(new DateTime())->y;
                                ?>
                                    <span class="text-xs font-bold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded"><?= $age ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-12 text-center">
            <button class="bg-white border border-slate-200 text-slate-600 px-8 py-3 rounded-xl font-bold text-sm hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                Carregar Mais Modelos
            </button>
        </div>
    </section>

    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <span class="font-display font-black text-2xl tracking-tighter text-slate-900 mb-4 block">
                TOP<span class="text-pink-600">Model</span>
            </span>
            <p class="text-slate-400 text-xs">
                &copy; <?= date('Y') ?> TOP Model. Todos os direitos reservados.<br>
                Plataforma destinada a maiores de 18 anos.
            </p>
        </div>
    </footer>

</body>
</html>