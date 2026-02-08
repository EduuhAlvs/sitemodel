<?php
// views/pages/profile_detail.php

// 1. TRATAMENTO DE DADOS (Mantido para segurança)
$workingHours = is_string($profile['working_hours'] ?? '') ? json_decode($profile['working_hours'], true) : ($profile['working_hours'] ?? []);
$serviceDetails = is_string($profile['service_details'] ?? '') ? json_decode($profile['service_details'], true) : ($profile['service_details'] ?? []);
$locations = $locations ?? []; 

// Imagem
$profileImg = !empty($profile['profile_image']) ? url('/' . $profile['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=db2777&color=fff&size=512';

// Idade
$age = 'N/A';
if(!empty($profile['birth_date'])) {
    $age = (new DateTime($profile['birth_date']))->diff(new DateTime())->y;
}

// WhatsApp Link
$phoneClean = preg_replace('/[^0-9]/', '', $profile['phone'] ?? '');
$whatsappLink = "https://wa.me/55{$phoneClean}?text=" . urlencode("Olá {$profile['display_name']}, vi seu perfil no TOP Model.");

// Helper para tradução de cores/olhos se necessário
function translateAttr($val) {
    $map = ['loira'=>'Loira', 'morena'=>'Morena', 'ruiva'=>'Ruiva', 'preto'=>'Pretos', 'castanhos'=>'Castanhos', 'verdes'=>'Verdes', 'azuis'=>'Azuis'];
    return $map[$val] ?? ucfirst($val);
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
        
        /* Cards Padronizados (Igual Dashboard) */
        .panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        
        /* Sticky Sidebar Desktop */
        @media (min-width: 1024px) {
            .sticky-sidebar { position: sticky; top: 5.5rem; }
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
            <div class="w-16"></div> </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-24 pb-20 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <aside class="lg:col-span-4 space-y-6 sticky-sidebar">
            
            <div class="panel p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-br from-slate-100 to-slate-200"></div>
                
                <div class="relative text-center mt-8">
                    <div class="relative inline-block">
                        <img src="<?= $profileImg ?>" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md mx-auto bg-white">
                        <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full" title="Online"></div>
                    </div>
                    
                    <h1 class="font-display font-bold text-2xl text-slate-900 mt-3 flex justify-center items-center gap-1">
                        <?= htmlspecialchars($profile['display_name']) ?>
                        <i class="fas fa-check-circle text-blue-500 text-lg"></i>
                    </h1>
                    
                    <div class="flex justify-center gap-2 mt-2 mb-6">
                        <?php if($profile['current_plan_level'] > 0): ?>
                            <span class="bg-yellow-50 text-yellow-700 border border-yellow-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                <i class="fas fa-crown"></i> VIP
                            </span>
                        <?php endif; ?>
                        <span class="bg-slate-50 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($profile['city'] ?? 'Brasil') ?>
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
                        <span class="text-[10px] text-slate-400">Gênero</span>
                        <span class="font-semibold text-slate-700 text-sm capitalize"><?= $profile['gender']=='woman'?'Mulher':$profile['gender'] ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Altura</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $profile['height_cm'] ?> cm</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Peso</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= $profile['weight_kg'] ?> kg</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Cabelos</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['hair_color']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400">Olhos</span>
                        <span class="font-semibold text-slate-700 text-sm"><?= translateAttr($profile['eye_color']) ?></span>
                    </div>
                </div>
                
                <div class="border-t border-slate-100 mt-4 pt-4 flex flex-wrap gap-2">
                    <?php if($profile['tattoos']): ?>
                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold">Com Tatuagem</span>
                    <?php endif; ?>
                    <?php if($profile['smoker'] == 'no'): ?>
                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-[10px] font-bold">Não Fuma</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($languages)): ?>
            <div class="panel p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Idiomas</h3>
                <div class="space-y-2">
                    <?php foreach($languages as $lang): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium text-slate-700"><?= $lang['language'] ?></span>
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
                        <?php foreach($photos as $photo): 
                            if(!$photo['is_approved']) continue;
                        ?>
                            <div class="group relative aspect-[3/4] rounded-lg overflow-hidden cursor-zoom-in bg-slate-100">
                                <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
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
                                <li class="flex items-center gap-2"><i class="fas fa-home text-pink-400 text-xs"></i> Apartamento</li>
                            <?php endif; ?>
                            <?php if(!empty($serviceDetails['incall_hotel'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-hotel text-pink-400 text-xs"></i> Hotel</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">Indisponível</span>
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
                                <li class="flex items-center gap-2"><i class="fas fa-bed text-indigo-400 text-xs"></i> Hotéis/Motéis</li>
                            <?php endif; ?>
                            <?php if(!empty($serviceDetails['outcall_home'])): ?>
                                <li class="flex items-center gap-2"><i class="fas fa-building text-indigo-400 text-xs"></i> Residências</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">Indisponível</span>
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

        </main>
    </div>

    <?php if($profile['whatsapp_enabled']): ?>
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white/90 backdrop-blur border-t border-slate-200 lg:hidden z-40">
        <a href="<?= $whatsappLink ?>" target="_blank" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg flex justify-center items-center gap-2">
            <i class="fab fa-whatsapp text-xl"></i> WhatsApp
        </a>
    </div>
    <?php endif; ?>

</body>
</html>