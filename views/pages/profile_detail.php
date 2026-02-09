<?php
// views/pages/profile_detail.php

// 1. SUPRESSÃO DE ERROS E BUFFER (Segurança visual)
ob_start();
error_reporting(0); 
ini_set('display_errors', 0);

use App\Core\Database;

$db = Database::getInstance();

// 2. TRATAMENTO DE DADOS
$workingHours = is_string($profile['working_hours'] ?? '') ? json_decode($profile['working_hours'], true) : ($profile['working_hours'] ?? []);
$serviceDetails = is_string($profile['service_details'] ?? '') ? json_decode($profile['service_details'], true) : ($profile['service_details'] ?? []);
$locations = $locations ?? []; 
$photos = $photos ?? []; 

// Prepara fotos para o Lightbox
$galleryUrls = [];
foreach($photos as $p) {
    if($p['is_approved']) {
        $galleryUrls[] = url('/' . $p['file_path']);
    }
}

// --- BUSCA DE AVALIAÇÕES ---
$reviews = [];
$avgRating = 0;
$totalReviews = 0;
try {
    $stmtReviews = $db->getConnection()->prepare("SELECT * FROM profile_reviews WHERE profile_id = ? ORDER BY created_at DESC");
    $stmtReviews->execute([$profile['id']]);
    $reviews = $stmtReviews->fetchAll(\PDO::FETCH_ASSOC);

    $totalReviews = count($reviews);
    if ($totalReviews > 0) {
        $sum = array_sum(array_column($reviews, 'rating'));
        $avgRating = number_format($sum / $totalReviews, 1);
    }
} catch (\Exception $e) {}

// --- BUSCA DE IDIOMAS ---
$languages = [];
try {
    $stmtLang = $db->getConnection()->prepare("SELECT * FROM profile_languages WHERE profile_id = ?");
    $stmtLang->execute([$profile['id']]);
    $languages = $stmtLang->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) {}

// --- LÓGICA DO BADGE (VIA TABELA SUBSCRIPTIONS) ---
// Hierarquia: VIP (3) > PLUS (2) > PREMIUM (1)
$activeBadge = null;
$highestLevel = 0; 

