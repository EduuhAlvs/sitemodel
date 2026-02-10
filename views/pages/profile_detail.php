<?php
// views/pages/profile_detail.php

// 1. CONFIGURAÇÃO
ob_start();
error_reporting(0); 
ini_set('display_errors', 0);

use App\Core\Database;
$db = Database::getInstance();

// 2. MAPAS DE TRADUÇÃO
$mapSimNao = [0 => 'Não', '0' => 'Não', 1 => 'Sim', '1' => 'Sim'];
$mapSmoker = ['yes' => 'Sim', 'no' => 'Não'];
$mapDrinker = ['yes' => 'Sim', 'no' => 'Não', 'occasionally' => 'Socialmente'];

// Mapa Depilação
$mapShaving = [
    'full' => 'Depilação Completa',
    'partial' => 'Parcialmente Depilada',
    'natural' => 'Ao Natural',
    '' => '--'
];

// Tradutor de Atributos
function translateAttr($val, $type = 'general') {
    if (empty($val)) return '--';
    $maps = [
        'hair' => ['loira'=>'Loira', 'morena'=>'Morena', 'ruiva'=>'Ruiva', 'preto'=>'Pretos', 'colorido'=>'Colorido'],
        'eye' => ['castanhos'=>'Castanhos', 'verdes'=>'Verdes', 'azuis'=>'Azuis', 'pretos'=>'Pretos'],
        'ethnicity' => ['white' => 'Branca', 'caucasian' => 'Branca', 'black' => 'Negra', 'mixed' => 'Parda/Morena', 'latin' => 'Latina', 'asian' => 'Asiática', 'indigenous' => 'Indígena', 'indian' => 'Indiana', 'middle_eastern' => 'Oriental']
    ];
    return $maps[$type][strtolower($val)] ?? ucfirst($val);
}

// 3. Tratamento de Dados
$workingHours = is_string($profile['working_hours'] ?? '') ? json_decode($profile['working_hours'], true) : ($profile['working_hours'] ?? []);
$serviceDetails = is_string($profile['service_details'] ?? '') ? json_decode($profile['service_details'], true) : ($profile['service_details'] ?? []);
$locations = $locations ?? []; 
$photos = $photos ?? []; 

// Idade
$age = 'N/A';
if (!empty($profile['birth_date'])) {
    try {
        $age = (new DateTime($profile['birth_date']))->diff(new DateTime())->y . ' Anos';
    } catch (Exception $e) { $age = '--'; }
}

// Galeria
$galleryUrls = [];
foreach($photos as $p) {
    if($p['is_approved']) {
        $galleryUrls[] = url('/' . $p['file_path']);
    }
}

