<?php
// views/pages/home.php
$profiles = $profiles ?? [];
$filters = $filters ?? [];

// Definição dos filtros para gerar os botões dinamicamente e manter o código limpo
$genderOptions = [
    ['label' => 'Mulheres', 'val' => 'woman', 'icon' => 'venus', 'color' => 'text-pink-500'],
    ['label' => 'Trans',    'val' => 'trans', 'icon' => 'transgender', 'color' => 'text-purple-500'],
    ['label' => 'Homens',   'val' => 'man',   'icon' => 'mars', 'color' => 'text-blue-500'],
    ['label' => 'Casais',   'val' => 'couple','icon' => 'user-friends', 'color' => 'text-orange-500'] // Novo Filtro
];
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
                    colors: { primary: '#db2777', secondary: '#4f46e5' },
                    boxShadow: {
                        'glow': '0 0 20px rgba(219, 39, 119, 0.15)',
                        'card': '0 10px 30px -10px rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; }
        
        /* Padrão de Fundo Sutil */
        .bg-pattern {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Glassmorphism na Navegação */
        .glass-nav { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px); 
            border-bottom: 1px solid rgba(226, 232, 240, 0.6); 
        }
        
        /* Blobs de cor no fundo do Hero */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: float 10s infinite ease-in-out;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10px, -20px); }
        }

        /* --- ESTILOS DAS TAGS (Mantidos do Design System) --- */
        .tag-vip {
            background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            color: #000;
            border: 1px solid #FFF;
            box-shadow: 0 4px 10px rgba(253, 185, 49, 0.3);
        }
        .tag-plus {
            background: linear-gradient(135deg, #E2E8F0 0%, #94a3b8 100%);
            color: #0f172a;
            border: 1px solid #FFF;
            box-shadow: 0 4px 10px rgba(148, 163, 184, 0.3);
        }
        .tag-premium {
            background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%);
            color: #FFF;
            border: 1px solid #FFF;
            box-shadow: 0 4px 10px rgba(234, 88, 12, 0.3);
        }
        /* Fallback */
        .tag-solid {
            color: #FFF;
            border: 1px solid #FFF;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="text-slate-600 antialiased selection:bg-pink-500 selection:text-white bg-pattern">

    <nav class="fixed top-0 w-full z-50 glass-nav h-16 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 group">
                <div class="bg-slate-900 text-white w-9 h-9 rounded-xl flex items-center justify-center font-display font-bold text-lg group-hover:bg-pink-600 group-hover:scale-105 transition duration-300 shadow-md">T</div>
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
            </a>
            
            <div class="hidden md:flex items-center gap-8">
                <a href="<?= url('/') ?>" class="text-sm font-bold text-slate-900 hover:text-pink-600 transition">Home</a>
                <a href="?gender=woman" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Mulheres</a>
                <a href="?gender=trans" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Trans</a>
                <a href="?gender=couple" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Casais</a>
            </div>

            <div class="flex items-center gap-3">
                <button class="w-9 h-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fas fa-search"></i></button>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?= url('/perfil/editar') ?>" class="text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 rounded-xl transition shadow-lg shadow-slate-300 transform hover:-translate-y-0.5">Painel</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="text-xs font-bold text-slate-500 hover:text-pink-600 transition uppercase tracking-wide mr-2">Entrar</a>
                    <a href="<?= url('/registrar') ?>" class="text-xs font-bold text-white bg-pink-600 hover:bg-pink-700 px-5 py-2.5 rounded-xl transition shadow-lg shadow-pink-200 transform hover:-translate-y-0.5">Criar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-20 px-4 relative overflow-hidden">
        <div class="blob bg-pink-300 w-96 h-96 rounded-full top-0 left-0 mix-blend-multiply"></div>
        <div class="blob bg-purple-300 w-96 h-96 rounded-full bottom-0 right-0 mix-blend-multiply animation-delay-2000"></div>

        <div class="max-w-4xl mx-auto text-center space-y-8 relative z-10">
            
            <div class="inline-flex items-center gap-2 py-1.5 px-4 rounded-full bg-white border border-slate-200 shadow-sm mb-4 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Disponíveis Agora</span>
            </div>

            <h1 class="font-display font-extrabold text-5xl sm:text-6xl lg:text-7xl text-slate-900 tracking-tight leading-[1.1]">
                Sua companhia <br> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600">exclusiva está aqui.</span>
            </h1>
            
            <p class="text-lg text-slate-500 max-w-xl mx-auto font-medium">
                Explore os perfis mais selecionados da sua região com segurança e discrição.
            </p>
            
            <div class="mt-12 relative max-w-2xl mx-auto">
                <form action="<?= url('/') ?>" method="GET" class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-pink-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-2 flex items-center border border-white/50">
                        <div class="pl-5 text-slate-400"><i class="fas fa-search text-lg"></i></div>
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['search']) ?>" 
                               class="flex-1 bg-transparent border-none focus:ring-0 text-slate-800 placeholder-slate-400 px-4 py-4 font-semibold text-base" 
                               placeholder="Busque por nome, cidade ou estilo...">
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-bold text-sm transition shadow-lg transform hover:scale-105 active:scale-95">
                            Buscar
                        </button>
                    </div>
                </form>
                
                <div class="flex flex-wrap justify-center gap-3 mt-8">
                    <?php foreach($genderOptions as $opt): 
                        $isActive = ($filters['gender'] == $opt['val']);
                        $baseClass = "flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition border shadow-sm transform hover:-translate-y-0.5";
                        $activeClass = "bg-slate-900 text-white border-slate-900";
                        $inactiveClass = "bg-white text-slate-600 border-slate-200 hover:border-pink-300 hover:text-pink-600";
                    ?>
                    <a href="?gender=<?= $opt['val'] ?>" class="<?= $baseClass ?> <?= $isActive ? $activeClass : $inactiveClass ?>">
                        <i class="fas fa-<?= $opt['icon'] ?> <?= $isActive ? 'text-white' : $opt['color'] ?>"></i> <?= $opt['label'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-16">
        <div class="flex items-end justify-between mb-10">
            <div>
                <h2 class="font-display font-bold text-3xl text-slate-900">Em Destaque</h2>
                <p class="text-slate-500 text-sm mt-1">Os perfis mais visitados da semana.</p>
            </div>
            
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm">
                    <?= count($profiles) ?> resultados
                </span>
            </div>
        </div>

        <?php if (empty($profiles)): ?>
            <div class="text-center py-24 bg-white rounded-3xl border border-dashed border-slate-300 mx-auto max-w-2xl">
                <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800">Nenhum perfil encontrado</h3>
                <p class="text-slate-500 text-sm mt-2 max-w-xs mx-auto">Não encontramos resultados para sua busca atual.</p>
                <a href="<?= url('/') ?>" class="inline-block mt-6 text-pink-600 font-bold text-sm hover:underline">Limpar todos os filtros</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($profiles as $profile): 
                    $imgUrl = !empty($profile['cover_photo']) ? url('/'.$profile['cover_photo']) : 
                              (!empty($profile['profile_image']) ? url('/'.$profile['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=f1f5f9&color=64748b&size=512');
                    
                    $loc = !empty($profile['city_name']) ? htmlspecialchars($profile['city_name']) : 'Brasil';
                    
                    $age = '';
                    if(!empty($profile['birth_date'])) {
                        $age = (new DateTime($profile['birth_date']))->diff(new DateTime())->y;
                    }
                    
                    // LÓGICA DE BADGES (Hierarquia Visual)
                    $planName = $profile['plan_name'] ?? 'VIP';
                    $planColor = $profile['plan_color'] ?? '#000';
                    
                    // Definição das Classes CSS baseadas no nome do plano
                    $tagClass = 'tag-solid'; // Padrão
                    $iconClass = 'fa-star';
                    $customStyle = "background-color: {$planColor}; border-color: white;";

                    if (stripos($planName, 'VIP') !== false) {
                        $tagClass = 'tag-vip'; // Classe Dourada
                        $iconClass = 'fa-crown';
                        $customStyle = ""; // Estilo via CSS
                    } elseif (stripos($planName, 'Plus') !== false) {
                        $tagClass = 'tag-plus'; // Classe Prateada
                        $iconClass = 'fa-gem';
                        $customStyle = "";
                    } elseif (stripos($planName, 'Premium') !== false) {
                        $tagClass = 'tag-premium'; // Classe Bronze
                        $iconClass = 'fa-award';
                        $customStyle = "";
                    }
                ?>
                <a href="<?= url('/perfil/' . $profile['slug']) ?>" class="group bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-card hover:shadow-2xl hover:-translate-y-2 transition duration-500 flex flex-col relative h-full">
                    
                    <div class="aspect-[3/4] relative bg-slate-100 overflow-hidden">
                        <img src="<?= $imgUrl ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110" loading="lazy" alt="Foto de <?= htmlspecialchars($profile['display_name']) ?>">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent opacity-60 group-hover:opacity-80 transition duration-300"></div>
                        
                        <div class="absolute top-3 left-3 z-10">
                            <span class="<?= $tagClass ?> text-[10px] font-extrabold px-3 py-1.5 rounded-lg flex items-center gap-1.5 uppercase tracking-wide transform group-hover:scale-105 transition" style="<?= $customStyle ?>">
                                <i class="fas <?= $iconClass ?> text-[9px]"></i> <?= htmlspecialchars($planName) ?>
                            </span>
                        </div>

                        <div class="absolute top-3 right-3 z-10">
                            <span class="relative flex h-3 w-3" title="Online Agora">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 border-2 border-white shadow-sm"></span>
                            </span>
                        </div>

                        <div class="absolute bottom-0 left-0 w-full p-5 text-white z-10 translate-y-2 group-hover:translate-y-0 transition duration-300">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h3 class="font-display font-bold text-xl leading-none mb-1.5 shadow-sm">
                                        <?= htmlspecialchars($profile['display_name']) ?>
                                    </h3>
                                    <p class="text-xs font-medium text-slate-300 flex items-center gap-1.5">
                                        <i class="fas fa-map-marker-alt text-pink-500"></i> <?= $loc ?>
                                    </p>
                                </div>
                                <?php if($age): ?>
                                    <div class="bg-white/10 backdrop-blur-md px-2.5 py-1 rounded-lg text-xs font-bold border border-white/20 shadow-sm text-slate-100">
                                        <?= $age ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-16 text-center">
            <a href="#" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-pink-600 hover:border-pink-200 transition px-8 py-4 rounded-full border border-slate-200 bg-white shadow-lg shadow-slate-100/50 group">
                Ver todos os perfis <i class="fas fa-arrow-down group-hover:translate-y-1 transition"></i>
            </a>
        </div>
    </section>

    <footer class="bg-white border-t border-slate-100 py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2 opacity-80 hover:opacity-100 transition">
                <span class="bg-slate-900 text-white w-7 h-7 rounded flex items-center justify-center font-bold text-xs">T</span>
                <p class="font-display font-bold text-lg text-slate-900">TOP<span class="text-pink-600">Model</span></p>
            </div>
            
            <div class="flex gap-8 text-sm font-medium text-slate-500">
                <a href="#" class="hover:text-pink-600 transition">Termos de Uso</a>
                <a href="#" class="hover:text-pink-600 transition">Privacidade</a>
                <a href="#" class="hover:text-pink-600 transition">Anuncie Aqui</a>
            </div>
            
            <p class="text-slate-400 text-xs">© <?= date('Y') ?> TOP Model. Todos os direitos reservados.</p>
        </div>
    </footer>

</body>
</html>