try {
    // 1. Consulta TODAS as assinaturas ativas na tabela subscriptions
    // Isso garante que pegamos tudo que o usuário pagou e está valendo
    $stmtSubs = $db->getConnection()->prepare("
        SELECT p.name 
        FROM subscriptions s 
        JOIN plans p ON s.plan_id = p.id 
        WHERE s.profile_id = ? 
          AND s.status = 'active' 
          AND s.expires_at > NOW()
    ");
    $stmtSubs->execute([$profile['id']]);
    $activePlanNames = $stmtSubs->fetchAll(\PDO::FETCH_COLUMN);

    if ($activePlanNames) {
        foreach ($activePlanNames as $name) {
            $n = strtolower($name);
            $currentScore = 0;

            // Pontuação por Hierarquia
            if (strpos($n, 'vip') !== false) {
                $currentScore = 3;
            } elseif (strpos($n, 'plus') !== false) {
                $currentScore = 2;
            } elseif (strpos($n, 'premium') !== false) {
                $currentScore = 1;
            }

            // Mantém sempre o maior nível encontrado
            if ($currentScore > $highestLevel) {
                $highestLevel = $currentScore;
            }
        }
    }
} catch (\Exception $e) {}

// Fallback: Se não achou na subscriptions (ex: erro ou plano manual antigo), olha o perfil
if ($highestLevel === 0) {
    $dbLevel = (int)($profile['current_plan_level'] ?? 0);
    // Mapeia nível do banco (se sua lógica de banco for 3=VIP, 2=Plus, 1=Premium)
    if ($dbLevel >= 3) $highestLevel = 3;
    elseif ($dbLevel == 2) $highestLevel = 2;
    elseif ($dbLevel == 1) $highestLevel = 1;
}

// 2. Define o visual baseado no vencedor da hierarquia
if ($highestLevel === 3) {
    // VIP - Topo
    $activeBadge = ['label' => 'VIP', 'color' => 'bg-pink-600 text-white border-pink-700 shadow-lg shadow-pink-200', 'icon' => 'fas fa-crown'];
} elseif ($highestLevel === 2) {
    // PLUS - Médio
    $activeBadge = ['label' => 'PLUS', 'color' => 'bg-purple-600 text-white border-purple-700', 'icon' => 'fas fa-star'];
} elseif ($highestLevel === 1) {
    // PREMIUM - Base
    $activeBadge = ['label' => 'PREMIUM', 'color' => 'bg-slate-800 text-white border-slate-900', 'icon' => 'fas fa-gem'];
}


// --- FOTO PRINCIPAL ---
$imgSource = $profile['profile_image'] ?? null;
if (empty($imgSource) && !empty($photos)) {
    $firstPhoto = reset($photos); 
    $imgSource = $firstPhoto['file_path'] ?? null;
}
$profileImg = !empty($imgSource) ? url('/' . $imgSource) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=db2777&color=fff&size=512';

// --- IDADE ---
$age = 'N/A';
if(!empty($profile['birth_date'])) {
    try {
        $age = (new DateTime($profile['birth_date']))->diff(new DateTime())->y;
    } catch(Exception $e) {}
}

// WhatsApp
$phoneClean = preg_replace('/[^0-9]/', '', $profile['phone'] ?? '');
$whatsappLink = "https://wa.me/55{$phoneClean}?text=" . urlencode("Olá {$profile['display_name']}, vi seu perfil no TOP Model.");

// Tradutor
function translateAttr($val, $type = 'general') {
    if (empty($val)) return '--';
    $maps = [
        'hair' => ['loira'=>'Loira', 'morena'=>'Morena', 'ruiva'=>'Ruiva', 'preto'=>'Pretos', 'colorido'=>'Colorido'],
        'eye' => ['castanhos'=>'Castanhos', 'verdes'=>'Verdes', 'azuis'=>'Azuis', 'pretos'=>'Pretos'],
        'ethnicity' => ['white' => 'Branca', 'caucasian' => 'Branca', 'black' => 'Negra', 'mixed' => 'Parda/Morena', 'latin' => 'Latina', 'asian' => 'Asiática', 'indigenous' => 'Indígena', 'indian' => 'Indiana', 'middle_eastern' => 'Oriental']
    ];
    return $maps[$type][strtolower($val)] ?? ucfirst($val);
}

// Localização
$locationDisplay = "Localização não informada";
if (!empty($profile['city_name'])) {
    $locationDisplay = htmlspecialchars($profile['city_name']);
    if (!empty($profile['country_name'])) $locationDisplay .= ', ' . htmlspecialchars($profile['country_name']);
} elseif (!empty($profile['country_name'])) {
    $locationDisplay = htmlspecialchars($profile['country_name']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['display_name']) ?> - Perfil Oficial</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
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
        body { background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 24px 24px; }
        .panel { background: white; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); }
        @media (min-width: 1024px) { .sticky-sidebar { position: sticky; top: 5.5rem; } }
        
        /* Lightbox Styles */
        .lightbox-nav-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15); color: white;
            border: none; width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; z-index: 110;
            backdrop-filter: blur(4px);
        }
        .lightbox-nav-btn:hover { background: rgba(255, 255, 255, 0.4); transform: translateY(-50%) scale(1.1); }
        .lightbox-nav-btn:active { transform: translateY(-50%) scale(0.95); }
        #prevBtn { left: 20px; }
        #nextBtn { right: 20px; }
        
        @media (max-width: 768px) {
            .lightbox-nav-btn { width: 40px; height: 40px; background: rgba(0,0,0,0.3); }
            #prevBtn { left: 10px; }
            #nextBtn { right: 10px; }
        }
    </style>