// --- BUSCA DE COMENTÁRIOS (QUERY CORRIGIDA - SEM JOIN QUEBRADO) ---
$reviews = [];
$avgRating = 0;
$totalReviews = 0;
try {
    // Consulta direta na tabela de reviews para garantir que funcione
    $stmtReviews = $db->getConnection()->prepare("
        SELECT * FROM profile_reviews 
        WHERE profile_id = ? 
        ORDER BY created_at DESC
    ");
    $stmtReviews->execute([$profile['id']]);
    $reviews = $stmtReviews->fetchAll(\PDO::FETCH_ASSOC);

    $totalReviews = count($reviews);
    if ($totalReviews > 0) {
        $sum = array_sum(array_column($reviews, 'rating'));
        $avgRating = number_format($sum / $totalReviews, 1);
    }
} catch (\Exception $e) { /* Silêncio */ }

// Foto Principal
$imgSource = $profile['profile_image'] ?? null;
if (empty($imgSource) && !empty($photos)) {
    $firstPhoto = reset($photos); 
    $imgSource = $firstPhoto['file_path'] ?? null;
}
$profileImg = !empty($imgSource) ? url('/' . $imgSource) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=db2777&color=fff&size=512';

// WhatsApp
$phoneClean = preg_replace('/[^0-9]/', '', $profile['phone'] ?? '');
$whatsappLink = "https://wa.me/55{$phoneClean}?text=" . urlencode("Olá {$profile['display_name']}, vi seu perfil no TOP Model.");

// Localização
$locationDisplay = "Não informada";
if (!empty($profile['city_name'])) {
    $locationDisplay = htmlspecialchars($profile['city_name']);
    if (!empty($profile['country_name'])) $locationDisplay .= ', ' . htmlspecialchars($profile['country_name']);
}

// Badge
$activeBadge = null;
$level = (int)($profile['current_plan_level'] ?? 0);
if ($level >= 3) $activeBadge = ['label' => 'VIP', 'color' => 'bg-pink-600 text-white border-pink-700 shadow-lg shadow-pink-200', 'icon' => 'fas fa-crown'];
elseif ($level == 2) $activeBadge = ['label' => 'PLUS', 'color' => 'bg-purple-600 text-white border-purple-700', 'icon' => 'fas fa-star'];
elseif ($level == 1) $activeBadge = ['label' => 'PREMIUM', 'color' => 'bg-slate-800 text-white border-slate-900', 'icon' => 'fas fa-gem'];

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
        
        .lightbox-nav-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15); color: white;
            border: none; width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; z-index: 110;
            backdrop-filter: blur(4px);
        }
        .lightbox-nav-btn:hover { background: rgba(255, 255, 255, 0.4); transform: translateY(-50%) scale(1.1); }
        #prevBtn { left: 20px; } #nextBtn { right: 20px; }
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
            <div class="flex items-center gap-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?= url('/dashboard') ?>" class="text-xs font-bold text-slate-700 hover:text-pink-600 transition uppercase">Meu Painel</a>
                    <a href="<?= url('/logout') ?>" class="text-xs font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition uppercase">Sair</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="text-xs font-bold text-slate-700 hover:text-pink-600 transition uppercase">Entrar</a>
                    <a href="<?= url('/register') ?>" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-pink-600 transition">Criar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-24 pb-20 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <aside class="lg:col-span-4 space-y-6 sticky-sidebar">
            
            <div class="panel p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-br from-slate-100 to-slate-200"></div>
                
                <div class="relative text-center mt-8">
                    <div class="relative inline-block">
                        <img src="<?= $profileImg ?>" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md mx-auto bg-white cursor-pointer hover:scale-105 transition" onclick="openLightbox('<?= $profileImg ?>')">
                        <?php if(isset($profile['status']) && $profile['status'] === 'active'): ?>
                            <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full" title="Online"></div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="font-display font-bold text-2xl text-slate-900 mt-3 flex justify-center items-center gap-1">
                        <?= htmlspecialchars($profile['display_name']) ?>
                        <?php if($highestLevel >= 2): ?><i class="fas fa-check-circle text-blue-500 text-lg"></i><?php endif; ?>
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
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Idade</span><span class="font-semibold text-slate-700 text-sm"><?= $age ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Etnia</span><span class="font-semibold text-slate-700 text-sm capitalize"><?= translateAttr($profile['ethnicity'] ?? '', 'ethnicity') ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Gênero</span><span class="font-semibold text-slate-700 text-sm capitalize"><?= $profile['gender']=='woman'?'Mulher':$profile['gender'] ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Altura</span><span class="font-semibold text-slate-700 text-sm"><?= !empty($profile['height_cm']) ? $profile['height_cm'].' cm' : '--' ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Peso</span><span class="font-semibold text-slate-700 text-sm"><?= !empty($profile['weight_kg']) ? $profile['weight_kg'].' kg' : '--' ?></span></div>
                    
                    <?php if(!empty($profile['bust_cm'])): ?><div class="flex flex-col"><span class="text-[10px] text-slate-400">Busto</span><span class="font-semibold text-slate-700 text-sm"><?= $profile['bust_cm'] ?> cm</span></div><?php endif; ?>
                    <?php if(!empty($profile['waist_cm'])): ?><div class="flex flex-col"><span class="text-[10px] text-slate-400">Cintura</span><span class="font-semibold text-slate-700 text-sm"><?= $profile['waist_cm'] ?> cm</span></div><?php endif; ?>
                    <?php if(!empty($profile['hips_cm'])): ?><div class="flex flex-col"><span class="text-[10px] text-slate-400">Quadril</span><span class="font-semibold text-slate-700 text-sm"><?= $profile['hips_cm'] ?> cm</span></div><?php endif; ?>
                    <?php if(!empty($profile['cup_size'])): ?><div class="flex flex-col"><span class="text-[10px] text-slate-400">Seios</span><span class="font-semibold text-slate-700 text-sm"><?= htmlspecialchars($profile['cup_size']) ?></span></div><?php endif; ?>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Silicone</span><span class="font-semibold text-slate-700 text-sm"><?= $mapSimNao[$profile['silicone'] ?? 0] ?></span></div>
                    <?php if(!empty($profile['shaving'])): ?><div class="flex flex-col"><span class="text-[10px] text-slate-400">Depilação</span><span class="font-semibold text-slate-700 text-sm"><?= $mapShaving[$profile['shaving']] ?? '--' ?></span></div><?php endif; ?>

                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Cabelos</span><span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['hair_color'] ?? '', 'hair') ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Olhos</span><span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['eye_color'] ?? '', 'eye') ?></span></div>
                </div>
                
                <div class="border-t border-slate-100 mt-4 pt-4 grid grid-cols-2 gap-y-4 gap-x-2">
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Tatuagem</span><span class="font-semibold text-slate-700 text-sm"><?= $mapSimNao[$profile['tattoos'] ?? 0] ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Fuma</span><span class="font-semibold text-slate-700 text-sm"><?= $mapSmoker[$profile['smoker'] ?? 'no'] ?? 'Não' ?></span></div>
                    <div class="flex flex-col"><span class="text-[10px] text-slate-400">Bebe</span><span class="font-semibold text-slate-700 text-sm"><?= $mapDrinker[$profile['drinker'] ?? 'no'] ?? 'Não' ?></span></div>
                </div>
            </div>

            <?php if(!empty($languages)): ?>
            <div class="panel p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Idiomas</h3>
                <div class="space-y-2">
                    <?php foreach($languages as $lang): ?>
                        <div class="flex justify-between items-center text-sm border-b border-slate-50 pb-1 last:border-0">
                            <span class="font-medium text-slate-700 flex items-center gap-2"><i class="fas fa-language text-slate-400"></i> <?= htmlspecialchars($lang['language']) ?></span>
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
                    <h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2"><i class="fas fa-camera text-pink-500"></i> Galeria de Fotos</h3>
                    <span class="text-xs font-medium text-slate-400"><?= count($photos) ?> fotos</span>
                </div>
                <?php if(empty($photos)): ?>
                    <div class="bg-slate-50 rounded-xl p-8 text-center border-2 border-dashed border-slate-200"><p class="text-slate-500 text-sm">Nenhuma foto pública disponível.</p></div>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach($photos as $index => $photo): if(!$photo['is_approved']) continue; $photoUrl = url('/' . $photo['file_path']); ?>
                            <div class="group relative aspect-[3/4] rounded-lg overflow-hidden cursor-zoom-in bg-slate-100" onclick="openLightbox(<?= $index ?>)">
                                <img src="<?= $photoUrl ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center"><i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 text-2xl drop-shadow-lg transform scale-50 group-hover:scale-100 transition"></i></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel p-6">
                <h3 class="font-display font-bold text-lg text-slate-800 mb-3 flex items-center gap-2"><i class="fas fa-heart text-pink-500"></i> Sobre Mim</h3>
                <div class="prose prose-slate prose-sm max-w-none text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100"><?= nl2br(htmlspecialchars($profile['bio'] ?? 'Olá! Entre em contato para saber mais.')) ?></div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="panel p-5 bg-gradient-to-br from-pink-50/50 to-white border border-pink-100/80">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-pink-100 flex items-center justify-center text-pink-600"><i class="fas fa-home text-lg"></i></div>
                            <div><h4 class="font-bold text-slate-800 text-sm">Local Próprio (Incall)</h4><p class="text-[10px] text-slate-500 uppercase tracking-wide">Recebe no local</p></div>
                        </div>
                        <?php if($profile['incall_available']): ?><span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-full border border-green-200">Disponível</span><?php else: ?><span class="px-2 py-1 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full">Indisponível</span><?php endif; ?>
                    </div>
                    <?php if($profile['incall_available']): ?>
                        <div class="space-y-2 mt-3">
                            <?php if(!empty($serviceDetails['incall_private'])): ?><div class="flex items-center gap-2 text-xs text-slate-600 bg-white/80 p-2 rounded-lg border border-pink-100/50"><i class="fas fa-check-circle text-pink-500"></i> Apartamento Privado</div><?php endif; ?>
                            <?php if(!empty($serviceDetails['incall_hotel'])): ?><div class="flex items-center gap-2 text-xs text-slate-600 bg-white/80 p-2 rounded-lg border border-pink-100/50"><i class="fas fa-check-circle text-pink-500"></i> Hotel</div><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel p-5 bg-gradient-to-br from-indigo-50/50 to-white border border-indigo-100/80">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600"><i class="fas fa-car text-lg"></i></div>
                            <div><h4 class="font-bold text-slate-800 text-sm">Atendimento Externo (Outcall)</h4><p class="text-[10px] text-slate-500 uppercase tracking-wide">Vai até você</p></div>
                        </div>
                        <?php if($profile['outcall_available']): ?><span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-full border border-green-200">Disponível</span><?php else: ?><span class="px-2 py-1 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full">Indisponível</span><?php endif; ?>
                    </div>
                    <?php if($profile['outcall_available']): ?>
                        <div class="space-y-2 mt-3">
                            <?php if(!empty($serviceDetails['outcall_hotel'])): ?><div class="flex items-center gap-2 text-xs text-slate-600 bg-white/80 p-2 rounded-lg border border-indigo-100/50"><i class="fas fa-check-circle text-indigo-500"></i> Hotéis/Motéis</div><?php endif; ?>
                            <?php if(!empty($serviceDetails['outcall_home'])): ?><div class="flex items-center gap-2 text-xs text-slate-600 bg-white/80 p-2 rounded-lg border border-indigo-100/50"><i class="fas fa-check-circle text-indigo-500"></i> Residências</div><?php endif; ?>
                            <?php if(!empty($serviceDetails['outcall_events'])): ?><div class="flex items-center gap-2 text-xs text-slate-600 bg-white/80 p-2 rounded-lg border border-indigo-100/50"><i class="fas fa-check-circle text-indigo-500"></i> Eventos</div><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel p-6">
                <h3 class="font-display font-bold text-lg text-slate-800 mb-4 flex items-center gap-2"><i class="far fa-clock text-pink-500"></i> Disponibilidade</h3>
                <?php if($profile['is_24_7']): ?>
                    <div class="bg-green-50 border border-green-100 rounded-lg p-4 flex items-center gap-4">
                        <div class="bg-white p-2 rounded-full shadow-sm text-green-500"><i class="fas fa-bolt"></i></div>
                        <div><h4 class="font-bold text-green-800 text-sm">Disponível 24 Horas</h4><p class="text-green-600 text-xs">Atendimento a qualquer momento.</p></div>
                    </div>
                <?php elseif($profile['show_as_night']): ?>
                    <div class="bg-slate-900 border border-slate-700 rounded-lg p-4 flex items-center gap-4 text-white">
                        <div class="bg-white/10 p-2 rounded-full shadow-sm"><i class="fas fa-moon"></i></div>
                        <div><h4 class="font-bold text-white text-sm">Plantão Noturno</h4><p class="text-slate-400 text-xs">Atendimento preferencial à noite.</p></div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto"><div class="flex gap-2 min-w-max">
                        <?php $daysMap = ['seg'=>'Seg','ter'=>'Ter','qua'=>'Qua','qui'=>'Qui','sex'=>'Sex','sab'=>'Sáb','dom'=>'Dom'];
                        foreach($daysMap as $key => $label): $day = $workingHours[$key] ?? null; $isActive = isset($day['active']) && $day['active'] == '1'; ?>
                        <div class="flex-1 min-w-[80px] p-3 rounded-lg border text-center <?= $isActive ? 'bg-white border-slate-200' : 'bg-slate-50 border-slate-100 opacity-60' ?>">
                            <span class="block text-xs font-bold text-slate-400 uppercase mb-1"><?= $label ?></span>
                            <?php if($isActive): ?><span class="block text-xs font-bold text-slate-800"><?= $day['start'] ?></span><span class="block text-[10px] text-slate-400">até</span><span class="block text-xs font-bold text-slate-800"><?= $day['end'] ?></span><?php else: ?><span class="block text-xs font-medium text-slate-300">Folga</span><?php endif; ?>
                        </div><?php endforeach; ?></div></div>
                <?php endif; ?>
            </section>

            <section class="panel p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2"><i class="far fa-comments text-pink-500"></i> Comentários</h3>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'member'): ?><button onclick="document.getElementById('reviewForm').classList.toggle('hidden')" class="text-xs font-bold text-white bg-primary px-4 py-2 rounded-lg hover:bg-pink-700 transition">Escrever Comentário</button><?php endif; ?>
                </div>

                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'member'): ?>
                <div id="reviewForm" class="hidden mb-8 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                    <form onsubmit="submitReview(event)">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <input type="hidden" name="rating" id="rating-value" required>
                        <div class="flex gap-2 text-2xl text-slate-300 mb-4" id="star-input">
                            <i class="far fa-star cursor-pointer hover:text-yellow-400 transition" onclick="setRating(1)"></i><i class="far fa-star cursor-pointer hover:text-yellow-400 transition" onclick="setRating(2)"></i><i class="far fa-star cursor-pointer hover:text-yellow-400 transition" onclick="setRating(3)"></i><i class="far fa-star cursor-pointer hover:text-yellow-400 transition" onclick="setRating(4)"></i><i class="far fa-star cursor-pointer hover:text-yellow-400 transition" onclick="setRating(5)"></i>
                        </div>
                        <textarea name="comment" rows="3" class="w-full text-sm p-4 rounded-xl border border-slate-200 focus:outline-none focus:border-primary bg-white mb-3" placeholder="Compartilhe sua experiência..." required></textarea>
                        <div class="text-right"><button class="bg-slate-900 text-white text-xs font-bold px-6 py-2.5 rounded-xl hover:bg-slate-800 transition shadow-lg">Publicar</button></div>
                    </form>
                </div>
                <?php elseif(!isset($_SESSION['user_id'])): ?>
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-6 text-center mb-8">
                        <i class="fas fa-lock text-slate-300 text-2xl mb-2"></i>
                        <p class="text-slate-600 text-sm font-medium mb-3">Faça login para deixar um comentário.</p>
                        <a href="<?= url('/login') ?>" class="inline-block bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-50 transition">Entrar / Criar Conta</a>
                    </div>
                <?php endif; ?>

                <?php if(empty($reviews)): ?>
                    <div class="text-center py-8 opacity-50">
                        <i class="far fa-comment-dots text-4xl text-slate-300 mb-2"></i>
                        <p class="text-slate-400 text-sm">Nenhum comentário ainda. Seja o primeiro!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach($reviews as $rev): 
                            $reviewerName = htmlspecialchars($rev['reviewer_name'] ?? 'Membro');
                            $revInitial = strtoupper(substr($reviewerName, 0, 1));
                        ?>
                        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative hover:border-pink-100 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm border border-slate-200">
                                        <?= $revInitial ?>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 text-sm block"><?= $reviewerName ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="flex text-yellow-400 text-[10px] bg-slate-50 px-2 py-1 rounded-full border border-slate-100"><?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-slate-300"></i>'; ?></div>
                            </div>
                            <div class="pl-1 text-slate-600 text-sm leading-relaxed italic">"<?= htmlspecialchars($rev['comment']) ?>"</div>
                            
                            <?php if(!empty($rev['reply'])): ?>
                                <div class="mt-4 ml-2 pl-4 border-l-2 border-pink-200 bg-pink-50/20 p-4 rounded-r-xl flex gap-3 items-start">
                                    <img src="<?= $profileImg ?>" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                    <div>
                                        <p class="text-[10px] text-pink-600 font-bold uppercase mb-1">Resposta da Modelo:</p>
                                        <p class="text-xs text-slate-600 leading-relaxed"><?= htmlspecialchars($rev['reply']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <div id="lightbox" class="fixed inset-0 z-[100] bg-black/95 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <button onclick="closeLightbox()" class="absolute top-5 right-5 text-white/50 hover:text-white text-4xl transition">&times;</button>
        <button id="prevBtn" class="lightbox-nav-btn" onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></button>
        <button id="nextBtn" class="lightbox-nav-btn" onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></button>
        <img id="lightbox-img" src="" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-2xl scale-95 transition-transform duration-300">
    </div>

    <footer class="bg-white border-t border-slate-100 py-8 mt-12"><div class="max-w-7xl mx-auto px-4 text-center"><p class="text-slate-400 text-xs">&copy; <?= date('Y') ?> TOPModel. Todos os direitos reservados.</p></div></footer>

    <script>
        const galleryImages = <?= json_encode($galleryUrls) ?>;
        let currentIndex = 0;
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');

        function openLightbox(src) {
            if (typeof src === 'number') { currentIndex = src; src = galleryImages[currentIndex]; } else { currentIndex = -1; }
            lightboxImg.src = src; lightbox.classList.remove('hidden');
            setTimeout(() => { lightbox.classList.remove('opacity-0'); lightboxImg.classList.remove('scale-95'); }, 10);
            document.body.style.overflow = 'hidden';
            updateNavButtons();
        }
        function closeLightbox() { lightbox.classList.add('opacity-0'); lightboxImg.classList.add('scale-95'); setTimeout(() => { lightbox.classList.add('hidden'); document.body.style.overflow = ''; }, 300); }
        function changeImage(dir) { if (currentIndex === -1 && galleryImages.length > 0) currentIndex = 0; else { currentIndex = (currentIndex + dir + galleryImages.length) % galleryImages.length; } lightboxImg.style.opacity = '0.5'; setTimeout(() => { lightboxImg.src = galleryImages[currentIndex]; lightboxImg.style.opacity = '1'; }, 150); }
        function updateNavButtons() { const hasGallery = galleryImages.length > 0; document.getElementById('prevBtn').style.display = hasGallery ? 'flex' : 'none'; document.getElementById('nextBtn').style.display = hasGallery ? 'flex' : 'none'; }
        document.addEventListener('keydown', (e) => { if (lightbox.classList.contains('hidden')) return; if (e.key === 'Escape') closeLightbox(); if (e.key === 'ArrowLeft') changeImage(-1); if (e.key === 'ArrowRight') changeImage(1); });

        function setRating(val) {
            document.getElementById('rating-value').value = val;
            const stars = document.querySelectorAll('#star-input i');
            stars.forEach((s, i) => {
                s.className = ''; 
                if (i < val) { s.classList.add('fas', 'fa-star', 'text-yellow-400', 'cursor-pointer', 'transition', 'transform', 'hover:scale-110'); } 
                else { s.classList.add('far', 'fa-star', 'text-slate-300', 'cursor-pointer', 'transition', 'transform', 'hover:scale-110'); }
            });
        }
        async function submitReview(e) {
            e.preventDefault(); const btn = e.target.querySelector('button'); const oldText = btn.innerText; btn.innerText = 'Enviando...'; btn.disabled = true;
            const fd = new FormData(e.target);
            try { const res = await fetch('<?= url('/api/reviews/create') ?>', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(Object.fromEntries(fd)) }); const json = await res.json(); if (json.success) location.reload(); else alert(json.message || 'Erro'); } 
            catch (err) { alert('Erro de conexão.'); } finally { btn.innerText = oldText; btn.disabled = false; }
        }
    </script>
</body>
</html>