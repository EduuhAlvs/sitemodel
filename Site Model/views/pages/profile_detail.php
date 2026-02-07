<?php require __DIR__ . '/../partials/header.php'; ?>

<style>
    .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
    @media(min-width: 768px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; } }
    @media(min-width: 1024px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; } }
</style>

<div class="bg-gray-50 min-h-screen pb-24 md:pb-12 pt-4 md:pt-8">

    <div class="max-w-6xl mx-auto px-4">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-24 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                        <?php 
                            $cover = !empty($photos) ? url('/' . $photos[0]['file_path']) : 'https://via.placeholder.com/600x800?text=Sem+Foto';
                        ?>
                        <div class="relative aspect-[3/4] lg:aspect-[4/5] bg-gray-200">
                            <img src="<?= $cover ?>" class="w-full h-full object-cover">
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                            
                            <div class="absolute bottom-0 left-0 w-full p-6 text-white">
                                <h1 class="text-3xl font-extrabold mb-1 drop-shadow-md">
                                    <?= $profile['display_name'] ?> 
                                    <?php if($profile['ranking_score'] > 1000): ?>
                                        <i class="fas fa-certificate text-gold ml-1 text-xl" title="Verificado VIP"></i>
                                    <?php endif; ?>
                                </h1>
                                <p class="text-lg font-medium opacity-90">
                                    <i class="fas fa-map-marker-alt text-gold mr-2"></i><?= $profile['city_name'] ?? 'Local não informado' ?>
                                </p>
                            </div>
                        </div>

                        <div class="p-6 space-y-3">
                            <?php if($profile['whatsapp_enabled']): ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profile['phone']) ?>?text=Vi+seu+perfil+no+TOPModel" target="_blank" 
                               class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-xl text-center transition shadow-md hover:shadow-lg flex items-center justify-center">
                                <i class="fab fa-whatsapp text-2xl mr-3"></i> Iniciar Conversa
                            </a>
                            <?php endif; ?>
                            
                            <a href="tel:<?= $profile['phone'] ?>" 
                               class="block w-full bg-white border-2 border-pink-500 text-pink-600 hover:bg-pink-50 font-bold py-3 px-4 rounded-xl text-center transition flex items-center justify-center">
                                <i class="fas fa-phone-alt mr-3"></i> <?= $profile['phone'] ?>
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-gray-100 hidden lg:block">
                        <h3 class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-4">Ficha Técnica</h3>
                        <div class="grid grid-cols-2 gap-y-4 text-sm">
                            <div><span class="block text-gray-400">Idade</span> <span class="font-bold text-gray-800"><?= date_diff(date_create($profile['birth_date']), date_create('today'))->y ?> anos</span></div>
                            <div><span class="block text-gray-400">Altura</span> <span class="font-bold text-gray-800"><?= $profile['height_cm'] ?> cm</span></div>
                            <div><span class="block text-gray-400">Peso</span> <span class="font-bold text-gray-800"><?= $profile['weight_kg'] ?> kg</span></div>
                            <div><span class="block text-gray-400">Olhos</span> <span class="font-bold text-gray-800 capitalize"><?= $profile['eye_color'] ?></span></div>
                            <div><span class="block text-gray-400">Cabelo</span> <span class="font-bold text-gray-800 capitalize"><?= $profile['hair_color'] ?></span></div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">

                <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 border border-gray-100">
                    <div class="flex items-center mb-6">
                        <div class="w-1 h-8 bg-gold rounded-full mr-4"></div>
                        <h2 class="text-2xl font-bold text-gray-800">Sobre Mim</h2>
                    </div>
                    
                    <div class="prose prose-pink max-w-none text-gray-600 leading-relaxed whitespace-pre-line text-lg">
                        <?= $profile['bio'] ? htmlspecialchars($profile['bio']) : 'Nenhuma descrição informada.' ?>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h4 class="text-sm font-bold text-gray-400 uppercase mb-3">Idiomas</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($languages as $lang): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">
                                    <img src="https://flagcdn.com/24x18/<?= strtolower($lang['language'] == 'Inglês' ? 'us' : ($lang['language'] == 'Português' ? 'br' : 'fr')) ?>.png" class="mr-2 h-3 w-4 object-cover rounded-sm opacity-80">
                                    <?= $lang['language'] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 lg:hidden">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Detalhes</h3>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <span class="block text-xs text-gray-500 uppercase">Idade</span>
                            <span class="font-bold text-gray-800"><?= date_diff(date_create($profile['birth_date']), date_create('today'))->y ?></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <span class="block text-xs text-gray-500 uppercase">Altura</span>
                            <span class="font-bold text-gray-800"><?= $profile['height_cm'] ?></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <span class="block text-xs text-gray-500 uppercase">Peso</span>
                            <span class="font-bold text-gray-800"><?= $profile['weight_kg'] ?></span>
                        </div>
                         <div class="p-3 bg-gray-50 rounded-lg">
                            <span class="block text-xs text-gray-500 uppercase">Olhos</span>
                            <span class="font-bold text-gray-800 capitalize"><?= $profile['eye_color'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-concierge-bell text-pink-500 mr-2 bg-pink-100 p-2 rounded-full"></i> Serviços
                        </h3>
                        <ul class="space-y-3">
                            <?php if($profile['incall_available']): ?>
                                <li class="flex items-center text-gray-700">
                                    <i class="fas fa-check text-green-500 mr-3"></i> 
                                    <span>Recebe no Local (Incall)</span>
                                </li>
                            <?php endif; ?>
                            <?php if($profile['outcall_available']): ?>
                                <li class="flex items-center text-gray-700">
                                    <i class="fas fa-check text-green-500 mr-3"></i> 
                                    <span>Atende Externo (Outcall)</span>
                                </li>
                            <?php endif; ?>
                            <li class="flex items-center text-gray-500 text-sm italic mt-2 border-t pt-2">
                                * Consulte detalhes pelo WhatsApp
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-map-marked-alt text-blue-500 mr-2 bg-blue-100 p-2 rounded-full"></i> Cidades
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($locations as $loc): ?>
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-sm font-medium border border-blue-100">
                                    <?= $loc['name'] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div id="photos">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Galeria de Fotos</h2>
                        <span class="text-sm text-gray-500"><?= count($photos) ?> fotos</span>
                    </div>
                    
                    <div class="gallery-grid">
                        <?php foreach($photos as $index => $photo): ?>
                            <a href="<?= url('/' . $photo['file_path']) ?>" target="_blank" class="group relative block overflow-hidden rounded-xl shadow-sm hover:shadow-lg transition cursor-zoom-in aspect-[3/4] bg-gray-100">
                                <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if(!$profile['is_24_7']): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Horários Disponíveis</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <?php 
                        $daysMap = ['seg'=>'Seg', 'ter'=>'Ter', 'qua'=>'Qua', 'qui'=>'Qui', 'sex'=>'Sex', 'sab'=>'Sáb', 'dom'=>'Dom'];
                        foreach($profile['working_hours'] as $day => $h): 
                            if(isset($h['active']) && $h['active']):
                        ?>
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <div class="font-bold text-gray-500 uppercase text-xs mb-1"><?= $daysMap[$day] ?? $day ?></div>
                                <div class="text-gray-800 font-medium"><?= $h['start'] ?> - <?= $h['end'] ?></div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div> </div> </div>
</div>

<div class="fixed bottom-0 left-0 w-full bg-white/95 backdrop-blur border-t border-gray-200 p-4 z-50 lg:hidden shadow-[0_-5px_20px_rgba(0,0,0,0.1)]">
    <div class="flex space-x-3">
        <?php if($profile['whatsapp_enabled']): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profile['phone']) ?>?text=Vi+seu+perfil+no+TOPModel" target="_blank" 
               class="flex-1 bg-green-500 active:bg-green-600 text-white font-bold py-3 rounded-xl text-center flex items-center justify-center shadow-lg">
                <i class="fab fa-whatsapp text-2xl mr-2"></i> WhatsApp
            </a>
        <?php endif; ?>
        
        <a href="tel:<?= $profile['phone'] ?>" class="flex-1 bg-gray-900 active:bg-black text-white font-bold py-3 rounded-xl text-center flex items-center justify-center shadow-lg">
            <i class="fas fa-phone text-xl mr-2"></i> Ligar
        </a>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>