</head>
<body class="text-slate-600 antialiased">

    <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 h-16">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition uppercase tracking-wide">
                <i class="fas fa-chevron-left"></i> Voltar
            </a>
            <div class="flex items-center gap-2">
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
            </div>
            <div class="w-16"></div> 
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-24 pb-20 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <aside class="lg:col-span-4 space-y-6 sticky-sidebar">
            
            <div class="panel p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-br from-slate-100 to-slate-200"></div>
                
                <div class="relative text-center mt-8">
                    <div class="relative inline-block">
                        <img src="<?= $profileImg ?>" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md mx-auto bg-white cursor-pointer hover:scale-105 transition" onclick="openLightbox('<?= $profileImg ?>')">
                        <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full" title="Online"></div>
                    </div>
                    
                    <h1 class="font-display font-bold text-2xl text-slate-900 mt-3 flex justify-center items-center gap-1">
                        <?= htmlspecialchars($profile['display_name']) ?>
                        <i class="fas fa-check-circle text-blue-500 text-lg"></i>
                    </h1>

                    <div class="flex justify-center items-center gap-1 mt-1 mb-2">
                        <div class="flex text-yellow-400 text-xs">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="<?= $i <= round($avgRating) ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs font-bold text-slate-600"><?= $avgRating ?></span>
                        <span class="text-[10px] text-slate-400">(<?= $totalReviews ?> reviews)</span>
                    </div>
                    
                    <div class="flex justify-center gap-2 mt-2 mb-6 flex-wrap">
                        <?php if($activeBadge): ?>
                            <span class="<?= $activeBadge['color'] ?> border px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-sm">
                                <i class="<?= $activeBadge['icon'] ?>"></i> <?= htmlspecialchars($activeBadge['label']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="bg-slate-50 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-map-marker-alt"></i> <?= $locationDisplay ?>
                        </span>
                    </div>

                    <div class="space-y-3">
                        <?php if($profile['whatsapp_enabled']): ?>
                            <a href="<?= $whatsappLink ?>" target="_blank" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-sm transition flex items-center justify-center gap-2 transform active:scale-95">
                                <i class="fab fa-whatsapp text-xl"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                        
                        <?php if($profile['phone']): ?>
                            <a href="tel:<?= $phoneClean ?>" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                                <i class="fas fa-phone-alt"></i> Ligar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider">Ficha Técnica</h3>
                <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Idade</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $age ?> Anos</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Etnia</span>
                        <span class="font-semibold text-slate-700 text-sm capitalize"><?= translateAttr($profile['ethnicity'] ?? '', 'ethnicity') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Gênero</span>
                        <span class="font-semibold text-slate-700 text-sm capitalize"><?= $profile['gender']=='woman'?'Mulher':$profile['gender'] ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Altura</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= !empty($profile['height_cm']) ? $profile['height_cm'].' cm' : '--' ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Peso</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= !empty($profile['weight_kg']) ? $profile['weight_kg'].' kg' : '--' ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Cabelos</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['hair_color'] ?? '', 'hair') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Olhos</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['eye_color'] ?? '', 'eye') ?></span>
                    </div>
                    <?php if(!empty($profile['bust_cm'])): ?>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Busto</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $profile['bust_cm'] ?> cm</span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($profile['waist_cm'])): ?>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Cintura</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $profile['waist_cm'] ?> cm</span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($profile['hips_cm'])): ?>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Quadril</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $profile['hips_cm'] ?> cm</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="border-t border-slate-100 mt-4 pt-4 flex flex-wrap gap-2">
                    <?php if(!empty($profile['tattoos'])): ?>
                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold">Com Tatuagem</span>
                    <?php endif; ?>
                    <?php if(($profile['smoker'] ?? '') == 'no'): ?>
                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-[10px] font-bold">Não Fuma</span>
                    <?php endif; ?>
                    <?php if(($profile['drinker'] ?? '') == 'no'): ?>
                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-[10px] font-bold">Não Bebe</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($languages)): ?>
            <div class="panel p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Idiomas</h3>
                <div class="space-y-2">
                    <?php foreach($languages as $lang): ?>
                        <div class="flex justify-between items-center text-sm border-b border-slate-50 pb-1 last:border-0">
                            <span class="font-medium text-slate-700 flex items-center gap-2">
                                <i class="fas fa-language text-slate-400"></i> <?= htmlspecialchars($lang['language']) ?>
                            </span>
                            <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded text-slate-500 uppercase font-bold"><?= $lang['level'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </aside>

        <main class="lg:col-span-8 space-y-6">

            <section class="panel p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2">
                        <i class="fas fa-camera text-pink-500"></i> Galeria de Fotos
                    </h3>
                    <span class="text-xs font-medium text-slate-400"><?= count($photos) ?> fotos</span>
                </div>

                <?php if(empty($photos)): ?>
                    <div class="bg-slate-50 rounded-xl p-8 text-center border-2 border-dashed border-slate-200">
                        <p class="text-slate-500 text-sm">Nenhuma foto pública disponível.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach($photos as $index => $photo): 
                            if(!$photo['is_approved']) continue;
                            $photoUrl = url('/' . $photo['file_path']);
                        ?>
                            <div class="group relative aspect-[3/4] rounded-lg overflow-hidden cursor-zoom-in bg-slate-100" onclick="openLightbox(<?= $index ?>)">
                                <img src="<?= $photoUrl ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 text-2xl drop-shadow-lg transform scale-50 group-hover:scale-100 transition"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel p-6">
                <h3 class="font-display font-bold text-lg text-slate-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-heart text-pink-500"></i> Sobre Mim
                </h3>
                <div class="prose prose-slate prose-sm max-w-none text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <?= nl2br(htmlspecialchars($profile['bio'] ?? 'Olá! Entre em contato para saber mais.')) ?>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="panel p-5 border-l-4 <?= $profile['incall_available'] ? 'border-pink-500' : 'border-slate-300 opacity-75' ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-slate-800">Incall</h4>
                            <p class="text-xs text-slate-500">Local Próprio</p>
                        </div>
                        <?php if($profile['incall_available']): ?>
                            <i class="fas fa-check-circle text-green-500"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-slate-300"></i>
                        <?php endif; ?>
                    </div>
                    <?php if($profile['incall_available']): ?>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <?php if(!empty($serviceDetails['incall_private'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-home text-pink-400 text-xs w-4"></i> Apartamento Privado</li>
                            <?php endif; ?>
                            <?php if(!empty($serviceDetails['incall_hotel'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-hotel text-pink-400 text-xs w-4"></i> Atende em Hotéis</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">Serviço Indisponível</span>
                    <?php endif; ?>
                </div>

                <div class="panel p-5 border-l-4 <?= $profile['outcall_available'] ? 'border-indigo-500' : 'border-slate-300 opacity-75' ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-slate-800">Outcall</h4>
                            <p class="text-xs text-slate-500">Atende Fora</p>
                        </div>
                        <?php if($profile['outcall_available']): ?>
                            <i class="fas fa-check-circle text-green-500"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-slate-300"></i>
                        <?php endif; ?>
                    </div>
                    <?php if($profile['outcall_available']): ?>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <?php if(!empty($serviceDetails['outcall_hotel'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-bed text-indigo-400 text-xs w-4"></i> Vai até Hotéis/Motéis</li>
                            <?php endif; ?>
                            <?php if(!empty($serviceDetails['outcall_home'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-building text-indigo-400 text-xs w-4"></i> Vai até Residências</li>
                            <?php endif; ?>
                            <?php if(!empty($serviceDetails['outcall_events'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-glass-cheers text-indigo-400 text-xs w-4"></i> Acompanha em Eventos</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">Serviço Indisponível</span>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel p-6">
                <h3 class="font-display font-bold text-lg text-slate-800 mb-4 flex items-center gap-2">
                    <i class="far fa-clock text-pink-500"></i> Disponibilidade
                </h3>

                <?php if($profile['is_24_7']): ?>
                    <div class="bg-green-50 border border-green-100 rounded-lg p-4 flex items-center gap-4">
                        <div class="bg-white p-2 rounded-full shadow-sm text-green-500"><i class="fas fa-bolt"></i></div>
                        <div>
                            <h4 class="font-bold text-green-800 text-sm">Disponível 24 Horas</h4>
                            <p class="text-green-600 text-xs">Atendimento a qualquer momento.</p>
                        </div>
                    </div>
                <?php elseif($profile['show_as_night']): ?>
                    <div class="bg-slate-900 border border-slate-700 rounded-lg p-4 flex items-center gap-4 text-white">
                        <div class="bg-white/10 p-2 rounded-full text-yellow-400"><i class="fas fa-moon"></i></div>
                        <div>
                            <h4 class="font-bold text-sm">Plantão Noturno</h4>
                            <p class="text-slate-300 text-xs">Preferência pelo período da noite.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <?php 
                            $days = ['seg'=>'Segunda', 'ter'=>'Terça', 'qua'=>'Quarta', 'qui'=>'Quinta', 'sex'=>'Sexta', 'sab'=>'Sábado', 'dom'=>'Domingo'];
                            foreach($days as $key => $label): 
                                $isOpen = isset($workingHours[$key]['active']) && $workingHours[$key]['active'] == 1;
                        ?>
                            <div class="flex justify-between py-2 border-b border-slate-50 last:border-0">
                                <span class="text-slate-600"><?= $label ?></span>
                                <?php if($isOpen): ?>
                                    <span class="font-bold text-slate-800"><?= $workingHours[$key]['start'] ?> - <?= $workingHours[$key]['end'] ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs uppercase">Fechado</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2">
                            <i class="fas fa-star text-yellow-500"></i> Avaliações
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">O que dizem sobre <?= htmlspecialchars($profile['display_name']) ?></p>
                    </div>
                    <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                        <span class="text-2xl font-bold text-slate-800"><?= $avgRating ?></span>
                        <div class="flex flex-col items-end">
                            <div class="flex text-yellow-400 text-xs">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="<?= $i <= round($avgRating) ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[10px] text-slate-400"><?= $totalReviews ?> opiniões</span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <?php if(isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'member'): ?>
                        <div class="bg-white p-5 rounded-2xl mb-8 border border-slate-200 shadow-sm relative group focus-within:ring-2 focus-within:ring-pink-100 transition duration-300">
                            <div class="absolute top-0 left-0 w-1 h-full bg-pink-500 rounded-l-2xl"></div>
                            <h4 class="text-sm font-bold text-slate-700 mb-3">Deixe sua avaliação</h4>
                            
                            <form onsubmit="submitReview(event)">
                                <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                                
                                <div class="mb-4 flex items-center gap-3">
                                    <span class="text-xs text-slate-500 font-medium">Sua nota:</span>
                                    <div class="flex gap-1 text-xl text-slate-300 cursor-pointer" id="star-input">
                                        <i class="fas fa-star hover:text-yellow-400 hover:scale-110 transition" onclick="setRating(1)"></i>
                                        <i class="fas fa-star hover:text-yellow-400 hover:scale-110 transition" onclick="setRating(2)"></i>
                                        <i class="fas fa-star hover:text-yellow-400 hover:scale-110 transition" onclick="setRating(3)"></i>
                                        <i class="fas fa-star hover:text-yellow-400 hover:scale-110 transition" onclick="setRating(4)"></i>
                                        <i class="fas fa-star hover:text-yellow-400 hover:scale-110 transition" onclick="setRating(5)"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" value="5">
                                </div>
                                
                                <textarea name="comment" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:bg-white focus:border-pink-500 focus:outline-none transition resize-none" placeholder="Conte como foi sua experiência..." required></textarea>
                                
                                <div class="flex justify-end mt-3">
                                    <button class="bg-slate-900 text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-800 transition shadow-lg shadow-slate-200 transform active:scale-95">
                                        Publicar Avaliação
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php elseif(isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'model'): ?>
                        <div class="bg-slate-50 p-4 rounded-xl mb-8 border border-slate-200 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                            <i class="fas fa-info-circle"></i> Modelos não podem avaliar outros perfis.
                        </div>
                    <?php elseif(!isset($_SESSION['user_id'])): ?>
                        <div class="bg-gradient-to-r from-slate-50 to-white p-6 rounded-xl mb-8 border border-dashed border-slate-300 text-center">
                            <p class="text-slate-500 text-sm mb-2">Quer avaliar esta modelo?</p>
                            <a href="<?= url('/login') ?>" class="inline-block bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold hover:border-pink-500 hover:text-pink-600 transition shadow-sm">
                                Faça Login como Membro
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if(empty($reviews)): ?>
                        <div class="text-center py-10 opacity-50">
                            <i class="far fa-comment-dots text-4xl text-slate-300 mb-3"></i>
                            <p class="text-slate-400 text-sm italic">Seja o primeiro a avaliar!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach($reviews as $rev): 
                                $initial = substr($rev['reviewer_name'] ?? 'U', 0, 1);
                                $colorClasses = ['bg-pink-500', 'bg-purple-500', 'bg-indigo-500', 'bg-blue-500', 'bg-teal-500'];
                                $bgColor = $colorClasses[rand(0, 4)];
                            ?>
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full <?= $bgColor ?> text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">
                                            <?= $initial ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-grow">
                                        <div class="bg-white rounded-2xl rounded-tl-none border border-slate-100 p-4 shadow-sm hover:shadow-md transition duration-300">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <span class="block text-sm font-bold text-slate-800"><?= htmlspecialchars($rev['reviewer_name'] ?? 'Membro') ?></span>
                                                    <span class="text-[10px] text-slate-400"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                                </div>
                                                <div class="flex text-yellow-400 text-[10px] bg-yellow-50 px-2 py-1 rounded-full border border-yellow-100">
                                                    <?php for($i=0; $i<5; $i++): ?>
                                                        <i class="<?= $i < $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            
                                            <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($rev['comment']) ?></p>
                                        </div>

                                        <?php if(!empty($rev['reply'])): ?>
                                            <div class="ml-4 mt-2 flex gap-3">
                                                <div class="flex-shrink-0 mt-2">
                                                    <img src="<?= $profileImg ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm" alt="Modelo">
                                                </div>
                                                <div class="bg-slate-50 p-4 rounded-2xl rounded-tl-none border border-slate-200 w-full relative">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-[10px] font-bold text-pink-600 bg-pink-50 px-2 py-0.5 rounded border border-pink-100 uppercase tracking-wide">
                                                            <i class="fas fa-check-circle"></i> Resposta da Modelo
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-600 italic leading-relaxed">"<?= htmlspecialchars($rev['reply']) ?>"</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>

    <?php if($profile['whatsapp_enabled']): ?>
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white/90 backdrop-blur border-t border-slate-200 lg:hidden z-40">
        <a href="<?= $whatsappLink ?>" target="_blank" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg flex justify-center items-center gap-2">
            <i class="fab fa-whatsapp text-xl"></i> WhatsApp
        </a>
    </div>
    <?php endif; ?>

    <div id="lightbox" class="fixed inset-0 z-[100] bg-black/95 hidden flex items-center justify-center p-4 backdrop-blur-sm select-none" onclick="closeLightbox()">
        <button class="absolute top-4 right-4 text-white text-4xl hover:text-pink-500 transition focus:outline-none z-[120] p-2" onclick="closeLightbox()">&times;</button>
        
        <button id="prevBtn" class="lightbox-nav-btn" onclick="event.stopPropagation(); navigateLightbox(-1)">
            <i class="fas fa-chevron-left text-2xl"></i>
        </button>
        
        <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl transform transition-transform duration-300 scale-95 opacity-0 object-contain z-[110]" onclick="event.stopPropagation()">
        
        <button id="nextBtn" class="lightbox-nav-btn" onclick="event.stopPropagation(); navigateLightbox(1)">
            <i class="fas fa-chevron-right text-2xl"></i>
        </button>
    </div>

    <script>
        // Dados da Galeria para Navegação
        const galleryImages = <?= json_encode($galleryUrls) ?>;
        let currentImageIndex = 0;

        function openLightbox(indexOrUrl) {
            const modal = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            // Lógica para abrir pelo índice ou pela URL (caso clique na foto de perfil)
            if (typeof indexOrUrl === 'number') {
                currentImageIndex = indexOrUrl;
                img.src = galleryImages[currentImageIndex];
                // Mostra botões apenas se houver mais de uma foto
                const showNav = galleryImages.length > 1;
                prevBtn.style.display = showNav ? 'flex' : 'none';
                nextBtn.style.display = showNav ? 'flex' : 'none';
            } else {
                // Se for URL direta (foto de perfil), esconde navegação
                img.src = indexOrUrl;
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                img.classList.remove('scale-95', 'opacity-0');
                img.classList.add('scale-100', 'opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden'; 
        }

        function closeLightbox() {
            const modal = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            
            img.classList.remove('scale-100', 'opacity-100');
            img.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                img.src = '';
                document.body.style.overflow = '';
            }, 300);
        }

        function navigateLightbox(direction) {
            if (galleryImages.length <= 1) return;

            // Calcula novo índice (circular)
            currentImageIndex += direction;
            if (currentImageIndex < 0) currentImageIndex = galleryImages.length - 1;
            if (currentImageIndex >= galleryImages.length) currentImageIndex = 0;

            // Efeito de troca suave
            const img = document.getElementById('lightbox-img');
            img.style.opacity = '0.5';
            setTimeout(() => {
                img.src = galleryImages[currentImageIndex];
                img.style.opacity = '1';
            }, 150);
        }

        // Teclado
        document.addEventListener('keydown', function(event) {
            if (document.getElementById('lightbox').classList.contains('hidden')) return;
            
            if (event.key === "Escape") closeLightbox();
            if (event.key === "ArrowLeft") navigateLightbox(-1);
            if (event.key === "ArrowRight") navigateLightbox(1);
        });

        // Review
        function setRating(val) {
            document.getElementById('rating-value').value = val;
            const stars = document.querySelectorAll('#star-input i');
            stars.forEach((s, i) => { s.className = (i < val) ? 'fas fa-star text-yellow-400 hover:scale-110' : 'far fa-star text-slate-300 hover:scale-110'; });
        }
        async function submitReview(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const old = btn.innerText; btn.innerText='Enviando...'; btn.disabled=true;
            const fd = new FormData(e.target);
            try {
                const res = await fetch('<?= url('/api/reviews/create') ?>', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(Object.fromEntries(fd))
                });
                const json = await res.json();
                if(json.success) location.reload(); else alert(json.message || 'Erro ao enviar');
            } catch(e){alert('Erro de conexão');}
            finally{btn.innerText=old; btn.disabled=false;}
        }
    </script>

</body>
</html>